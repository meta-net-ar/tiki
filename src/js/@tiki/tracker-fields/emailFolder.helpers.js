export async function updateEmailPreview(href) {
    const urlParams = new URLSearchParams(href.split("?")[1]);

    const requestKey = await getCyphtRequestKey();
    if (!requestKey) return;

    $.post(
        $.service("cypht", "ajax", {
            cypht_page: "message",
            uid: urlParams.get("uid"),
            list_path: urlParams.get("list_path"),
        }),
        {
            imap_msg_uid: urlParams.get("uid"),
            list_path: urlParams.get("list_path"),
            hm_ajax_hook: "ajax_tiki_message_content",
            hm_page_key: requestKey,
        },
        function (res) {
            if (res.match?.(/"status":\s*"not callable"/)) {
                getCyphtRequestKey(true); // Refresh the request key, which is the culprit for "not callable" status.
                return updateEmailPreview(href);
            }

            $(".email-folder-preview").html(`
                <div class="msg-headers">${res.msg_headers}</div>
                <div class="msg-body">${res.msg_text}</div>
                <div class="msg-parts">${res.msg_parts}</div>`);
            // Remove .msg_actions and .long_header, if the user wants more interaction, they can expand the email in a new tab.
            $(".email-folder-preview").find(".msg_actions, .long_header").remove();

            // Disable interactive elements in msg_haders and msg_parts. For interaction, the user can expand the email in a new tab.
            $(".email-folder-preview")
                .find(".msg-headers a, .msg-parts a, .msg-headers button, .msg-parts button")
                .each(function () {
                    $(this).replaceWith(
                        $(this)
                            .clone()
                            .removeAttr("onclick")
                            .css("cursor", "not-allowed")
                            .attr("data-bs-content", tr("Expand the email in a new tab to interact with this element."))
                            .attr("data-bs-toggle", "tooltip")
                            .attr("data-bs-trigger", "hover")
                            .addClass("tips")
                    );
                });

            $(".email-folder-preview").tiki_popover();
        }
    ).fail(() => {
        showMessage(tr("An error occurred while fetching the email preview."), "error");
        $.closeModal();
    });
}

async function getCyphtRequestKey(refresh = false) {
    if (sessionStorage.getItem("cypht_request_key") && !refresh) {
        return sessionStorage.getItem("cypht_request_key");
    }

    try {
        const response = await fetch($.service("cypht", "getRequestKey"));
        const requestKey = await response.json();
        sessionStorage.setItem("cypht_request_key", requestKey);
        return requestKey;
    } catch (error) {
        showMessage(tr("Failed to launch the request"), "error");
        $.closeModal();
        return;
    }
}

export function getPreviewNavLinks(currentItem) {
    const nextItem = $(currentItem).closest(".email-row").next(".email-row").find(".tracker-email-view-path");
    const prevItem = $(currentItem).closest(".email-row").prev(".email-row").find(".tracker-email-view-path");

    return `
        <div class="d-flex gap-3 w-50 email-preview-nav">
            ${prevItem.length ? `<a href="${prevItem.attr("href")}" class="btn btn-sm btn-light text-truncate w-50 d-flex align-items-center gap-2 nav-email-preview">${$.fn.getIcon("chevron-left").prop("outerHTML")} <span class="text-truncate">${prevItem.text()}</span></a>` : ""}
            ${nextItem.length ? `<a href="${nextItem.attr("href")}" class="btn btn-sm btn-light w-50 d-flex align-items-center gap-2 nav-email-preview"><span class="text-truncate">${nextItem.text()}</span> ${$.fn.getIcon("chevron-right").prop("outerHTML")}</a>` : ""}
        </div>
    `;
}

export function submitFolder(triggerButton, modal, fieldId, itemId) {
    const folder = $(modal).find("#email-folder-name").val().trim();
    if (!folder) return;

    const buttonContent = $(triggerButton).html();
    $(triggerButton).html($.BUTTON_LOADER_MARKUP);
    $.post(
        $.service("tracker", "addItemEmailFolder"),
        {
            folder: folder,
            fieldId,
            itemId,
        },
        function (response) {
            if (response.success) {
                $.closeModal(modal);
                showMessage(response.message, "success");
            }
        }
    ).fail(() => {
        showMessage(tr("An error occurred while saving the folder."), "error");
        $(triggerButton).html(buttonContent);
    });
}
