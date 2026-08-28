$(".add-email-folder").on("click", function () {
    const fieldId = $(this).data("fieldId");
    const itemId = $(this).data("itemId");
    $.openModal({
        title: tr("Add Folder"),
        content: `<div>
            <label for="email-folder-name">${tr("Folder Name")}</label>
            <input type="text" id="email-folder-name" class="form-control" placeholder="${tr("Enter folder name")}">
        </div>`,
        buttons: [
            {
                text: tr("Save") + ` ${$.fn.getIcon("save").prop("outerHTML")}`,
                onClick: function (e) {
                    submitFolder(e.target, this, fieldId, itemId);
                },
            },
        ],
    });
});

const isHoverable = matchMedia("(hover: hover) and (pointer: fine)").matches;

$(".tracker-email-view-path").on("click", function (e) {
    if (!isHoverable) return;

    e.preventDefault();

    $.openModal({
        content: `<div class="email-folder-preview">${$.IMPORT_LOADER_MARKUP}</div>`,
        size: "modal-xl",
        buttons: [],
        open: function () {
            $(this)
                .find(".modal-header")
                .html(
                    `<a href="${$(e.target).attr("href")}" target="_blank" class="ms-auto">${tr("Expand")} ${$.fn.getIcon("link-external").prop("outerHTML")}</a>`
                );
        },
    });

    const urlParams = new URLSearchParams($(this).attr("href").split("?")[1]);

    $.get($.service("tracker", "previewItemEmail", { uid: urlParams.get("uid"), listPath: urlParams.get("list_path") }), function (email) {
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
});

function submitFolder(triggerButton, modal, fieldId, itemId) {
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
