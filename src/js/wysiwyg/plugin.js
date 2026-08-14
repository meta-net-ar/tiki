import initSummernote, { loadLanguage } from "@wysiwyg/summernote";

export default function (elementId, tools, page, editorConfig) {
    const target = $(`#${elementId}`);

    const editButton = $("<button></button>").text(tr("Edit")).addClass("btn btn-primary edit_button");
    target.wrap("<div class='wp_wysiwyg-wrapper'></div>");
    target.after(editButton);

    const initialValue = target.html();

    loadLanguage(editorConfig.langFilePath, () => {
        editButton.on("click", function () {
            target.tikiModal(tr("Loading..."));
            $.getJSON(
                $.service("semaphore", "is_set_by_other"),
                {
                    object_type: jqueryTiki.current_object.type,
                    object_id: jqueryTiki.current_object.object,
                },
                function (data) {
                    if (data) {
                        $("#tikifeedback").showError(tr("This page is being edited by another user. Please reload the page and try again later."));
                        return;
                    }

                    initSummernote(elementId, tools, { lang: editorConfig.lang });

                    const actions = $("<div class='d-flex justify-content-end mt-1 gap-1'></div>");
                    const saveButton = $("<button></button>").text(tr("Save")).addClass("btn btn-outline-primary btn-sm");
                    const cancelButton = $("<button></button>").text(tr("Cancel")).addClass("btn btn-outline-secondary btn-sm");
                    actions.append(cancelButton, saveButton);
                    const editor = target.data("summernote").layoutInfo.editor;
                    editor.after(actions);

                    cancelButton.on("click", function () {
                        target.summernote("destroy");
                        actions.remove();
                        target.html(initialValue);
                    });

                    saveButton.on("click", function () {
                        editor.tikiModal(tr("Saving..."));
                        $.getJSON($.service("semaphore", "set"), {
                            object_type: jqueryTiki.current_object.type,
                            object_id: jqueryTiki.current_object.object,
                        });

                        $.getJSON($.service("semaphore", "unset"), {
                            object_type: jqueryTiki.current_object.type,
                            object_id: jqueryTiki.current_object.object,
                        });

                        const options = {
                            page: page,
                            type: "wysiwyg",
                            message: "Modified by WYSIWYG Plugin",
                            index: target.data("index"),
                            content: target.summernote("code"),
                            params: { use_html: target.data("html") },
                            ticket: target.data("ticket"),
                        };

                        $.post(
                            $.service("plugin", "replace"),
                            options,
                            function () {
                                target.html(options.content);
                                editor.tikiModal();
                                target.summernote("destroy");
                                actions.remove();
                            },
                            "json"
                        );
                    });
                }
            )
                .always(() => target.tikiModal())
                .fail(() => $("#tikifeedback").showError(tr("An error occurred while loading the editor.")));
        });
    });
}
