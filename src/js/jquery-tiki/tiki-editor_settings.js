export default function () {
    $("#editor-settings").on("submit", function (event) {
        event.preventDefault();

        const domId = $(this).data("areaId");
        const $textarea = $("#" + domId);
        const $form = $textarea.parents("form");
        const $editorSelect = $("#editor-select", event.target);
        const $syntaxSelect = $("#syntax-select", event.target);
        const $wysiwygInput = $form.find("input[name=wysiwyg]");
        const $syntaxInput = $form.find("input[name=syntax]");

        const initialEditorType = $wysiwygInput.val() === "y" ? "wysiwyg" : "plain";

        if ($editorSelect.val() === initialEditorType && $syntaxSelect.val() === $syntaxInput.val()) {
            $.closeModal();
            return;
        }

        $.post(
            event.target.action,
            {
                data: $textarea.val(),
                syntax: $syntaxSelect.val(),
                editor: $editorSelect.val(),
                domId: domId,
                domName: $textarea.attr("name"),
                page: $form.find("input[name=page]").val(),
            },
            function (data) {
                $.closeModal();

                // remove the old textarea relative inputs
                $form.find("input[name=wysiwyg]").remove();
                $form.find("input[name=syntax]").remove();

                if ($textarea.data("summernote")) {
                    $textarea.summernote("destroy");
                    $textarea.replaceWith(data);
                } else if (window.tuiEditors?.[domId]) {
                    const tuiEditor = window.tuiEditors[domId];
                    $textarea.remove();
                    $(tuiEditor.options.el).replaceWith(data);
                } else {
                    $textarea.closest(".edit-zone").replaceWith(data);
                }
            }
        );

        return false;
    });
}
