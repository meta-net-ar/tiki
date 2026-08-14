import { debounce, throttle } from "underscore";

/**
 * @typedef {Object} MouseoverPluginOptions
 * @property {string} anchorId DOM id of the trigger element (without '#').
 * @property {string} popupId DOM id of the popup element (without '#').
 * @property {boolean} [isSticky=false] If true, popup stays open until click (or optional auto-close).
 * @property {number} [closeDelayMs=0] Sticky mode auto-close delay in milliseconds. `0` disables auto-close.
 * @property {number} [hideDelayMs=80] Non-sticky hide debounce in milliseconds to avoid flicker on brief hover gaps.
 * @property {number} [offsetX=0] Horizontal delta from cursor-based placement (pixels).
 * First placement uses mouseenter coordinates, then mousemove updates.
 * @property {number} [offsetY=0] Vertical delta from cursor-based placement (pixels).
 * First placement uses mouseenter coordinates, then mousemove updates.
 * @property {string} [effect=""] jQuery UI effect name used by `showJQ`/`hideJQ`; empty string means default show/hide.
 * @property {"normal"|"fast"|"slow"|string} [speed="normal"] Animation speed forwarded to `showJQ`/`hideJQ`.
 */

/**
 * Initializes hover/click behavior and viewport-safe positioning for plugin popups.
 *
 * `closeDelayMs` and `hideDelayMs` serve different purposes:
 * - `closeDelayMs`: sticky popups auto-close timer.
 * - `hideDelayMs`: debounce for non-sticky mouseleave.
 *
 * @param {MouseoverPluginOptions} [options={}]
 */
export function initMouseoverPlugin(options = {}) {
    const viewportPaddingPx = 4;
    const anchor = document.getElementById(options.anchorId);
    const popup = document.getElementById(options.popupId);
    if (!anchor || !popup) {
        return;
    }

    const popupSelector = `#${options.popupId}`;
    const isSticky = Boolean(options.isSticky);
    const closeDelayMs = Number(options.closeDelayMs) || 0;
    const hideDelayMs = Number(options.hideDelayMs) || 80;
    const offsetX = Number(options.offsetX) || 0;
    const offsetY = Number(options.offsetY) || 0;
    const effect = options.effect || "";
    const speed = options.speed || "normal";

    const showPopup = () => {
        if (typeof window.showJQ === "function") {
            window.showJQ(popupSelector, effect, speed);
        } else {
            popup.style.display = "block";
        }
    };

    const hidePopupNow = () => {
        if (typeof window.hideJQ === "function") {
            window.hideJQ(popupSelector, effect, speed);
        } else {
            popup.style.display = "none";
        }
    };

    // Position from cursor + offsets, flip near viewport edges, then avoid cursor overlap.
    // This keeps hover stable and prevents leave/enter flicker ("bounce").
    const positionPopup = throttle((event) => {
        const popupWidth = popup.offsetWidth || popup.clientWidth || 0;
        const popupHeight = popup.offsetHeight || popup.clientHeight || 0;
        const viewportLeft = window.scrollX;
        const viewportTop = window.scrollY;
        const viewportRight = viewportLeft + window.innerWidth;
        const viewportBottom = viewportTop + window.innerHeight;

        let left = event.pageX + offsetX;
        let top = event.pageY + offsetY;

        if (left + popupWidth > viewportRight) {
            left = event.pageX - popupWidth - offsetX;
        }

        if (top + popupHeight > viewportBottom) {
            top = event.pageY - popupHeight - offsetY;
        }

        if (left < viewportLeft) {
            left = viewportLeft + viewportPaddingPx;
        }

        if (top < viewportTop) {
            top = viewportTop + viewportPaddingPx;
        }

        // If popup would cover the cursor, move it to the other side.
        // Otherwise the cursor can enter the popup and trigger hover oscillation.
        if (left <= event.pageX && event.pageX <= left + popupWidth && top <= event.pageY && event.pageY <= top + popupHeight) {
            left = Math.max(viewportLeft + viewportPaddingPx, event.pageX - popupWidth - offsetX);
        }

        popup.style.left = left + "px";
        popup.style.top = top + "px";
    }, 16);

    // Workaround for inline anchors split across lines: don't hide immediately on brief hover gaps.
    const hidePopup = debounce(hidePopupNow, hideDelayMs);
    const stickyAutoHide = isSticky && closeDelayMs > 0 ? debounce(hidePopupNow, closeDelayMs) : null;

    anchor.addEventListener("mouseenter", (event) => {
        hidePopup.cancel();
        if (stickyAutoHide) {
            stickyAutoHide.cancel();
        }
        showPopup();
        positionPopup(event);
        if (stickyAutoHide) {
            stickyAutoHide();
        }
    });

    anchor.addEventListener("mousemove", positionPopup);

    anchor.addEventListener("mouseleave", () => {
        if (!isSticky) {
            hidePopup();
        }
    });

    if (isSticky) {
        popup.style.cursor = "pointer";
        popup.addEventListener("click", () => {
            hidePopup.cancel();
            if (stickyAutoHide) {
                stickyAutoHide.cancel();
            }
            hidePopupNow();
        });
    }
}
