/**
 * Wiki plugin FADE: Bootstrap collapse, print snapshot.
 */
(function ($) {
    "use strict";

    let printState = null;

    function fadeHasBootstrapCollapse($fade) {
        return $fade.find(".collapse").first().length > 0;
    }

    function legacyToggle($link, $body, speeds) {
        const $fade = $link.closest(".wikiplugin-fade");
        $body.stop(true, true);
        const willExpand = $body.is(":hidden");
        if (willExpand) {
            $body.show("blind", {}, speeds.show, function () {
                syncFadeUiFromExpanded($fade, isFadeExpanded($fade));
            });
        } else {
            $body.hide("blind", {}, speeds.hide, function () {
                syncFadeUiFromExpanded($fade, isFadeExpanded($fade));
            });
        }
        syncFadeUiFromExpanded($fade, willExpand);
    }

    /**
     * @param {string|undefined|null} group Empty string or null/undefined = all .wikiplugin-fade on the page.
     */
    function getFadeSet(group) {
        // The html editor (wysiwyg, inline editing) renders a hidden second copy of the page.
        // Those blocks can never be expanded, so grouped actions must ignore them.
        const $all = $(".wikiplugin-fade").not(".inline-editor-content .wikiplugin-fade");
        if (group === undefined || group === null || group === "") {
            return $all;
        }
        const g = String(group);
        return $all.filter(function () {
            return ($(this).attr("data-fade-group") || "") === g;
        });
    }

    function isFadeExpanded($fade) {
        const $collapse = $fade.find(".collapse").first();
        if (fadeHasBootstrapCollapse($fade)) {
            return $collapse.hasClass("show");
        }
        const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
        return $body.length > 0 && $body.is(":visible");
    }

    function setBootstrapTriggerAria($fade, expanded) {
        const $a = $fade.find('.card-header a[data-bs-toggle="collapse"]').first();
        if ($a.length) {
            $a.attr("aria-expanded", expanded ? "true" : "false");
        }
    }

    /**
     * Point the toggle indicator the right way after a block was opened or closed by script
     * (expand all, print, initial state): the chevron in Bootstrap, the arrow icon in legacy.
     * Clicks already update it on their own.
     * @param {JQuery} $fade
     * @param {boolean} expanded
     */
    function syncFadeUiFromExpanded($fade, expanded) {
        if (fadeHasBootstrapCollapse($fade)) {
            setBootstrapTriggerAria($fade, expanded);
        } else {
            syncLegacyLinkIconClasses($fade.find(".wpfade-legacy-toggle").first(), expanded);
        }
    }

    function syncLegacyLinkIconClasses($link, expanded) {
        if (!$link.length) {
            return;
        }
        if ($link.hasClass("wpfade-hidden") || $link.hasClass("wpfade-shown")) {
            if (expanded) {
                $link.removeClass("wpfade-hidden").addClass("wpfade-shown");
            } else {
                $link.removeClass("wpfade-shown").addClass("wpfade-hidden");
            }
        }
        $link.attr("aria-expanded", expanded ? "true" : "false");
    }

    /**
     * @param {JQuery|Element} $fade
     */
    function expandFadeBlock($fade) {
        $fade = $($fade);
        if (fadeHasBootstrapCollapse($fade)) {
            const $collapse = $fade.find(".collapse").first();
            if (typeof bootstrap !== "undefined" && bootstrap.Collapse) {
                try {
                    bootstrap.Collapse.getOrCreateInstance($collapse[0], { toggle: false }).show();
                } catch (e) {
                    $collapse.addClass("show").css("height", "");
                }
            } else {
                $collapse.addClass("show").css("height", "");
            }
            syncFadeUiFromExpanded($fade, true);
            return;
        }
        const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
        $body.stop(true, true);
        $body.show(0);
        $body.css("display", "block");
        syncFadeUiFromExpanded($fade, true);
    }

    function collapseFadeBlock($fade) {
        $fade = $($fade);
        if (fadeHasBootstrapCollapse($fade)) {
            const $collapse = $fade.find(".collapse").first();
            if (typeof bootstrap !== "undefined" && bootstrap.Collapse) {
                try {
                    bootstrap.Collapse.getOrCreateInstance($collapse[0], { toggle: false }).hide();
                } catch (e) {
                    $collapse.removeClass("show").css("height", "");
                }
            } else {
                $collapse.removeClass("show").css("height", "");
            }
            syncFadeUiFromExpanded($fade, false);
            return;
        }
        const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
        $body.stop(true, true);
        $body.hide(0);
        syncFadeUiFromExpanded($fade, false);
    }

    function snapshotFadeState() {
        const state = [];
        $(".wikiplugin-fade").each(function () {
            const $fade = $(this);
            const id = $fade.attr("data-fade-id") || "";
            const $collapse = $fade.find(".collapse").first();
            if ($collapse.length) {
                state.push({ type: "bs", id: id, wasShown: $collapse.hasClass("show") });
            } else {
                const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
                const visible = $body.length && $body.is(":visible");
                state.push({ type: "legacy", id: id, wasShown: visible });
            }
        });
        return state;
    }

    function restoreFadeState(state) {
        if (!state || !state.length) {
            return;
        }
        state.forEach(function (entry) {
            const $fade = $(".wikiplugin-fade")
                .filter(function () {
                    return $(this).attr("data-fade-id") === entry.id;
                })
                .first();
            if (!$fade.length) {
                return;
            }
            if (entry.type === "bs") {
                const el = $fade.find(".collapse").get(0);
                if (!el) {
                    return;
                }
                if (entry.wasShown) {
                    $(el).addClass("show").css("height", "");
                    syncFadeUiFromExpanded($fade, true);
                } else {
                    $(el).removeClass("show").css("height", "");
                    syncFadeUiFromExpanded($fade, false);
                }
            } else {
                const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
                if (entry.wasShown) {
                    $body.show(0);
                } else {
                    $body.hide(0);
                }
                syncFadeUiFromExpanded($fade, entry.wasShown);
            }
        });
    }

    function expandAllForPrint() {
        $(".wikiplugin-fade").each(function () {
            expandFadeBlock(this);
        });
    }

    function syncInitialOpenLegacy() {
        $(".wikiplugin-fade--initial-open").each(function () {
            const $fade = $(this);
            if (fadeHasBootstrapCollapse($fade)) {
                return;
            }
            const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
            if (!$body.length) {
                return;
            }
            $body.show(0);
            $body.css("display", "block");
            syncFadeUiFromExpanded($fade, true);
        });
    }

    /**
     * Build Bootstrap Collapse instances so DOM .show matches plugin internal state before first toggle.
     */
    function initBootstrapFadeCollapses() {
        if (typeof bootstrap === "undefined" || !bootstrap.Collapse) {
            return;
        }
        $(".wikiplugin-fade .collapse").each(function () {
            try {
                bootstrap.Collapse.getOrCreateInstance(this, { toggle: false });
            } catch (e) {
                // ignore invalid nodes
            }
        });
        $(".wikiplugin-fade").each(function () {
            const $fade = $(this);
            if (fadeHasBootstrapCollapse($fade)) {
                syncFadeUiFromExpanded($fade, isFadeExpanded($fade));
            }
        });
    }

    /**
     * Align legacy toggle link classes and aria-expanded with actual body visibility on load.
     */
    function syncLegacyFadeVisibilityOnReady() {
        $(".wikiplugin-fade").each(function () {
            const $fade = $(this);
            if (fadeHasBootstrapCollapse($fade)) {
                return;
            }
            const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
            const $link = $fade.find(".wpfade-legacy-toggle").first();
            if (!$body.length || !$link.length) {
                return;
            }
            syncLegacyLinkIconClasses($link, $body.is(":visible"));
        });
    }

    function expandAll(group) {
        getFadeSet(group).each(function () {
            expandFadeBlock(this);
        });
    }

    function collapseAll(group) {
        getFadeSet(group).each(function () {
            collapseFadeBlock(this);
        });
    }

    /**
     * If any FADE in the set is collapsed, expand all; otherwise collapse all.
     */
    function toggleAll(group) {
        const $set = getFadeSet(group);
        if (!$set.length) {
            return;
        }
        let anyCollapsed = false;
        $set.each(function () {
            if (!isFadeExpanded($(this))) {
                anyCollapsed = true;
                return false;
            }
        });
        if (anyCollapsed) {
            expandAll(group);
        } else {
            collapseAll(group);
        }
    }

    $(function () {
        $(document).on("show.bs.collapse", ".wikiplugin-fade .collapse", function (e) {
            const $fade = $(e.target).closest(".wikiplugin-fade");
            syncFadeUiFromExpanded($fade, true);
        });
        $(document).on("hide.bs.collapse", ".wikiplugin-fade .collapse", function (e) {
            const $fade = $(e.target).closest(".wikiplugin-fade");
            syncFadeUiFromExpanded($fade, false);
        });

        $(document).on("click", ".wikiplugin-fade .wpfade-legacy-toggle", function (e) {
            e.preventDefault();
            const $link = $(this);
            const $fade = $link.closest(".wikiplugin-fade");
            const $body = $fade.find(".wpfade-div-plain, .wpfade-div-icon").first();
            const speeds = {
                show: $fade.attr("data-fade-show-speed") || "400",
                hide: $fade.attr("data-fade-hide-speed") || "400",
            };
            legacyToggle($link, $body, speeds);
        });
        $(document).on("click", "[data-fade-action]", function (e) {
            const btn = e.target.closest("[data-fade-action]");
            if (!btn) {
                return;
            }

            const action = (btn.getAttribute("data-fade-action") || "").trim();
            const group = (btn.getAttribute("data-fade-group") || "").trim();
            if (!group) {
                return;
            }

            switch (action) {
                case "toggleAll":
                    e.preventDefault();
                    toggleAll(group);
                    break;
                case "openAll":
                    e.preventDefault();
                    expandAll(group);
                    break;
                case "closeAll":
                    e.preventDefault();
                    collapseAll(group);
                    break;
                default:
                    break;
            }
        });

        window.addEventListener("beforeprint", function () {
            printState = snapshotFadeState();
            expandAllForPrint();
        });
        window.addEventListener("afterprint", function () {
            if (printState) {
                restoreFadeState(printState);
            }
            printState = null;
        });

        syncInitialOpenLegacy();
        syncLegacyFadeVisibilityOnReady();
        initBootstrapFadeCollapses();
    });
})(jQuery);
