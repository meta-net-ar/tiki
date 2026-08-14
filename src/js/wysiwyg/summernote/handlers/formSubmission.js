import { parseData } from "./formSubmission.helpers";

export default function (textarea) {
    const form = textarea.closest("form");
    form.data("should-parse-editor-data", form.data("should-parse-editor-data") ?? true);
    const currentHandlerCounter = form.data("handlerCounter") || 0;
    form.data("handlerCounter", currentHandlerCounter + 1);

    textarea.closest("form").on("submit", function (e) {
        if (
            !form.data("should-parse-editor-data") ||
            textarea.summernote("codeview.isActivated") ||
            (form.validate?.()?.pendingRequest && form.data("should-parse-editor-data") && !form.data("submitted"))
        ) {
            /*
            --- should-parse-editor-data flag ---
            This is an internally editor-set flag, which is practically used to prevent potential double executions of the ajaxSubmitEventHandler function,
            and an infinite data-parsing loop.

            --- submitted flag ---
            Another submit event usually gets triggered by the jquery validation plugin, and when this happens,
            data-parsing is already done. So this flag ensure that the data-parsing and submission are not done twice.
            */

            textarea.data("is-submitting", true); // prevent the browser from warning about unsaved changes
            return;
        }
        e.preventDefault();
        form.data("submitted", false);

        parseData(textarea, () => {
            textarea.data("is-submitting", true); // prevent the browser from warning about unsaved changes

            form.data("handlerCounter", form.data("handlerCounter") - 1);
            if (form.data("handlerCounter") > 0) {
                return;
            }

            form.data("should-parse-editor-data", false);
            const submitter = e.originalEvent?.submitter;
            if (submitter) {
                $(submitter).trigger("click");
            } else {
                this.submit();
            }
        });
    });
}
