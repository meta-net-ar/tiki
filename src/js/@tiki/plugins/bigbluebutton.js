function setupBBBMeetingInIframe(meetId) {
    const $ = jQuery;

    const formId = `bbbJoinForm_${meetId}`;
    const wrapperId = `bbbMeetingWrapper_${meetId}`;
    const joinBtnId = `bbbJoinButton_${meetId}`;
    const leaveBtnId = `bbbLeaveMeeting_${meetId}`;
    const fullBtnId = `bbbToggleFullPage_${meetId}`;
    const controlsId = `bbbMeetingControls_${meetId}`;
    const iframeId = `bbbMeetingFrame_${meetId}`;
    const returnToMeetingBtnId = `bbbReturnToMeeting_${meetId}`;
    const bbbRoomNotExistMessageID = `bbbRoomNotExistMessage_${meetId}`;
    let $form, $wrapper, $joinBtn, $controls, $leaveBtn, $fullBtn, $returnToMeetingBtn, $returnButton;

    document.addEventListener("DOMContentLoaded", () => {
        $form = $(`#${formId}`);
        $wrapper = $(`#${wrapperId}`);
        if (!$form.length) return;
        let meetingActive = false;
        let isFullPage = false;
        const iframeMode = $form.attr("data-iframe") === "1";

        const $meetingControls = $(
            '<div class="bbb-meeting-controls mb-3" id="' +
                controlsId +
                '" style="display: none; font-size: 12px;">' +
                '<button class="btn btn-danger btn-sm me-2" id="' +
                leaveBtnId +
                '" style="font-size:12px; padding:3px 8px;">' +
                '<i class="fas fa-door-open me-1"></i>Leave Meeting' +
                "</button>" +
                '<button class="btn btn-outline-secondary btn-sm" id="' +
                fullBtnId +
                '" style="font-size:12px; padding:3px 8px;">' +
                '<i class="fas fa-expand me-1"></i>Full Screen' +
                "</button>" +
                "</div>"
        );

        $wrapper.before($meetingControls);

        $controls = $(`#${controlsId}`);
        $leaveBtn = $(`#${leaveBtnId}`);
        $fullBtn = $(`#${fullBtnId}`);
        $returnToMeetingBtn = $(`#${returnToMeetingBtnId}`);

        $form.on("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = $form.attr("action");
            const $submitBtn = $(`#${joinBtnId}`);
            const originalText = $submitBtn.html();

            $submitBtn.prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-1"></i> Joining ...');

            fetch(action, {
                method: "POST",
                body: formData,
            })
                .then((response) => response.json())
                .then((data) => {
                    const url = data.joinUrl;
                    if (!iframeMode) {
                        // Default: redirect user to BBB meeting
                        window.location.href = url;
                        return;
                    }

                    let joinURL = new URL(url);
                    joinURL.searchParams.set("redirect", "true");
                    let joinURLStr = joinURL.toString();

                    return fetch(joinURLStr.toString());
                })
                .then((bbbResponse) => {
                    if (!bbbResponse.ok) {
                        throw new Error(`HTTP ${bbbResponse.status} while contacting BBB`);
                    }
                    return bbbResponse.url.includes("/html5client/") ? bbbResponse.url : bbbResponse.text();
                })
                .then((meetingUrl) => {
                    // Step 4: Embed in iframe
                    const $wrapper = $(`#${wrapperId}`);
                    if (!$wrapper.length) {
                        // eslint-disable-next-line no-console
                        console.warn(`[BBB] Missing #${wrapperId} container.`);
                        return;
                    }
                    $form.hide();
                    $meetingControls.show();
                    $wrapper.show(); // Ensure wrapper is visible
                    meetingActive = true;

                    $(`#${bbbRoomNotExistMessageID}`).hide();

                    $wrapper.html(
                        $("<iframe>", {
                            src: meetingUrl,
                            width: "100%",
                            height: isFullPage ? "100%" : 640,
                            allow: "geolocation *; microphone *; camera *; display-capture *;",
                            allowFullScreen: "true",
                            webkitallowfullscreen: "true",
                            mozallowfullscreen: "true",
                            sandbox: "allow-same-origin allow-scripts allow-modals allow-forms",
                            id: iframeId,
                        })
                            .on("load", function () {
                                $submitBtn.prop("disabled", false).html(originalText);
                            })
                            .on("error", function () {
                                showMeetingEndedMessage("Could not load the meeting in IFrame. Please try again.", "danger");
                                showFormAgain($submitBtn, originalText);
                            })
                    );
                })
                .catch((err) => {
                    showMeetingEndedMessage("Could not start the meeting. Please try again.", "danger");
                    showFormAgain($submitBtn, originalText);
                });
        });

        $leaveBtn.on("click", function () {
            if (confirm("Are you sure you want to leave the meeting?")) {
                leaveMeeting($(`#${formId} button[type="submit"]`), '<i class="fas fa-sign-in-alt me-1"></i> Join Meeting');
            }
        });

        $fullBtn.on("click", toggleFullPage);

        $(document).on("visibilitychange", function () {
            if (document.hidden && meetingActive && isFullPage) {
                toggleFullPage(); // Exit fullpage when user switches tabs
            }
        });

        $returnToMeetingBtn.on("click", function () {
            if (meetingActive) {
                $form.hide();
                $returnButton.show();
                $wrapper.show();
                $(`#${iframeId}`)[0]?.contentWindow?.focus?.();
            }
        });

        // Helper functions ---------------------------------------------------------------

        function showFormAgain($submitBtn, originalText) {
            $form.show();
            $meetingControls.hide();
            meetingActive = false;
            isFullPage = false;
            $wrapper.removeClass("bbb-fullpage-active");
            $wrapper.empty().show(); // Ensure wrapper is visible and empty
            $submitBtn.prop("disabled", false).html(originalText);
        }

        function toggleFullPage() {
            if (!isFullPage) {
                $wrapper.addClass("bbb-fullpage-active");
                $meetingControls.addClass("bbb-controls-fullpage");
                $fullBtn.html('<i class="fas fa-compress me-1"></i> Exit Full Screen').removeClass("btn-outline-secondary").addClass("btn-warning");
            } else {
                // Exit full page mode
                $wrapper.removeClass("bbb-fullpage-active");
                $meetingControls.removeClass("bbb-controls-fullpage");
                $fullBtn.html('<i class="fas fa-expand me-1"></i> Full Screen').removeClass("btn-warning").addClass("btn-outline-secondary");
            }
            isFullPage = !isFullPage;
        }

        function leaveMeeting($submitBtn, originalText) {
            if (isFullPage) {
                toggleFullPage();
            }
            meetingActive = false;
            showFormAgain($submitBtn, originalText);
            showMeetingEndedMessage("You have left the meeting.", "info");
        }

        function showMeetingEndedMessage(message, type = "info") {
            const $notification = $(
                '<div class="alert alert-' +
                    type +
                    ' alert-dismissible fade show">' +
                    '<i class="fas fa-info-circle me-2"></i>' +
                    message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    "</div>"
            );

            $form.before($notification);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.alert("close");
            }, 5000);
        }

        /*
         * STYLES FOR PSEUDO-FULLSCREEN MODE FOR BBB BIGBLUEBUTTON PLUGIN
         * =============================================================================
         *
         * Purpose:
         *   Create an immersive, app-like experience without using browser's native fullscreen API.
         *   This allows users to focus on the BigBlueButton session while keeping browser controls accessible.
         *
         * How it works:
         *   1. The .bbb-fullpage-active class is applied to the iframe wrapper container
         *   2. Container becomes fixed-position covering the entire viewport
         *   3. All surrounding Tiki UI (toolbars, menus, modules) are hidden behind the high z-index layer
         *   4. Custom controls panel is repositioned for usability in this mode
         *
         * Reasons for this approach:
         *   - Native fullscreen would hide browser controls, potentially confusing users
         *   - This approach maintains browser navigation, address bar, and extension accessibility
         *   - We want the user to feel like they are using BBB as a standalone app,
         *     without entering the browser’s real fullscreen mode
         *
         * Implementation notes:
         *   - Uses !important to override Bootstrap and Tiki's default layout constraints
         *   - Responsive design maintained through viewport units (vw/vh)
         */
        const fullPageStyles = `
            .bbb-fullpage-active {
                overflow: hidden !important;
                position: fixed ;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                z-index: 9997;
                background: white;
                margin: 0;
            }

            .bbb-fullpage-active #${iframeId} {
                height: 100% !important;
            }

            .bbb-controls-fullpage {
                position: fixed !important;
                top: 20px !important;
                left: 30% !important;
                z-index: 9999 !important;
                background: rgba(255, 255, 255, 0.95) !important;
                padding: 15px !important;
                border-radius: 8px !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
                backdrop-filter: blur(10px) !important;
                border: 1px solid rgba(0, 0, 0, 0.1) !important;
                margin: 0 !important;
            }

            .bbb-controls-fullpage {
                opacity: 0.8 !important;
                transition: opacity 0.3s ease !important;
            }

            .bbb-controls-fullpage:hover {
                opacity: 1 !important;
            }
        `;

        $("<style>").text(fullPageStyles).appendTo("head");
    });
}

export { setupBBBMeetingInIframe };
