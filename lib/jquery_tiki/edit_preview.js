/**
 * New "in-tabs" edit previews
 */

if (typeof initEditPreview === "undefined") {
    function initEditPreview() {
        $(".edit-preview-zone").each(function () {
            const $this = $(this),
                $tabs = $this.find(".tabs"),
                $preview = $this.find(".textarea-preview"),
                textAreaId = $preview.attr("id").replace("preview_div_", ""),
                $textarea = $("#" + textAreaId),
                is_markdown = $textarea.closest("form").find("[name=syntax]").val() === "markdown" ? 1 : 0;
                isHtml = $textarea.closest("form").find("[name=wysiwyg]").val() === "y" ? 1 : 0;
            $('li:nth-child(2) a[data-bs-toggle="tab"]', $tabs).on('show.bs.tab', function (event) {
                let data = "";

                if ($textarea.data("summernote") && !$textarea.summernote("codeview.isActivated")) {
                    $preview.html($textarea.summernote("code"));
                } else {
                    data = ($textarea.data("summernote") && $textarea.summernote("code")) || $textarea.val();
                    $preview.innerHeight($textarea.height());

                    if (is_markdown) {
                        data = "{syntax type=markdown}\n" + data;
                    }

                    $.getJSON($.service("edit", "tohtml"), {
                            data: data,
                            allowhtml: isHtml,
                        },
                        function (data) {
                            $preview.html(data.data);
                        }
                    );
                }
            });

            $('li:first-child a[data-bs-toggle="tab"]', $tabs).tab("show");
        });
    }
}

$(function() {
    initEditPreview();
});

$(document).on("tiki.ajax.redraw tiki.modal.redraw", function () {
    initEditPreview();
});
