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

    // Position submenu dropdowns appropriately when expanded
    $(document).on("shown.bs.collapse", ".sm-sub", function () {
        const $menu = $(this);
        const $toggler = $menu.prev(".sm-sub-toggler");
        if ($toggler.length === 0) return;

        const rect = $toggler[0].getBoundingClientRect();
        const vertical = rect.top < window.innerHeight / 2 ? "top-t" : "bottom-b";
        const horizontal = rect.left < window.innerWidth / 2 ? "end-l" : "start-r";

        $menu.removeClass("dropdown-menu-top-t dropdown-menu-bottom-b dropdown-menu-end-l dropdown-menu-start-r");
        $menu.addClass(`dropdown-menu-${vertical} dropdown-menu-${horizontal}`);
    });
});

/* Mobile dropdown positioning */
(function ($) {
    $(function () {
        function positionDropdownMobile($menu, $toggle) {
            if (window.innerWidth > 576) return;
            if (!$menu.length || !$toggle.length) return;

            var wasHidden = false;
            if (!$menu.is(":visible")) {
                wasHidden = true;
                $menu.addClass("show");
            }

            var rect = $toggle[0].getBoundingClientRect();
            var viewportW = window.innerWidth;
            var viewportH = window.innerHeight;

            var menuMaxW = Math.min(viewportW - 16, Math.max(200, $menu.outerWidth()));
            $menu.css({ position: "fixed", "box-sizing": "border-box", width: menuMaxW + "px" });

            var menuH = Math.min($menu.outerHeight(), viewportH - 16);
            $menu.css("max-height", menuH + "px");

            var topBelow = rect.bottom + 8;
            var topAbove = rect.top - menuH - 8;
            var top = topBelow + menuH <= viewportH - 8 ? topBelow : Math.max(8, topAbove);

            var left = Math.round(rect.left + (rect.width - menuMaxW) / 2);
            left = Math.max(8, Math.min(left, viewportW - menuMaxW - 8));

            $menu.css({ top: top + "px", left: left + "px", right: "auto", transform: "none", zIndex: 1060 });

            if (wasHidden) {
                $menu.removeClass("show");
            }
        }

        $(document).on("shown.bs.dropdown", "#quickadmin .dropdown, .siteloginbar_popup", function () {
            var $root = $(this);
            var $toggle = $root.find('[data-bs-toggle="dropdown"]').first();
            var $menu = $root.find(".dropdown-menu").first();
            positionDropdownMobile($menu, $toggle);
            $(window).on("resize._dropdownPos", function () {
                positionDropdownMobile($menu, $toggle);
            });
        });

        $(document).on("hidden.bs.dropdown", "#quickadmin .dropdown, .siteloginbar_popup", function () {
            var $menu = $(this).find(".dropdown-menu").first();
            $menu.css({ position: "", top: "", left: "", right: "", width: "", maxHeight: "", transform: "", zIndex: "" });
            $(window).off("resize._dropdownPos");
        });
    });
})(jQuery);

// end of src/js/tiki-menu.js
