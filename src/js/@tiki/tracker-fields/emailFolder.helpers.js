export function updateEmailPreview(msgUid, listPath) {
    $.get($.service("tracker", "previewItemEmail", { uid: msgUid, listPath: listPath }), function (email) {
        $(".email-folder-preview").html(`
            <div class="msg-headers">${email.headers}</div>
            <div class="msg-body">${email.body}</div>
            <div class="msg-parts">${email.msg_parts}</div>`);
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
    }).fail(() => {
        showMessage(tr("An error occurred while fetching the email preview."), "error");
        $.closeModal();
    });
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
