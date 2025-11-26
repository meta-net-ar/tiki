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

    $(".sm-nav-item").on("click", function () {
        const $menu = $(this).find(".dropdown-menu, ul").first();
        if ($menu.length === 0) return;

        const rect = this.getBoundingClientRect();

        // Determine vertical and horizontal position
        const vertical = rect.top < window.innerHeight / 2 ? "top-t" : "bottom-b";
        const horizontal = rect.left < window.innerWidth / 2 ? "end-l" : "start-r";

        // Remove existing position classes
        $menu.removeClass("dropdown-menu-top-t dropdown-menu-bottom-b dropdown-menu-end-l dropdown-menu-start-r");

        if (vertical && horizontal) {
            // Add new position classes
            $menu.addClass(`dropdown-menu-${vertical} dropdown-menu-${horizontal}`);
        }
    });
});

// end of src/js/tiki-menu.js
