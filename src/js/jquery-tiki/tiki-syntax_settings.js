export default function () {
    $("#syntax-settings").on("submit", function (event) {
        event.preventDefault();

        const domId = $(this).data("areaId");
        const $textarea = $("#" + domId);
        const $editZone = $textarea.closest(".edit-zone");
        const $form = $textarea.parents("form");
        const $syntaxInput = $form.find("input[name=syntax]");
        const syntaxType = $("#syntax-select", event.target).val();
        const editorType = $form.find("input[name=wysiwyg]").val() === "y" ? "wysiwyg" : "plain";

        if (syntaxType === $syntaxInput.val()) {
            $.closeModal();
            return;
        }

        $.post(
            event.target.action,
            {
                data: $textarea.val(),
                syntax: syntaxType,
                editor: editorType,
                domId: domId,
                domName: $textarea.attr("name"),
                page: $form.find("input[name=page]").val(),
                is_html: editorType === "wysiwyg" && $syntaxInput.val() === "tiki" ? "y" : "n",
            },
            function (data) {
                $.closeModal();
                $form.find("input[name=wysiwyg], input[name=syntax]").remove();

                if ($textarea.data("summernote")) {
                    $textarea.summernote("destroy");
                } else if (window.tuiEditors?.[domId]) {
                    const tuiEditor = window.tuiEditors[domId];
                    $textarea.remove();
                    $(tuiEditor.options.el).replaceWith(data);
                } else {
                    $editZone.replaceWith(data);
                }
            }
        ).fail(function (xhr) {
            $.closeModal();
        });

        return false;
    });
}
