(function ($) {
    "use strict";

    window.tikiInitSwiperTeleprompter = function (opts) {
        var $container = $("#" + opts.containerId);
        var $track = $("#" + opts.trackId);
        var $content = $("#" + opts.contentId);
        var $timer = $("#" + opts.timerId);

        if (!($container.length && $track.length && $content.length)) {
            return;
        }

        var speed = Number(opts.speed) || 60;
        var acceleration = Number(opts.acceleration) || 3;
        var fontScale = Number(opts.fontScale) || 120;
        var showTimer = !!opts.showTimer;
        var focusMode = opts.focusMode || "top";
        var pauseOnHover = !!opts.pauseOnHover;

        var minSpeed = Number(opts.minSpeed) || 10;
        var maxSpeed = Number(opts.maxSpeed) || 500;
        var speedStep = Number(opts.speedStep) || 10;
        var minFontScale = Number(opts.minFontScale) || 70;
        var maxFontScale = Number(opts.maxFontScale) || 300;
        var fontScaleStep = Number(opts.fontScaleStep) || 5;
        var resizeDebounceMs = Number(opts.resizeDebounceMs) || 120;

        var $focusOverlay = $container.find(".teleprompter-focus-overlay");
        var $focusMaskTop = $container.find(".tp-focus-mask-top");
        var $focusMaskBottom = $container.find(".tp-focus-mask-bottom");
        var $focusGuide = $container.find(".tp-focus-guide");
        var $leadSpacer = $content.find(".teleprompter-spacer-before");
        var $trailSpacer = $content.find(".teleprompter-spacer-after");

        var pausedByUser = false;
        var pausedByHover = false;
        var reachedEnd = false;

        var offset = 0;
        var lastTs = null;
        var maxOffset = 0;
        var lastSpeedInputTs = 0;
        var elapsedMs = 0;
        var lastTimerText = "";
        var resizeDebounceId = null;

        var isPaused = function () {
            return pausedByUser || pausedByHover;
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
            if (!showTimer || !$timer.length) {
                return;
            }

            var timerText = formatElapsed(elapsedMs);
            if (timerText !== lastTimerText) {
                $timer.text(timerText);
                lastTimerText = timerText;
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
            maxOffset = Math.max(contentHeight - containerHeight, 0);

            if (offset > maxOffset) {
                offset = maxOffset;
                $track.css("transform", "translate3d(0,-" + offset + "px,0)");
            }

            reachedEnd = maxOffset > 0 && offset >= maxOffset;
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
            if (focusMode === "none" || !$focusOverlay.length) {
                return;
            }

            var containerHeight = $container.innerHeight();
            if (!containerHeight || containerHeight <= 0) {
                return;
            }

            var lineHeight = getSampleLineHeight();
            var focusHeight = Math.min(containerHeight * 0.30, Math.max(lineHeight + 8, lineHeight * 1.35));
            var start = 0;

            if (focusMode === "top") {
                start = 0;
            } else if (focusMode === "bottom") {
                start = Math.max(0, containerHeight - focusHeight);
            } else {
                start = Math.max(0, (containerHeight - focusHeight) / 2);
            }

            var bottomHeight = Math.max(0, containerHeight - (start + focusHeight));
            $focusMaskTop.css("height", start + "px");
            $focusMaskBottom.css("height", bottomHeight + "px");
            $focusGuide.css("top", start + focusHeight / 2 + "px");
        };

        var refreshFontScale = function () {
            var scaleFactor = Math.max(minFontScale, Math.min(maxFontScale, fontScale)) / 100;
            $container.css("--teleprompter-font-scale", String(scaleFactor));
            refreshSpacers();
            refreshLoopHeight();
            refreshFocusOverlay();
        };

        // Apply acceleration-based speed changes while preserving smooth direction reversals.
        var applySpeedStep = function (targetDirection, stepCount) {
            targetDirection = targetDirection >= 0 ? 1 : -1;
            stepCount = Math.max(1, stepCount || 1);

            if (maxOffset > 0 && pausedByUser) {
                if (offset >= maxOffset && targetDirection < 0) {
                    speed = -Math.max(minSpeed, Math.min(maxSpeed, Math.abs(speed)));
                    pausedByUser = false;
                    reachedEnd = false;
                    return;
                }

                if (offset <= 0 && targetDirection > 0) {
                    speed = Math.max(minSpeed, Math.min(maxSpeed, Math.abs(speed)));
                    pausedByUser = false;
                    return;
                }
            }

            var now = Date.now();
            var elapsed = lastSpeedInputTs ? now - lastSpeedInputTs : 1000;
            lastSpeedInputTs = now;

            var accelerationFactor = 1;
            if (elapsed < 500) {
                accelerationFactor = 1 + (500 - elapsed) / 500 * Math.max(0, acceleration - 1);
            }

            var delta = speedStep * stepCount * accelerationFactor;
            var currentDirection = speed >= 0 ? 1 : -1;
            var currentMagnitude = Math.max(minSpeed, Math.min(maxSpeed, Math.abs(speed)));

            if (targetDirection === currentDirection) {
                currentMagnitude = Math.min(maxSpeed, currentMagnitude + delta);
                speed = currentDirection * currentMagnitude;
            } else {
                var reducedMagnitude = currentMagnitude - delta;
                if (reducedMagnitude > minSpeed) {
                    speed = currentDirection * reducedMagnitude;
                } else {
                    var reverseMagnitude = Math.min(maxSpeed, minSpeed + (minSpeed - reducedMagnitude));
                    speed = targetDirection * reverseMagnitude;
                }
            }

            if (pausedByUser && maxOffset > 0 && ((offset >= maxOffset && targetDirection < 0) || (offset <= 0 && targetDirection > 0))) {
                pausedByUser = false;
            }

            if (targetDirection < 0) {
                reachedEnd = false;
            }
        };

        var applyFontScaleStep = function (direction, stepCount) {
            stepCount = Math.max(1, stepCount || 1);
            fontScale = Math.max(minFontScale, Math.min(maxFontScale, fontScale + direction * fontScaleStep * stepCount));
            refreshFontScale();
        };

        var restartFromBeginning = function () {
            offset = 0;
            elapsedMs = 0;
            lastTs = null;
            reachedEnd = false;
            $track.css("transform", "translate3d(0,-0px,0)");
            updateTimerDisplay();
        };

        var animate = function (ts) {
            if (lastTs === null) {
                lastTs = ts;
            }

            var dt = (ts - lastTs) / 1000;
            lastTs = ts;

            if (!isPaused() && maxOffset > 0) {
                offset += speed * dt;
                elapsedMs += dt * 1000;

                if (offset >= maxOffset) {
                    offset = maxOffset;
                    reachedEnd = true;
                    if (speed > 0) {
                        pausedByUser = true;
                    }
                } else if (offset <= 0) {
                    offset = 0;
                    if (speed < 0) {
                        pausedByUser = true;
                    }
                    reachedEnd = false;
                } else {
                    reachedEnd = false;
                }

                $track.css("transform", "translate3d(0,-" + offset + "px,0)");
            }

            updateTimerDisplay();
            window.requestAnimationFrame(animate);
        };

        refreshFontScale();
        updateTimerDisplay();

        $(window).on("resize", function () {
            if (resizeDebounceId !== null) {
                window.clearTimeout(resizeDebounceId);
            }

            resizeDebounceId = window.setTimeout(function () {
                refreshSpacers();
                refreshLoopHeight();
                refreshFocusOverlay();
                resizeDebounceId = null;
            }, resizeDebounceMs);
        });

        window.requestAnimationFrame(animate);

        if (pauseOnHover) {
            $container.on("mouseenter", function () {
                pausedByHover = true;
            }).on("mouseleave", function () {
                pausedByHover = false;
            });
        }

        $container.on("keydown", function (e) {
            var target = e.target;
            var tagName = target && target.tagName ? target.tagName.toLowerCase() : "";
            if (tagName === "input" || tagName === "textarea" || (target && target.isContentEditable)) {
                return;
            }

            var key = e.key || "";
            var lowerKey = key.toLowerCase();

            if (key === " " || key === "Spacebar") {
                if (reachedEnd) {
                    restartFromBeginning();
                    pausedByUser = false;
                } else {
                    pausedByUser = !pausedByUser;
                }
                e.preventDefault();
                return;
            }

            if (key === "Backspace") {
                restartFromBeginning();
                pausedByUser = false;
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
        }).on("wheel", function (e) {
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
