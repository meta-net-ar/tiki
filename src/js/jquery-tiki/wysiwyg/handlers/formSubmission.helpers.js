export function parseData(textarea, onSuccess = null, toWiki = true) {
    const editor = textarea.data("summernote");
    const value = editor.code();

    editor.layoutInfo.editor.tikiModal(tr("Please wait..."));

    const payload = {
        data: value,
    };

    if (!toWiki) {
        payload.htmleditor = 1;
    }

    $.ajax({
        url: $.service("edit", toWiki ? "towiki" : "tohtml"),
        type: "POST",
        dataType: "json",
        data: payload,
        success: (res) => {
            editor.code(res.data);
            if (onSuccess) {
                onSuccess();
            }
        },
        error: function () {
            $("#tikifeedback").showError("An error occured while parsing the content.");
        },
        complete: function () {
            editor.layoutInfo.editor.tikiModal();
        },
    });
}
