import { afterAll, beforeAll, describe, test, vi } from "vitest";
import fileToBase64 from "../../../helpers/fileGalUploader/fileToBase64";
import { waitFor } from "@testing-library/vue";

const readAsDataURLMock = vi.fn();
let fileReaderInstance;
class MockFileReader extends FileReader {
    constructor() {
        super();
        this.readAsDataURL = readAsDataURLMock;
        fileReaderInstance = this;
    }
}

describe("fileGalUploader fileToBase64 helper", () => {
    beforeAll(() => {
        vi.stubGlobal("FileReader", MockFileReader);
    });

    afterAll(() => {
        vi.unstubAllGlobals();
    });

    test("resolves to the base64 data of the given file", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        const givenFileBase64Data = "data:foo/bar";

        const promise = fileToBase64(givenFile);

        await waitFor(() => {
            expect(readAsDataURLMock).toHaveBeenCalledWith(givenFile);
        });

        fileReaderInstance.result = givenFileBase64Data;
        fileReaderInstance.onload();

        expect(promise).resolves.toEqual(givenFileBase64Data);
    });

    test("rejects with an error when the file data cannot be retrieved", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        const givenError = new Error("foo");

        const promise = fileToBase64(givenFile);

        fileReaderInstance.onerror(givenError);

        await expect(promise).rejects.toEqual(givenError);
    });
});
