import initSummernote from "./initSummernote";

export default function (toolbar, lang, page) {
    const inlineEdit = $("#wysiwyg_inline_edit");
    if (inlineEdit.hasClass("active")) {
        $("#page-data > .inline-editor-content").show();
        $("#page-data > .content").hide();
    }
    inlineEdit.on("click", function () {
        if (inlineEdit.hasClass("active")) {
            $("#page-data > .inline-editor-content").hide();
            $("#page-data > .content").show();

            inlineEdit.find(".icon-toggle-off").show();
            inlineEdit.find(".icon-toggle-on").hide();

            inlineEdit.removeClass("active highlight");
        } else {
            $("#page-data > .inline-editor-content").show();
            $("#page-data > .content").hide();

            inlineEdit.find(".icon-toggle-on").show();
            inlineEdit.find(".icon-toggle-off").hide();

            inlineEdit.addClass("active highlight");
        }
    });

    $("#page-data > .inline-editor-content > *")
        .not(".icon_edit_section")
        .not(".editplugin")
        .each(function () {
            $(this).find(".heading-link").remove();

            const id = "inline-edit-" + Math.random().toString(36).substring(7);

            $(this).wrap(`<div id="${id}" class="inline-editor"></div>`);
            $(this).on("click", (e) => {
                e.preventDefault();
                toggleInlineEditor(id, toolbar, lang, page);
            });
        });
}

function toggleInlineEditor(id, toolbar, lang, page) {
    const target = $(`#${id}`);
    initSummernote(id, toolbar, lang);

    const initialValue = target.html();

    const actions = $("<div class='d-flex justify-content-end mt-1 gap-1 inline-editor-actions'></div>");
    const saveButton = $("<button></button>").text(tr("Save")).addClass("btn btn-outline-primary btn-sm");
    const cancelButton = $("<button></button>").text(tr("Cancel")).addClass("btn btn-outline-secondary btn-sm");
    actions.append(cancelButton, saveButton);
    const editor = target.data("summernote").layoutInfo.editor;
    editor.after(actions);

    const closeEditor = () => {
        target.summernote("destroy");
        actions.remove();
        target
            .children()
            .first()
            .each(function () {
                $(this).on("click", function () {
                    toggleInlineEditor(id, toolbar, lang, page);
                });
            });
    };

    cancelButton.on("click", function () {
        target.html(initialValue);
        closeEditor();
    });

    saveButton.on("click", function () {
        editor.tikiModal(tr("Saving..."));
        target.html(target.summernote("code"));

        const data = $("<div/>");
        $(".inline-editor").each(function () {
            data.append($(this).html());
        });

        $.post($.service("edit", "inlinesave"), {
            data: data.html(),
            page: page,
        })
            .done(function () {
                editor.tikiModal();
                closeEditor();
            })
            .fail(function () {
                editor.tikiModal();
                $.notify(tr("An error occurred while saving the content."));
            });
    });
}
