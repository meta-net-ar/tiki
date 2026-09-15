import { getPreviewNavLinks, submitFolder, updateEmailPreview } from "./emailFolder.helpers";

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

    const navigationLinks = getPreviewNavLinks(this);

    $.openModal({
        content: `<div class="email-folder-preview">${$.IMPORT_LOADER_MARKUP}</div>`,
        size: "modal-xl",
        buttons: [],
        open: function () {
            $(this).find(".modal-header").html(`
                ${navigationLinks}
                <a href="${$(e.target).attr("href")}" target="_blank" class="ms-auto expand-link">${tr("Expand")} ${$.fn.getIcon("link-external").prop("outerHTML")}</a>
            `);
        },
    });

    updateEmailPreview($(this).attr("href"));
});

$(document).on("click", ".nav-email-preview", function (e) {
    e.preventDefault();

    const modalContent = $(this).closest(".modal-content");
    modalContent.find(".email-folder-preview").html($.IMPORT_LOADER_MARKUP);

    updateEmailPreview($(this).attr("href"));

    const emailViewPath = $(".tracker-email-view-path[href='" + $(this).attr("href") + "']");
    const navigationLinks = getPreviewNavLinks(emailViewPath[0]);

    modalContent.find(".modal-header .email-preview-nav").replaceWith(navigationLinks);
    modalContent.find(".modal-header .expand-link").attr("href", $(this).attr("href"));
});
