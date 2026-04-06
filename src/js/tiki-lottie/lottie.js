/**
 * Tiki Lottie - Local bundle wrapper for @lottiefiles/dotlottie-wc
 *
 * This module imports the dotLottie Web Component and provides
 * initialization and click-to-play functionality for wiki pages.
 */

import "@lottiefiles/dotlottie-wc";

/**
 * Initialize click-to-play/pause functionality for all Lottie animations on the page.
 * Call this function after the DOM is loaded.
 */
export function initLottieClickHandlers() {
    document.querySelectorAll("dotlottie-wc").forEach((el) => {
        el.style.cursor = "pointer";
        el.addEventListener("click", function () {
            const lottie = el.dotLottie;
            if (lottie) {
                if (lottie.isPlaying) {
                    lottie.pause();
                } else {
                    lottie.play();
                }
            }
        });
    });
}

// Auto-initialize when DOM is ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initLottieClickHandlers);
} else {
    initLottieClickHandlers();
}
