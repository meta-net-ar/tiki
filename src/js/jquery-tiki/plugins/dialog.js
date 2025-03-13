import * as bootstrap from "bootstrap";

export function initializeDialog(dialogId, autoOpen = false, openCallback = null) {
    $(`#${dialogId}`).appendTo("body");
    if (autoOpen) {
        bootstrap.Modal.getOrCreateInstance($(`#${dialogId}`)).show();
    }
    if (openCallback) {
        $(`#${dialogId}`).on("shown.bs.modal", openCallback);
    }
}
