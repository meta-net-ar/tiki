/**
 * Dynamically adjusts the position of dropdown menus
 * based on their location in the viewport.
 *
 * This script is separated to avoid executing logic in files
 * meant only for global function and structure definitions.
 *
 * Dependencies: jQuery
 */

$(function () {
    $(".sm-nav-item, .dropdown, .mega-menu").each(function () {
        const rect = this.getBoundingClientRect();
        const position = rect.top < window.innerHeight / 2 ? "top" : "bottom";

        let $dropdown = $(this).find(".dropdown-menu");
        if ($dropdown.length === 0) {
            $dropdown = $(this).find("ul");
        }

        if ($dropdown.length > 0) {
            if (position !== "bottom") {
                $dropdown.removeClass("dropdown-menu-bottom");
            } else {
                $dropdown.addClass("dropdown-menu-bottom");
            }
        }
    });
});
// end of src/js/tiki-menu.js
