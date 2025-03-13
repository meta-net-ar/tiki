import { parseData } from "./formSubmission.helpers";

export default function (textarea) {
    textarea.closest("form").on("submit", function (e) {
        if (textarea.data("parsed") || textarea.summernote("codeview.isActivated")) {
            return;
        }
        e.preventDefault();

        parseData(textarea, () => {
            textarea.data("parsed", true);
            const submitter = e.originalEvent?.submitter;
            if (submitter) {
                $(submitter).trigger("click");
            } else {
                this.submit();
            }
        });
    });
}
