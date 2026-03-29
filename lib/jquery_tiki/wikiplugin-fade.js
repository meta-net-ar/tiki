/**
 * Wiki plugin FADE: Bootstrap chevron, legacy jQuery toggles, print snapshot, mobile expand.
 */
(function ($) {
    'use strict';

    const MOBILE_MQ = '(max-width: 767px)';
    let printState = null;

    function readMobilePrefFromDom() {
        const $el = $('.wikiplugin-fade').first();
        if (!$el.length) {
            return false;
        }
        return $el.attr('data-fade-mobile-pref') === 'y';
    }

    function setChevronExpanded($fade, expanded) {
        const $icon = $fade.find('.card-header .wikiplugin-fade__chevron').first();
        if (!$icon.length) {
            return;
        }
        $icon.toggleClass('fa-chevron-down', !expanded).toggleClass('fa-chevron-up', expanded);
    }

    function legacyToggle($link, $body, speeds) {
        if ($body.is(':hidden')) {
            $body.show('blind', {}, speeds.show);
            $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
        } else {
            $body.hide('blind', {}, speeds.hide);
            $link.removeClass('wpfade-shown').addClass('wpfade-hidden');
        }
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
                    setChevronExpanded($fade, true);
                } else {
                    $(el).removeClass('show').css('height', '');
                    setChevronExpanded($fade, false);
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
            }
        });
    }

    function expandAllForPrint() {
        $('.wikiplugin-fade').each(function () {
            const $fade = $(this);
            const $collapse = $fade.find('.collapse').first();
            if ($collapse.length) {
                $collapse.addClass('show').css('height', '');
                setChevronExpanded($fade, true);
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const $link = $fade.find('.wpfade-legacy-toggle').first();
            $body.show(0);
            $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
        });
    }

    function syncInitialOpenLegacy() {
        $('.wikiplugin-fade--initial-open').each(function () {
            const $fade = $(this);
            if ($fade.find('.collapse').first().length) {
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
            const $collapse = $fade.find('.collapse').first();
            if ($collapse.length) {
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
                setChevronExpanded($fade, true);
                return;
            }
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const $link = $fade.find('.wpfade-legacy-toggle').first();
            $body.show(0);
            $link.removeClass('wpfade-hidden').addClass('wpfade-shown');
        });
    }

    $(function () {
        $(document).on('show.bs.collapse', '.wikiplugin-fade .collapse', function (e) {
            const $fade = $(e.target).closest('.wikiplugin-fade');
            setChevronExpanded($fade, true);
        });
        $(document).on('hide.bs.collapse', '.wikiplugin-fade .collapse', function (e) {
            const $fade = $(e.target).closest('.wikiplugin-fade');
            setChevronExpanded($fade, false);
        });

        $(document).on('click', '.wikiplugin-fade .wpfade-legacy-toggle', function (e) {
            e.preventDefault();
            const $link = $(this);
            const $fade = $link.closest('.wikiplugin-fade');
            const $body = $fade.find('.wpfade-div-plain, .wpfade-div-icon').first();
            const speeds = {
                show: $fade.attr('data-fade-show-speed') || '400',
                hide: $fade.attr('data-fade-hide-speed') || '400'
            };
            legacyToggle($link, $body, speeds);
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
        applyMobileExpand();
        $(window).on('resize', function () {
            applyMobileExpand();
            syncInitialOpenLegacy();
        });
    });
}(jQuery));