import { resetMarkers, setMarkers } from "./replace.helpers";

export default function (context) {
    $.openModal({
        title: tr("Find and replace"),
        size: "modal-sm",
        backdrop: false,
        content: `
            <div class="form-group">
                <label for="find">${tr("Find")}</label>
                <input type="text" class="form-control" id="find" name="find" value="" autofocus>
            </div>
            <div class="form-group">
                <label for="replace">${tr("Replace")}</label>
                <input type="text" class="form-control" id="replace" name="replace" value="">
            </div>
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="caseSensitive" name="caseSensitive">
                    <label class="form-check-label" for="caseSensitive">${tr("Case sensitive")}</label>
                </div>
            </div>
        `,
        buttons: [
            {
                text: tr("Replace"),
                type: "primary btn-sm",
                onClick: function () {
                    const replace = $("#replace").val();
                    if (!replace) return;

                    const editable = context.layoutInfo.editable;
                    editable.html(editable.html().replace(/<mark>\w+<\/mark>/, replace));
                },
            },
            {
                text: tr("Replace all"),
                type: "primary btn-sm",
                onClick: function () {
                    const replace = $("#replace").val();
                    if (!replace) return;

                    const editable = context.layoutInfo.editable;
                    editable.html(editable.html().replace(/<mark>\w+<\/mark>/g, replace));
                },
            },
        ],
        open: function () {
            $(this).on("hidden.bs.modal", function () {
                resetMarkers(context);
            });
            $("#find").on("keyup", function () {
                setMarkers(context, $(this).val());
            });
            $("#caseSensitive").on("change", function () {
                setMarkers(context, $("#find").val());
            });
        },
    });
}
