import { UploadAjaxError } from "element-plus/es/components/upload/src/ajax";

/**
 * Returns an instance of UploadAjaxError with the appropriate message and status.
 * @param {Object} option
 * @param {XMLHttpRequest} xhr
 * @returns
 */
export default function (option, xhr) {
    let msg;
    if (xhr.response) {
        msg = `${xhr.response.error || xhr.response}`;
    } else if (xhr.responseText) {
        msg = `${xhr.responseText}`;
    } else {
        msg = `fail to ${option.method} ${option.action} ${xhr.status}`;
    }

    return new UploadAjaxError(msg, xhr.status, option.method, option.action);
}
