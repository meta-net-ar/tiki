import { parseData } from "./formSubmission.helpers";

export default function (textarea) {
    if (textarea.summernote("codeview.isActivated")) {
        parseData(textarea, null, true);
    } else {
        parseData(textarea, null, false);
    }
}
