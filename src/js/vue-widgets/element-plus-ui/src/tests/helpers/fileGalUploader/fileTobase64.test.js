import { describe, test, vi } from "vitest";
import fileToBase64 from "../../../helpers/fileGalUploader/fileToBase64";
import { waitFor } from "@testing-library/vue";

describe("fileGalUploader fileToBase64 helper", () => {
    test("resolves to the base64 data of the given file", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        const givenFileBase64Data = "data:foo/bar";

        const fileReader = new FileReader();
        const readAsDataURLMock = vi.fn();
        vi.spyOn(window, "FileReader").mockImplementation(() => {
            fileReader.readAsDataURL = readAsDataURLMock;
            return fileReader;
        });

        const promise = fileToBase64(givenFile);

        await waitFor(() => {
            expect(readAsDataURLMock).toHaveBeenCalledWith(givenFile);
        });

        fileReader.result = givenFileBase64Data;
        fileReader.onload();

        expect(promise).resolves.toEqual(givenFileBase64Data);
    });

    test("rejects with an error when the file data cannot be retrieved", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        const givenError = new Error("foo");

        const fileReader = new FileReader();
        vi.spyOn(window, "FileReader").mockImplementation(() => {
            return fileReader;
        });

        const promise = fileToBase64(givenFile);

        fileReader.onerror(givenError);

        await expect(promise).rejects.toEqual(givenError);
    });
});
