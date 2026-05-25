(function ($) {
    "use strict";

    var clamp = function (value, min, max) {
        return Math.max(min, Math.min(max, value));
    };

    var toNumber = function (value, fallback) {
        var parsed = Number(value);
        return isFinite(parsed) ? parsed : fallback;
    };

    window.tikiInitTeleprompter = function (opts) {
        var $container = $("#" + opts.containerId);
        var $track = $("#" + opts.trackId);
        var $content = $("#" + opts.contentId);
        var $timer = $("#" + opts.timerId);

        if (!($container.length && $track.length && $content.length)) {
            return;
        }

        var initVersion = (toNumber($container.data("tikiTeleprompterInitVersion"), 0) || 0) + 1;
        var eventNamespace = ".tikiTeleprompter" + String(opts.containerId).replace(/\W/g, "");
        $container.data("tikiTeleprompterInitVersion", initVersion);
        $(window).off(eventNamespace);

        var settings = {
            acceleration: toNumber(opts.acceleration, 3),
            focusMode: opts.focusMode || "top",
            fontScaleStep: toNumber(opts.fontScaleStep, 5),
            maxFontScale: toNumber(opts.maxFontScale, 300),
            maxSpeed: toNumber(opts.maxSpeed, 500),
            minFontScale: toNumber(opts.minFontScale, 70),
            minSpeed: toNumber(opts.minSpeed, 10),
            pauseLabel: opts.pauseLabel || "Pause",
            resizeDebounceMs: toNumber(opts.resizeDebounceMs, 120),
            resumeLabel: opts.resumeLabel || "Resume",
            reverseScroll: !!opts.reverseScroll,
            showControls: typeof opts.showControls === "undefined" ? true : !!opts.showControls,
            showTimer: !!opts.showTimer,
            speedStep: toNumber(opts.speedStep, 10)
        };

        var state = {
            elapsedMs: 0,
            fontScale: clamp(toNumber(opts.fontScale, 180), settings.minFontScale, settings.maxFontScale),
            lastSpeedInputTs: 0,
            lastTimerText: "",
            lastTs: null,
            maxOffset: 0,
            offset: 0,
            pausedByUser: false,
            reachedEnd: false,
            resizeDebounceId: null,
            speed: clamp(Math.abs(toNumber(opts.speed, 35)), settings.minSpeed, settings.maxSpeed)
        };
        if (settings.reverseScroll) {
            state.speed = -state.speed;
        }

        var $focusOverlay = $container.find(".teleprompter-focus-overlay");
        var $focusMaskTop = $container.find(".tp-focus-mask-top");
        var $focusMaskBottom = $container.find(".tp-focus-mask-bottom");
        var $focusGuide = $container.find(".tp-focus-guide");
        var $leadSpacer = $content.find(".teleprompter-spacer-before");
        var $trailSpacer = $content.find(".teleprompter-spacer-after");
        var $controls = $container.find(".teleprompter-controls");
        var $toggleControl = $controls.find(".teleprompter-btn-toggle");
        var $restartControl = $controls.find(".teleprompter-btn-restart");
        var $progress = $container.find(".teleprompter-progress");
        var $progressThumb = $progress.find(".teleprompter-progress-thumb");

        var getStartOffset = function () {
            return settings.reverseScroll && state.maxOffset > 0 ? state.maxOffset : 0;
        };

        var updateToggleControl = function () {
            if (!$toggleControl.length) {
                return;
            }

            $toggleControl
                .text(state.pausedByUser ? settings.resumeLabel : settings.pauseLabel)
                .attr("aria-pressed", state.pausedByUser ? "true" : "false");
        };

        var setPaused = function (paused) {
            state.pausedByUser = !!paused;
            updateToggleControl();
        };

        var updateProgressIndicator = function () {
            if (!$progress.length || !$progressThumb.length) {
                return;
            }

            if (state.maxOffset <= 0) {
                $progress.addClass("teleprompter-progress-disabled");
                return;
            }

            var trackHeight = Math.max($progress.innerHeight(), 1);
            var containerHeight = Math.max($container.innerHeight(), 1);
            var contentHeight = Math.max(state.maxOffset + containerHeight, 1);
            var thumbHeight = clamp(trackHeight * (containerHeight / contentHeight), 24, trackHeight);
            var progress = state.offset / state.maxOffset;

            if (settings.reverseScroll) {
                progress = 1 - progress;
            }

            $progress.removeClass("teleprompter-progress-disabled");
            $progressThumb.css({
                height: thumbHeight + "px",
                transform: "translate3d(0," + ((trackHeight - thumbHeight) * clamp(progress, 0, 1)) + "px,0)"
            });
        };

        var updateTrackTransform = function () {
            $track.css("transform", "translate3d(0,-" + state.offset + "px,0)");
            updateProgressIndicator();
        };

        var formatElapsed = function (totalMs) {
            var totalSeconds = Math.floor(totalMs / 1000);
            var hours = Math.floor(totalSeconds / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            var pad = function (value) {
                return value < 10 ? "0" + value : String(value);
            };

            if (hours > 0) {
                return pad(hours) + ":" + pad(minutes) + ":" + pad(seconds);
            }

            return pad(minutes) + ":" + pad(seconds);
        };

        var updateTimerDisplay = function () {
            if (!settings.showTimer || !$timer.length) {
                return;
            }

            var timerText = formatElapsed(state.elapsedMs);
            if (timerText !== state.lastTimerText) {
                $timer.text(timerText);
                state.lastTimerText = timerText;
            }
        };

        var getSampleLineHeight = function () {
            var $sample = $content.find(".teleprompter-block").first();
            if (!$sample.length) {
                $sample = $content;
            }

            var lineHeight = parseFloat($sample.css("line-height"));
            if (!isFinite(lineHeight)) {
                var fontSize = parseFloat($sample.css("font-size")) || 24;
                lineHeight = fontSize * 1.4;
            }

            return Math.max(12, lineHeight);
        };

        var refreshLoopHeight = function () {
            var contentHeight = Math.max($content.outerHeight(true), 1);
            var containerHeight = Math.max($container.innerHeight(), 1);
            state.maxOffset = Math.max(contentHeight - containerHeight, 0);

            if (state.offset > state.maxOffset) {
                state.offset = state.maxOffset;
                updateTrackTransform();
            }

            state.reachedEnd = state.maxOffset > 0 && (settings.reverseScroll ? state.offset <= 0 : state.offset >= state.maxOffset);
            updateProgressIndicator();
        };

        var refreshSpacers = function () {
            var containerHeight = Math.max($container.innerHeight(), 1);
            var lineHeight = getSampleLineHeight();
            var spacerHeight = Math.max(containerHeight, lineHeight * 4);

            if ($leadSpacer.length) {
                $leadSpacer.css("height", spacerHeight + "px");
            }
            if ($trailSpacer.length) {
                $trailSpacer.css("height", spacerHeight + "px");
            }
        };

        var refreshFocusOverlay = function () {
            if (settings.focusMode === "none" || !$focusOverlay.length) {
                return;
            }

            var containerHeight = $container.innerHeight();
            if (!containerHeight || containerHeight <= 0) {
                return;
            }

            var lineHeight = getSampleLineHeight();
            var focusHeight = Math.min(containerHeight * 0.30, Math.max(lineHeight + 8, lineHeight * 1.35));
            var start = 0;

            if (settings.focusMode === "top") {
                start = 0;
            } else if (settings.focusMode === "bottom") {
                start = Math.max(0, containerHeight - focusHeight);
            } else {
                start = Math.max(0, (containerHeight - focusHeight) / 2);
            }

            var bottomHeight = Math.max(0, containerHeight - (start + focusHeight));
            $focusMaskTop.css("height", start + "px");
            $focusMaskBottom.css("height", bottomHeight + "px");
            $focusGuide.css("top", start + focusHeight / 2 + "px");
        };

        var refreshLayout = function () {
            refreshSpacers();
            refreshLoopHeight();
            refreshFocusOverlay();
        };

        var refreshFontScale = function () {
            var scaleFactor = state.fontScale / 100;
            $container.css("--teleprompter-font-scale", String(scaleFactor));
            refreshLayout();
        };

        var restartFromBeginning = function () {
            refreshLayout();
            state.offset = getStartOffset();
            state.elapsedMs = 0;
            state.lastTs = null;
            state.reachedEnd = false;
            updateTrackTransform();
            updateTimerDisplay();
        };

        // Apply acceleration-based speed changes while preserving smooth direction reversals.
        var applySpeedStep = function (targetDirection, stepCount) {
            targetDirection = targetDirection >= 0 ? 1 : -1;
            stepCount = Math.max(1, stepCount || 1);

            if (state.maxOffset > 0 && state.pausedByUser) {
                if (state.offset >= state.maxOffset && targetDirection < 0) {
                    state.speed = -clamp(Math.abs(state.speed), settings.minSpeed, settings.maxSpeed);
                    setPaused(false);
                    state.reachedEnd = false;
                    return;
                }
                if (state.offset <= 0 && targetDirection > 0) {
                    state.speed = clamp(Math.abs(state.speed), settings.minSpeed, settings.maxSpeed);
                    setPaused(false);
                    return;
                }
            }

            var now = Date.now();
            var elapsed = state.lastSpeedInputTs ? now - state.lastSpeedInputTs : 1000;
            state.lastSpeedInputTs = now;

            var accelerationFactor = 1;
            if (elapsed < 500) {
                accelerationFactor = 1 + (500 - elapsed) / 500 * Math.max(0, settings.acceleration - 1);
            }

            var delta = settings.speedStep * stepCount * accelerationFactor;
            var currentDirection = state.speed >= 0 ? 1 : -1;
            var currentMagnitude = clamp(Math.abs(state.speed), settings.minSpeed, settings.maxSpeed);

            if (targetDirection === currentDirection) {
                currentMagnitude = Math.min(settings.maxSpeed, currentMagnitude + delta);
                state.speed = currentDirection * currentMagnitude;
            } else {
                var reducedMagnitude = currentMagnitude - delta;
                if (reducedMagnitude > settings.minSpeed) {
                    state.speed = currentDirection * reducedMagnitude;
                } else {
                    var reverseMagnitude = Math.min(settings.maxSpeed, settings.minSpeed + (settings.minSpeed - reducedMagnitude));
                    state.speed = targetDirection * reverseMagnitude;
                }
            }

            if (state.pausedByUser && state.maxOffset > 0 && ((state.offset >= state.maxOffset && targetDirection < 0) || (state.offset <= 0 && targetDirection > 0))) {
                setPaused(false);
            }

            if (targetDirection < 0) {
                state.reachedEnd = false;
            }
        };

        var applyFontScaleStep = function (direction, stepCount) {
            stepCount = Math.max(1, stepCount || 1);
            state.fontScale = clamp(state.fontScale + direction * settings.fontScaleStep * stepCount, settings.minFontScale, settings.maxFontScale);
            refreshFontScale();
        };

        var togglePauseOrRestart = function () {
            if (state.reachedEnd) {
                restartFromBeginning();
                setPaused(false);
                return;
            }
            setPaused(!state.pausedByUser);
        };

        var animate = function (ts) {
            if ($container.data("tikiTeleprompterInitVersion") !== initVersion) {
                return;
            }

            if (state.lastTs === null) {
                state.lastTs = ts;
            }

            var dt = (ts - state.lastTs) / 1000;
            state.lastTs = ts;

            if (!state.pausedByUser && state.maxOffset > 0) {
                state.offset += state.speed * dt;
                state.elapsedMs += dt * 1000;

                if (state.offset >= state.maxOffset) {
                    state.offset = state.maxOffset;
                    state.reachedEnd = !settings.reverseScroll;
                    if (state.speed > 0) {
                        setPaused(true);
                    }
                } else if (state.offset <= 0) {
                    state.offset = 0;
                    state.reachedEnd = settings.reverseScroll && state.speed < 0;
                    if (state.speed < 0) {
                        setPaused(true);
                    }
                } else {
                    state.reachedEnd = false;
                }

                updateTrackTransform();
            }

            updateTimerDisplay();
            window.requestAnimationFrame(animate);
        };

        refreshFontScale();
        if (settings.reverseScroll && state.maxOffset > 0) {
            state.offset = state.maxOffset;
            updateTrackTransform();
        }
        updateTimerDisplay();
        if (!settings.showControls && $controls.length) {
            $controls.addClass("teleprompter-controls-hidden");
        }
        updateToggleControl();

        $content.find("img").off(eventNamespace).on("load" + eventNamespace, function () {
            refreshLayout();
        }).each(function () {
            if (this.complete) {
                refreshLayout();
            }
        });

        $(window).on("resize" + eventNamespace, function () {
            if (state.resizeDebounceId !== null) {
                window.clearTimeout(state.resizeDebounceId);
            }

            state.resizeDebounceId = window.setTimeout(function () {
                refreshLayout();
                state.resizeDebounceId = null;
            }, settings.resizeDebounceMs);
        });

        window.requestAnimationFrame(animate);

        $toggleControl.off(eventNamespace).on("click" + eventNamespace, function (e) {
            e.preventDefault();
            togglePauseOrRestart();
            $container.trigger("focus");
        });

        $restartControl.off(eventNamespace).on("click" + eventNamespace, function (e) {
            e.preventDefault();
            restartFromBeginning();
            setPaused(false);
            $container.trigger("focus");
        });

        $container.off(eventNamespace).on("keydown" + eventNamespace, function (e) {
            var target = e.target;
            var tagName = target && target.tagName ? target.tagName.toLowerCase() : "";
            if (tagName === "input" || tagName === "textarea" || (target && target.isContentEditable)) {
                return;
            }

            var key = e.key || "";
            var lowerKey = key.toLowerCase();

            if (key === " " || key === "Spacebar") {
                togglePauseOrRestart();
                e.preventDefault();
                return;
            }

            if (key === "Backspace") {
                restartFromBeginning();
                setPaused(false);
                e.preventDefault();
                return;
            }

            if (key === "ArrowDown" || lowerKey === "s") {
                applySpeedStep(1, 1);
                e.preventDefault();
                return;
            }

            if (key === "ArrowUp" || lowerKey === "w") {
                applySpeedStep(-1, 1);
                e.preventDefault();
                return;
            }

            if (key === "ArrowRight" || lowerKey === "d") {
                applyFontScaleStep(1, 1);
                e.preventDefault();
                return;
            }

            if (key === "ArrowLeft" || lowerKey === "a") {
                applyFontScaleStep(-1, 1);
                e.preventDefault();
            }
        }).on("wheel" + eventNamespace, function (e) {
            var originalEvent = e.originalEvent || e;
            if (!originalEvent || typeof originalEvent.deltaY === "undefined" || originalEvent.deltaY === 0) {
                return;
            }

            var direction = originalEvent.deltaY > 0 ? -1 : 1;
            var stepCount = Math.max(1, Math.min(5, Math.round(Math.abs(originalEvent.deltaY) / 100)));
            applySpeedStep(direction, stepCount);
            e.preventDefault();
        });
    };
})(jQuery);
