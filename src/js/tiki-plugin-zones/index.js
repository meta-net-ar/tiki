/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 **/

// The id shape the parser emits. Anything else in a comment is ignored, so a page
// that contains hand written HTML cannot forge a zone pointing at another element.
const ID = "plugin-edit-[a-z0-9_]+\\d+";
const START = new RegExp("^tiki-plugin:start (" + ID + ")$", "i");
const END = new RegExp("^tiki-plugin:end (" + ID + ")$", "i");
const HIDE_DELAY = 250; // ms, time to move the pointer from the content to the button
const OFFSET = 3; // px, matches the outline-offset of .tiki-plugin-zone-outline
const MIN_SIZE = 80; // px, under this the corners are too close together to be worth choosing
const HYSTERESIS = 0.3; // how far past the middle the pointer goes before the corner changes

const translate = (text) => (typeof window.tr === "function" ? window.tr(text) : text);

/**
 * @param {Object} options
 * @param {string} options.mode "zone" hides the edit icons at rest and relies on the highlight,
 *                              "both" keeps the icons visible as before. See wiki_edit_plugin_mode.
 * @param {Element} options.container the page content, for tests
 */
export default function initPluginZones(options = {}) {
    const root = options.container || document.querySelector("#page-data > .content") || document.querySelector("#page-data");
    if (!root || root.tikiPluginZones) {
        return null;
    }

    // The icons are hidden in css rather than here, so that a device without a mouse,
    // where nothing can be hovered, still shows them.
    if (options.mode !== "both") {
        document.body.classList.add("tiki-plugin-zone-mode");
    }

    const rtl = getComputedStyle(root).direction === "rtl";
    let zones = [];
    let cache = new WeakMap();
    let current = null;
    let corner = null;
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
                const icon = document.getElementById(zone.id);
                zone.icon = icon && icon.classList.contains("editplugin") ? icon : null;
                zone.data = (window.tikiPluginZoneData || {})[zone.id] || null;
                if (!zone.icon && !zone.data) {
                    continue; // neither an edit icon of ours nor parameters: stale or forged markers
                }
                zone.name = (zone.icon && zone.icon.dataset.plugin) || (zone.data && zone.data.type) || zone.id;
                zone.end = comment;
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
        if (!parent) {
            return false;
        }
        const index = Array.prototype.indexOf.call(parent.childNodes, node);
        return range.comparePoint(parent, index) === 0 && range.comparePoint(parent, index + 1) === 0;
    }

    function zoneAtPoint(target, x, y) {
        if (cache.has(target)) {
            return cache.get(target);
        }
        const found = findZone(target, x, y);
        cache.set(target, found);
        return found;
    }

    function findZone(target, x, y) {
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
    button.className = "btn btn-xs btn-primary tiki-plugin-zone-edit";

    outline.appendChild(button);
    document.body.appendChild(outline);

    function show(zone, withButton, pointer) {
        clearTimeout(hideTimer);
        if (zone !== current) {
            corner = null;
        }
        current = zone;

        const visible = zoneBox(zone);
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;
        // A plugin can produce nothing visible (PluginJq, PluginCss...): point at the icon instead.
        const iconBox = zone.icon ? zone.icon.getBoundingClientRect() : null;
        const box = visible || { left: iconBox ? iconBox.left : 0, top: iconBox ? iconBox.top : 0, width: 24, height: 24 };

        outline.classList.add("shown");
        outline.classList.toggle("empty", !visible);
        outline.style.left = box.left + scrollX + "px";
        outline.style.top = box.top + scrollY + "px";
        outline.style.width = box.width + "px";
        outline.style.height = box.height + "px";

        label.textContent = visible ? zone.name : zone.name + " · " + translate("no visible output");
        label.hidden = withButton; // the button names the plugin already

        if (!withButton) {
            button.classList.remove("shown");
            return;
        }

        button.textContent = translate("Edit") + " " + zone.name.toUpperCase();
        button.setAttribute("aria-label", translate("Edit plugin") + " " + zone.name.toUpperCase());
        button.classList.add("shown");

        if (!corner) {
            corner = { right: rtl, bottom: false };
        }
        if (pointer && box.width > MIN_SIZE) {
            const margin = Math.min((box.width / 2) * HYSTERESIS, 120);
            const centre = box.left + box.width / 2;
            if (pointer.x > centre + margin) {
                corner.right = true;
            } else if (pointer.x < centre - margin) {
                corner.right = false;
            }
        }
        if (pointer && box.height > MIN_SIZE) {
            const margin = Math.min((box.height / 2) * HYSTERESIS, 120);
            const centre = box.top + box.height / 2;
            if (pointer.y > centre + margin) {
                corner.bottom = true;
            } else if (pointer.y < centre - margin) {
                corner.bottom = false;
            }
        }

        button.classList.toggle("is-right", corner.right);
        button.classList.toggle("is-bottom", corner.bottom);
        keepVisible();
    }

    /**
     * A plugin can be taller than the window, and a page can load in the middle of one, which
     * would leave the button out of sight. Keep it against the visible part of the zone.
     */
    function keepVisible() {
        if (!current || !button.classList.contains("shown")) {
            return;
        }

        button.style.top = "";
        button.style.bottom = "";

        const zone = outline.getBoundingClientRect();
        const rect = button.getBoundingClientRect();
        const room = rect.height + OFFSET;

        if (rect.top < 0 && zone.bottom > room) {
            button.style.top = Math.max(0, -zone.top) + "px";
            button.style.bottom = "auto";
        } else if (rect.bottom > window.innerHeight && zone.top < window.innerHeight - room) {
            button.style.top = Math.max(0, window.innerHeight - zone.top - rect.height) + "px";
            button.style.bottom = "auto";
        }
    }

    function hide(delay) {
        clearTimeout(hideTimer);
        hideTimer = setTimeout(() => {
            outline.classList.remove("shown");
            button.classList.remove("shown");
            current = null;
        }, delay || 0);
    }

    // --- events --------------------------------------------------------------------------

    function bindIcons() {
        for (const zone of zones) {
            const anchor = zone.icon || keyboardStop(zone);
            if (!anchor || anchor.tikiPluginZone) {
                continue;
            }
            anchor.tikiPluginZone = true;
            anchor.addEventListener("mouseenter", () => show(zoneOf(zone.id) || zone, false));
            anchor.addEventListener("focus", () => show(zoneOf(zone.id) || zone, false));
            anchor.addEventListener("mouseleave", () => hide());
            anchor.addEventListener("blur", () => hide());
        }
    }

    /** In zone mode nothing is in the page to tab to, so put a button where the icon used to be. */
    function keyboardStop(zone) {
        if (zone.stop) {
            return zone.stop;
        }
        // The zones are rebuilt from the markers on every refresh, so look in the page
        // rather than trusting the previous object, or a button is added on every pass.
        const existing = zone.end.nextElementSibling;
        if (existing && existing.classList.contains("tiki-plugin-zone-stop")) {
            zone.stop = existing;
            return existing;
        }
        const stop = document.createElement("button");
        stop.type = "button";
        stop.className = "tiki-plugin-zone-stop";
        stop.textContent = translate("Edit plugin") + " " + zone.name.toUpperCase();
        stop.addEventListener("click", () => openEditor(zoneOf(zone.id) || zone, stop));
        zone.end.parentNode.insertBefore(stop, zone.end.nextSibling);
        zone.stop = stop;
        return stop;
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
            if (target.closest && target.closest("a.editplugin, .tiki-plugin-zone-stop")) {
                return; // those carry their own handlers
            }
            const zone = zoneAtPoint(target, event.clientX, event.clientY);
            if (zone) {
                show(zone, true, { x: event.clientX, y: event.clientY });
            } else if (current) {
                hide(HIDE_DELAY);
            }
        });
    });

    root.addEventListener("pointerleave", () => hide(HIDE_DELAY));

    let pending = 0;
    const follow = () => {
        if (pending) {
            return;
        }
        pending = requestAnimationFrame(() => {
            pending = 0;
            keepVisible();
        });
    };
    window.addEventListener("scroll", follow, { passive: true });
    window.addEventListener("resize", follow, { passive: true });
    button.addEventListener("pointerenter", () => clearTimeout(hideTimer));
    button.addEventListener("pointerleave", () => hide(HIDE_DELAY));
    button.addEventListener("click", () => openEditor(current, button));

    function openEditor(zone, trigger) {
        if (!zone) {
            return;
        }
        if (zone.icon) {
            zone.icon.click(); // the icon carries its own handler
        } else if (zone.data && typeof window.popupPluginForm === "function") {
            const data = zone.data;
            window.popupPluginForm("editwiki", data.type, data.index, data.page, data.args, data.isMarkdown, data.body, trigger);
        }
    }
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            hide();
        }
    });

    function refresh() {
        zones = collectZones();
        cache = new WeakMap();
        bindIcons();
    }

    refresh();

    // Pages loaded over ajax, and plugins that rebuild their own content, change the markers.
    let refreshTimer = null;
    const ours = (node) => node.nodeType === 1 && node.classList.contains("tiki-plugin-zone-stop");
    new MutationObserver((records) => {
        const fromUs = records.every((record) => [...record.addedNodes].every(ours) && [...record.removedNodes].every(ours));
        if (fromUs) {
            return;
        }
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refresh, 200);
    }).observe(root, { childList: true, subtree: true });

    root.tikiPluginZones = { refresh, zones: () => zones };
    return root.tikiPluginZones;
}
