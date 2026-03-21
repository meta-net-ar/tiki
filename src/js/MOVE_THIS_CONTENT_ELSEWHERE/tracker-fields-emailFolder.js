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
