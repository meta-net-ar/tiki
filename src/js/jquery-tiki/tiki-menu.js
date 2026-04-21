/**
 * Dynamically adjusts the position of dropdown menus
 * based on their location in the viewport.
 *
 * This script is separated to avoid executing logic in files
 * meant only for global function and structure definitions.
 *
 * Dependencies: jQuery
 * Determine dropdown orientation based on module position
 * instead of viewport-based heuristics.
 *
 * This avoids unreliable layout assumptions and ensures
 * consistent behavior across themes and screen sizes.
 */

$(function () {
    $(".dropdown, .mega-menu").each(function () {
        const $parent = $(this);
        const $menu = $parent.find(".dropdown-menu, ul").first();
        if (!$menu.length || $parent.hasClass("sm-nav-item") || $menu.hasClass("sm-sub")) return;

        const id = $parent.closest(".card-body").attr("id");

        if (id && id.startsWith("mod-menubottom")) {
            $menu.addClass("dropdown-menu-bottom");
        } else {
            $menu.addClass("dropdown-menu-top-t");
        }
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
            var left = Math.round(rect.left + (rect.width - menuMaxW) / 2);
            left = Math.max(8, Math.min(left, viewportW - menuMaxW - 8));
            var top = rect.bottom + 4;

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

/* Fix mobile touch for menu parents that have links so they open instead of navigating */
$(document).on("touchend click", ".sm-navbar .sm-sub-toggler", function (e) {
    var $toggler = $(this);
    var $navbar = $toggler.closest(".sm-navbar");

    // Only run on mobile
    if (!$navbar.hasClass("sm-collapsible") && window.innerWidth >= 768) {
        return;
    }

    // Stop the link from opening and avoid double clicks
    e.preventDefault();
    e.stopImmediatePropagation();

    var $panel = $toggler.siblings(".sm-sub").first();
    if (!$panel.length || !window.bootstrap || !window.bootstrap.Collapse) return;

    // Open or close the menu using Bootstrap
    var bsCollapse = window.bootstrap.Collapse.getOrCreateInstance($panel[0], { toggle: false });
    var isShown = $panel.hasClass("show");

    bsCollapse.toggle();
    $toggler.attr("aria-expanded", isShown ? "false" : "true");

    // Close other open menus at the same level
    if (!isShown) {
        $toggler
            .closest("li")
            .siblings("li")
            .find("> .sm-sub.show")
            .each(function () {
                var peer = window.bootstrap.Collapse.getInstance(this);
                if (peer) peer.hide();
                $(this).siblings(".sm-sub-toggler").attr("aria-expanded", "false");
            });
    }
});

// end of src/js/tiki-menu.js
