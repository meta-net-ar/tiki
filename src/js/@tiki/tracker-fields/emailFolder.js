import DOMPurify from "dompurify";

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
let previewEmailTimeout = null;

$(".tracker-email-view-path")
    .on("mouseenter", function (e) {
        if (!isHoverable) return;

        // Add a small delay before showing the preview to avoid flickering when moving the mouse quickly over multiple emails
        previewEmailTimeout = setTimeout(() => {
            const previewPopover = bootstrap.Popover.getOrCreateInstance(this, {
                trigger: "manual",
                html: true,
                content: " ", // non-empty content, otherwise the popover won't show
            });

            // Avoid refetching popover content if it's already loaded
            if (previewPopover._element.querySelector(".popover-body")?.innerHTML.trim()) {
                previewPopover.show();
                return;
            }

            previewPopover.setContent({
                ".popover-body": $.BUTTON_LOADER_MARKUP,
            });

            previewPopover.show();

            const fieldId = $(this).data("fieldId");
            const itemId = $(this).data("itemId");
            const fileId = $(this).data("fileId");

            $.get($.service("tracker", "previewItemEmail", { fieldId, itemId, fileId }), function (email) {
                if (email) {
                    // Do not use popover.setContent() here, as it causes the popover to flicker.
                    $(previewPopover.tip).find(".popover-body")[0].innerHTML = `<pre>${email.plaintext || ""}</pre>`;
                }
            });
        }, 500);
    })
    .on("mouseleave", function (e) {
        if (!isHoverable) return;
        clearTimeout(previewEmailTimeout);
        const previewPopover = bootstrap.Popover.getInstance(this);
        if (previewPopover) {
            previewPopover.hide();
        }
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
