import { describe, test } from "vitest";
import { UploadAjaxError } from "element-plus/es/components/upload/src/ajax";
import getUploadAjaxError from "../../../helpers/fileGalUploader/getUploadAjaxError";

describe("fileGalUploader getUploadAjaxError helper", () => {
    test.each([
        [{ response: { error: "Server response error" } }],
        [{ response: "Server response string" }],
        [{ responseText: "Server response text" }],
        [{}],
    ])("returns UploadAjaxError with correct message and status for xhr: %o", (mockXhr) => {
        const mockOption = {
            method: "POST",
            action: "/upload",
        };

        const uploadAjaxErrorInstance = getUploadAjaxError(mockOption, {
            ...mockXhr,
            status: 500,
        });

        let expectedMessage;
        if (mockXhr.response) {
            expectedMessage = `${mockXhr.response.error || mockXhr.response}`;
        } else if (mockXhr.responseText) {
            expectedMessage = `${mockXhr.responseText}`;
        } else {
            expectedMessage = `fail to ${mockOption.method} ${mockOption.action} 500`;
        }

        expect(uploadAjaxErrorInstance).toBeInstanceOf(UploadAjaxError);
        expect(uploadAjaxErrorInstance.message).toBe(expectedMessage);
        expect(uploadAjaxErrorInstance.status).toBe(500);
        expect(uploadAjaxErrorInstance.method).toBe("POST");
        expect(uploadAjaxErrorInstance.url).toBe("/upload");
    });
});
