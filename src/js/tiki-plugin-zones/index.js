/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 * Plugin zones: make it clear which part of the page each wiki plugin produced.
 *
 * 1. Hovering (or focusing) a plugin edit icon highlights the content that plugin produced.
 * 2. Hovering that content highlights it too and shows an "Edit PLUGIN" button on its top
 *    start corner, so the way to edit the plugin can be found without knowing about the icon.
 *
 * The server (WikiParser_Parsable::parse_first()) surrounds the output of every editable
 * plugin with a pair of HTML comments carrying the id of its edit icon:
 *
 *     <!--tiki-plugin:start plugin-edit-box1-->  ...output...  <!--tiki-plugin:end plugin-edit-box1-->
 *     <a id="plugin-edit-box1" class="editplugin" data-plugin="box" data-index="1">...</a>
 *
 * Comments are used rather than a wrapper element because plugin output can be wiki markup
 * parsed afterwards, plain text inside a paragraph, several sibling elements or table content:
 * a wrapper would be split or moved by the HTML parser and would change the page layout.
 * A DOM Range between the two comments covers exactly the plugin's output, whatever its shape.
 */

const START = /^tiki-plugin:start ([\w-]+)$/;
const END = /^tiki-plugin:end ([\w-]+)$/;
const HIDE_DELAY = 250; // ms, time to move the pointer from the content to the button
const OFFSET = 3; // px, matches the outline-offset of .tiki-plugin-zone-outline

const translate = (text) => (typeof window.tr === "function" ? window.tr(text) : text);

