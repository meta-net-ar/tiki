/**
 * Wiki plugin FADE: Bootstrap collapse, print snapshot, mobile expand.
 */
(function ($) {
    'use strict';

    const MOBILE_MQ = '(max-width: 767px)';
    let printState = null;

    function fadeHasBootstrapCollapse($fade) {
        return $fade.find('.collapse').first().length > 0;
    }

    function readMobilePrefFromDom() {
        const $el = $('.wikiplugin-fade').first();
        if (!$el.length) {
            return false;
        }
        return $el.attr('data-fade-mobile-pref') === 'y';
    }

    function isBootstrapOnly($fade) {
        return String($fade.attr('data-has-summary') || '0') === '1';
    }

    function legacyToggle($link, $body, speeds) {
        const $fade = $link.closest('.wikiplugin-fade');
        if (isBootstrapOnly($fade)) {
            return;
        }
        $body.stop(true, true);
        if ($body.is(':hidden')) {
            $body.show('blind', {}, speeds.show, function () {
                syncFadeUiFromExpanded($fade, isFadeExpanded($fade));
            });
            $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
        } else {
            $body.hide('blind', {}, speeds.hide, function () {
                syncFadeUiFromExpanded($fade, isFadeExpanded($fade));
            });
            $link.removeClass('wpfade-shown').addClass('wpfade-hidden');
        }
    }

    /**
     * @param {string|undefined|null} group Empty string or null/undefined = all .wikiplugin-fade on the page.
     */
    function getFadeSet(group) {
        if (group === undefined || group === null || group === '') {
            return $('.wikiplugin-fade');
        }
        const g = String(group);
        return $('.wikiplugin-fade').filter(function () {
            return ($(this).attr('data-fade-group') || '') === g;
        });
    }

    function isFadeExpanded($fade) {
        const $collapse = $fade.find('.collapse').first();
        if (fadeHasBootstrapCollapse($fade)) {
            return $collapse.hasClass('show');
        }
        const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
        return $body.length > 0 && $body.is(':visible');
    }

    function setBootstrapTriggerAria($fade, expanded) {
        const $a = $fade.find('.card-header a[data-bs-toggle="collapse"]').first();
        if ($a.length) {
            $a.attr('aria-expanded', expanded ? 'true' : 'false');
        }
    }

    /**
     * Sync UI affordances (Bootstrap trigger ARIA or legacy link classes) to expanded state.
     * @param {JQuery} $fade
     * @param {boolean} expanded
     */
    function syncFadeUiFromExpanded($fade, expanded) {
        if (fadeHasBootstrapCollapse($fade)) {
            setBootstrapTriggerAria($fade, expanded);
        } else {
            syncLegacyLinkIconClasses($fade.find('.wpfade-legacy-toggle').first(), expanded);
        }
    }

    function syncLegacyLinkIconClasses($link, expanded) {
        if (!$link.length) {
            return;
        }
        if ($link.hasClass('wpfade-hidden') || $link.hasClass('wpfade-shown')) {
            if (expanded) {
                $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
            } else {
                $link.removeClass('wpfade-shown').addClass('wpfade-hidden');
            }
        }
        $link.attr('aria-expanded', expanded ? 'true' : 'false');
    }

    /**
     * @param {JQuery|Element} $fade
     */
    function expandFadeBlock($fade) {
        $fade = $($fade);
        if (fadeHasBootstrapCollapse($fade)) {
            const $collapse = $fade.find('.collapse').first();
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                try {
                    bootstrap.Collapse.getOrCreateInstance($collapse[0], { toggle: false }).show();
                } catch (e) {
                    $collapse.addClass('show').css('height', '');
                }
            } else {
                $collapse.addClass('show').css('height', '');
            }
            syncFadeUiFromExpanded($fade, true);
            return;
        }
        const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
        $body.stop(true, true);
        $body.show(0);
        $body.css('display', 'block');
        syncFadeUiFromExpanded($fade, true);
    }

    function collapseFadeBlock($fade) {
        $fade = $($fade);
        if (fadeHasBootstrapCollapse($fade)) {
            const $collapse = $fade.find('.collapse').first();
            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                try {
                    bootstrap.Collapse.getOrCreateInstance($collapse[0], { toggle: false }).hide();
                } catch (e) {
                    $collapse.removeClass('show').css('height', '');
                }
            } else {
                $collapse.removeClass('show').css('height', '');
            }
            syncFadeUiFromExpanded($fade, false);
            return;
        }
        const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
        $body.stop(true, true);
        $body.hide(0);
        syncFadeUiFromExpanded($fade, false);
    }

    function snapshotFadeState() {
        const state = [];
        $('.wikiplugin-fade').each(function () {
            const $fade = $(this);
            const id = $fade.attr('data-fade-id') || '';
            const $collapse = $fade.find('.collapse').first();
            if ($collapse.length) {
                state.push({ type: 'bs', id: id, wasShown: $collapse.hasClass('show') });
            } else {
                const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
                const visible = $body.length && $body.is(':visible');
                state.push({ type: 'legacy', id: id, wasShown: visible });
            }
        });
        return state;
    }

    function restoreFadeState(state) {
        if (!state || !state.length) {
            return;
        }
        state.forEach(function (entry) {
            const $fade = $('.wikiplugin-fade').filter(function () {
                return $(this).attr('data-fade-id') === entry.id;
            }).first();
            if (!$fade.length) {
                return;
            }
            if (entry.type === 'bs') {
                const el = $fade.find('.collapse').get(0);
                if (!el) {
                    return;
                }
                if (entry.wasShown) {
                    $(el).addClass('show').css('height', '');
                    syncFadeUiFromExpanded($fade, true);
                } else {
                    $(el).removeClass('show').css('height', '');
                    syncFadeUiFromExpanded($fade, false);
                }
            } else {
                const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
                const $link = $fade.find('.wpfade-legacy-toggle').first();
                if (entry.wasShown) {
                    $body.show(0);
                    $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
                } else {
                    $body.hide(0);
                    $link.removeClass('wpfade-shown').addClass('wpfade-hidden');
                }
                syncFadeUiFromExpanded($fade, entry.wasShown);
            }
        });
    }

    function expandAllForPrint() {
        $('.wikiplugin-fade').each(function () {
            expandFadeBlock(this);
        });
    }

    function syncInitialOpenLegacy() {
        $('.wikiplugin-fade--initial-open').each(function () {
            const $fade = $(this);
            if (isBootstrapOnly($fade)) {
                return;
            }
            if (fadeHasBootstrapCollapse($fade)) {
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const $link = $fade.find('.wpfade-legacy-toggle').first();
            if (!$body.length) {
                return;
            }
            $body.show(0);
            $body.css('display', 'block');
            if ($link.length && $link.hasClass('wpfade-legacy-toggle')) {
                $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
            }
        });
    }

    function applyMobileExpand() {
        if (!readMobilePrefFromDom()) {
            $('html').removeClass('wikiplugin-fade-mobile-forced');
            return;
        }
        if (!window.matchMedia(MOBILE_MQ).matches) {
            $('html').removeClass('wikiplugin-fade-mobile-forced');
            return;
        }
        $('html').addClass('wikiplugin-fade-mobile-forced');
        $('.wikiplugin-fade').each(function () {
            const $fade = $(this);
            if (fadeHasBootstrapCollapse($fade)) {
                const $collapse = $fade.find('.collapse').first();
                if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                    try {
                        const instance = bootstrap.Collapse.getOrCreateInstance($collapse[0], { toggle: false });
                        instance.show();
                    } catch (e) {
                        $collapse.addClass('show').css('height', '');
                    }
                } else {
                    $collapse.addClass('show').css('height', '');
                }
                syncFadeUiFromExpanded($fade, true);
                return;
            }
            if (isBootstrapOnly($fade)) {
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const $link = $fade.find('.wpfade-legacy-toggle').first();
            $body.show(0);
            $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
            syncFadeUiFromExpanded($fade, true);
        });
    }

    /**
     * Build Bootstrap Collapse instances so DOM .show matches plugin internal state before first toggle.
     */
    function initBootstrapFadeCollapses() {
        if (typeof bootstrap === 'undefined' || !bootstrap.Collapse) {
            return;
        }
        $('.wikiplugin-fade .collapse').each(function () {
            try {
                bootstrap.Collapse.getOrCreateInstance(this, { toggle: false });
            } catch (e) {
                // ignore invalid nodes
            }
        });
        $('.wikiplugin-fade').each(function () {
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
        $('.wikiplugin-fade').each(function () {
            const $fade = $(this);
            if (isBootstrapOnly($fade)) {
                return;
            }
            if (fadeHasBootstrapCollapse($fade)) {
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const $link = $fade.find('.wpfade-legacy-toggle').first();
            if (!$body.length || !$link.length) {
                return;
            }
            syncLegacyLinkIconClasses($link, $body.is(':visible'));
        });
    }

    window.tikiFadeExpandAll = function (group) {
        getFadeSet(group).each(function () {
            expandFadeBlock(this);
        });
    };

    window.tikiFadeCollapseAll = function (group) {
        getFadeSet(group).each(function () {
            collapseFadeBlock(this);
        });
    };
    window.tikiFadeOpenAll = window.tikiFadeExpandAll;
    window.tikiFadeCloseAll = window.tikiFadeCollapseAll;

    /**
     * If any FADE in the set is collapsed, expand all; otherwise collapse all.
     */
    window.tikiFadeToggleAll = function (group) {
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
            window.tikiFadeExpandAll(group);
        } else {
            window.tikiFadeCollapseAll(group);
        }
    };

    $(function () {
        $(document).on('show.bs.collapse', '.wikiplugin-fade .collapse', function (e) {
            const $fade = $(e.target).closest('.wikiplugin-fade');
            syncFadeUiFromExpanded($fade, true);
        });
        $(document).on('hide.bs.collapse', '.wikiplugin-fade .collapse', function (e) {
            const $fade = $(e.target).closest('.wikiplugin-fade');
            syncFadeUiFromExpanded($fade, false);
        });

        $(document).on('click', '.wikiplugin-fade .wpfade-legacy-toggle', function (e) {
            e.preventDefault();
            const $link = $(this);
            const $fade = $link.closest('.wikiplugin-fade');
            if (isBootstrapOnly($fade)) {
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const speeds = {
                show: $fade.attr('data-fade-show-speed') || '400',
                hide: $fade.attr('data-fade-hide-speed') || '400'
            };
            legacyToggle($link, $body, speeds);
        });
        $(document).on('click', '[data-fade-action]', function (e) {
            const btn = e.target.closest('[data-fade-action]');
            if (!btn) {
                return;
            }

            const action = (btn.getAttribute('data-fade-action') || '').trim();
            const group = (btn.getAttribute('data-fade-group') || '').trim();
            if (!group) {
                return;
            }

            switch (action) {
            case 'toggleAll':
                e.preventDefault();
                window.tikiFadeToggleAll(group);
                break;
            case 'openAll':
                e.preventDefault();
                window.tikiFadeExpandAll(group);
                break;
            case 'closeAll':
                e.preventDefault();
                window.tikiFadeCollapseAll(group);
                break;
            default:
                break;
            }
        });

        window.addEventListener('beforeprint', function () {
            printState = snapshotFadeState();
            expandAllForPrint();
        });
        window.addEventListener('afterprint', function () {
            if (printState) {
                restoreFadeState(printState);
            }
            printState = null;
        });

        syncInitialOpenLegacy();
        syncLegacyFadeVisibilityOnReady();
        initBootstrapFadeCollapses();
        applyMobileExpand();
        $(window).on('resize', function () {
            applyMobileExpand();
            syncInitialOpenLegacy();
            syncLegacyFadeVisibilityOnReady();
        });
    });
}(jQuery));
