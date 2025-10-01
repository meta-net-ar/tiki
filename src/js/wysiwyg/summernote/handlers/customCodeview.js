import { parseData } from "./formSubmission.helpers";

export default function (textarea) {
    if (textarea.summernote("codeview.isActivated")) {
        parseData(textarea, null, true);
        textarea.data("summernote").layoutInfo.codable.on("change", (e) => textarea.val(e.target.value));
    } else {
        parseData(textarea, null, false);
    }
}