export default function initPluginZones(container) {
    const root = container || document.querySelector("#page-data > .content") || document.querySelector("#page-data");
    if (! root || root.tikiPluginZones) {
        return null;
    }

    const rtl = getComputedStyle(root).direction === "rtl";
    let zones = [];
    let current = null;
    let hideTimer = null;
    let frame = 0;

    // --- the zones -----------------------------------------------------------------------

    function collectZones() {
        const found = [];
        const open = [];
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_COMMENT);

        while (walker.nextNode()) {
            const comment = walker.currentNode;
            const text = comment.data.trim();
            let match;

            if ((match = START.exec(text))) {
                open.push({ id: match[1], start: comment, depth: open.length });
            } else if ((match = END.exec(text)) && open.length && open[open.length - 1].id === match[1]) {
                const zone = open.pop();
                zone.icon = document.getElementById(zone.id);
                zone.name = (zone.icon && zone.icon.dataset.plugin) || zone.id.replace(/^plugin-edit-/, "").replace(/\d+$/, "");
                zone.range = document.createRange();
                zone.range.setStartAfter(zone.start);
                zone.range.setEndBefore(comment);
                found.push(zone);
            }
        }

        // innermost first, so a plugin inside another plugin wins
        found.sort((a, b) => b.depth - a.depth);
        return found;
    }

    /** Bounding box of everything the plugin produced, in viewport coordinates, or null when nothing is visible. */
    function zoneBox(zone) {
        // Recomputed every time: plugins such as PluginPivotTable or PluginChart build their
        // content with javascript, so sizes are not known when the page is first read.
        let left = Infinity;
        let top = Infinity;
        let right = -Infinity;
        let bottom = -Infinity;

        for (const rect of zone.range.getClientRects()) {
            if (rect.width < 1 || rect.height < 1) {
                continue;
            }
            left = Math.min(left, rect.left);
            top = Math.min(top, rect.top);
            right = Math.max(right, rect.right);
            bottom = Math.max(bottom, rect.bottom);
        }

        return left === Infinity ? null : { left, top, width: right - left, height: bottom - top };
    }

    function nodeInRange(range, node) {
        const parent = node.parentNode;
        if (! parent) {
            return false;
        }
        const index = Array.prototype.indexOf.call(parent.childNodes, node);
        return range.comparePoint(parent, index) === 0 && range.comparePoint(parent, index + 1) === 0;
    }

    function zoneAtPoint(target, x, y) {
        for (let node = target; node && node !== root; node = node.parentNode) {
            for (const zone of zones) {
                if (nodeInRange(zone.range, node)) {
                    return zone;
                }
            }
        }

        // Plugins whose output is only text (PluginNow, PluginSort...) end up inside a paragraph
        // that is not itself part of any zone, so ask the browser which character is under the pointer.
        const caret = document.caretPositionFromPoint
            ? document.caretPositionFromPoint(x, y)
            : document.caretRangeFromPoint && document.caretRangeFromPoint(x, y);

        if (caret) {
            const node = caret.offsetNode || caret.startContainer;
            const offset = caret.offset !== undefined ? caret.offset : caret.startOffset;
            for (const zone of zones) {
                if (zone.range.isPointInRange(node, offset)) {
                    return zone;
                }
            }
        }

        return null;
    }

    // --- the overlay ---------------------------------------------------------------------

    const outline = document.createElement("div");
    outline.className = "tiki-plugin-zone-outline";
    outline.setAttribute("aria-hidden", "true");

    const label = document.createElement("span");
    label.className = "tiki-plugin-zone-label";
    outline.appendChild(label);

    const button = document.createElement("button");
    button.type = "button";
    button.className = "tiki-plugin-zone-edit";

    document.body.appendChild(outline);
    document.body.appendChild(button);

    function show(zone, withButton) {
        clearTimeout(hideTimer);
        current = zone;

        const visible = zoneBox(zone);
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        // A plugin can produce nothing visible (PluginJq, PluginCss...): point at the icon instead.
        const iconBox = zone.icon ? zone.icon.getBoundingClientRect() : null;
        const box = visible || { left: iconBox ? iconBox.left : 0, top: iconBox ? iconBox.top : 0, width: 24, height: 24 };

        outline.classList.add("shown");
        outline.classList.toggle("empty", ! visible);
        outline.style.left = box.left + scrollX + "px";
        outline.style.top = box.top + scrollY + "px";
        outline.style.width = box.width + "px";
        outline.style.height = box.height + "px";

        label.textContent = visible ? zone.name : zone.name + " · " + translate("no visible output");
        label.hidden = withButton; // the button names the plugin already

        if (! withButton) {
            button.classList.remove("shown");
            return;
        }

        button.textContent = translate("Edit") + " " + zone.name.toUpperCase();
        button.setAttribute("aria-label", translate("Edit plugin") + " " + zone.name.toUpperCase());
        button.classList.add("shown");

        // A tab on the top start edge, outside the content, so it never covers the plugin's own
        // controls; tucked inside only when the content touches the top of the page.
        const width = button.offsetWidth;
        const height = button.offsetHeight;
        const x = rtl ? box.left + box.width + OFFSET - width : box.left - OFFSET;
        const y = box.top - OFFSET - height + scrollY >= 0 ? box.top - OFFSET - height : box.top;
        button.style.left = x + scrollX + "px";
        button.style.top = y + scrollY + "px";
    }

    function hide(delay) {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(
            () => {
                outline.classList.remove("shown");
                button.classList.remove("shown");
                current = null;
            },
            delay || 0
        );
    }

    // --- events --------------------------------------------------------------------------

    function bindIcons() {
        for (const zone of zones) {
            if (! zone.icon || zone.icon.tikiPluginZone) {
                continue;
            }
            zone.icon.tikiPluginZone = true;
            zone.icon.addEventListener("mouseenter", () => show(zoneOf(zone.id) || zone, false));
            zone.icon.addEventListener("focus", () => show(zoneOf(zone.id) || zone, false));
            zone.icon.addEventListener("mouseleave", () => hide());
            zone.icon.addEventListener("blur", () => hide());
        }
    }

    function zoneOf(id) {
        return zones.find((zone) => zone.id === id) || null;
    }

    root.addEventListener("pointermove", (event) => {
        if (event.pointerType === "touch" || frame) {
            return; // no hovering on touch screens; the edit icons stay the way in
        }
        frame = requestAnimationFrame(() => {
            frame = 0;
            const target = event.target;
            if (target.closest && target.closest("a.editplugin")) {
                return; // the icon has its own handler
            }
            const zone = zoneAtPoint(target, event.clientX, event.clientY);
            if (zone) {
                show(zone, true);
            } else if (current) {
                hide(HIDE_DELAY);
            }
        });
    });

    root.addEventListener("pointerleave", () => hide(HIDE_DELAY));
    button.addEventListener("pointerenter", () => clearTimeout(hideTimer));
    button.addEventListener("pointerleave", () => hide(HIDE_DELAY));
    button.addEventListener("click", () => {
        if (current && current.icon) {
            current.icon.click(); // opens the usual plugin edit form (popupPluginForm)
        }
    });
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            hide();
        }
    });

    function refresh() {
        zones = collectZones();
        bindIcons();
    }

    refresh();

    // Pages loaded over ajax, and plugins that rebuild their own content, change the markers.
    let refreshTimer = null;
    new MutationObserver(() => {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refresh, 200);
    }).observe(root, { childList: true, subtree: true });

    root.tikiPluginZones = { refresh, zones: () => zones };
    return root.tikiPluginZones;
}
