export default function (context) {
    $.openModal({
        title: tr("Wiki Link"),
        size: "modal-sm",
        content: `
            <div class="form-group">
                <label for="label">${tr("Label")}</label>
                <input type="text" class="form-control" id="label" name="label" value="">
            </div>
            <div class="form-group">
                <label for="page">${tr("Page")}</label>
                <input type="text" class="form-control" id="page" name="page" value="">
            </div>
        `,
        buttons: [
            {
                text: tr("Insert"),
                type: "primary btn-sm",
                onClick: function () {
                    const label = $("#label").val();
                    const page = $("#page").val();

                    if (!page) return;

                    let link = page;
                    if (!jqueryTiki.sefurl) {
                        link = "tiki-index.php?page=" + page;
                    }

                    const html = `<a href="${link}">${label || page}</a>`;
                    const textareaId = context.$note.attr("id");
                    insertAt(textareaId, html);

                    $.closeModal();
                },
            },
        ],
        open: function () {
            autocomplete($("#page")[0], "pagename");
        },
    });
}
