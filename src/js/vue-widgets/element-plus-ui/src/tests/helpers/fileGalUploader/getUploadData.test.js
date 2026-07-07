import { beforeAll, describe, expect, test, vi } from "vitest";
import getUploadData from "../../../helpers/fileGalUploader/getUploadData";
import $ from "jquery";
import * as fileToBase64 from "../../../helpers/fileGalUploader/fileToBase64";
import * as adjustImageSize from "../../../helpers/fileGalUploader/adjustImageSize";
import { ElMessage } from "element-plus";

describe("fileGalUploader getUploadData helper", () => {
    beforeAll(() => {
        window.$ = $;
        const form = $("<form id='file_0'><input type='text' name='foo' value='bar' /></form>");
        $("body").append(form);
    });

    test("returns the correct data given any file", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        const givenFileBase64Data = "data:text/plain;base64,base64 Mock data";
        vi.spyOn(fileToBase64, "default").mockResolvedValueOnce(givenFileBase64Data);

        const result = await getUploadData(givenFile);

        expect(fileToBase64.default).toHaveBeenCalledWith(givenFile);

        expect(result).toEqual({
            name: givenFile.name,
            type: givenFile.type,
            size: givenFile.size,
            data: givenFileBase64Data.replace(/^data:.*?;base64,/, ""),
            foo: "bar",
        });
    });

    test("returns the resized image data given an image file and maxWidth and maxHeight", async () => {
        const givenFile = new File(["foo"], "foo.jpg", { type: "image/jpeg" });
        const givenFileBase64Data = "data:image/jpeg;base64,base64 Mock data";
        const givenResizedImageBase64Data = "data:image/jpeg;base64,base64 Mock resized data";
        vi.spyOn(fileToBase64, "default").mockResolvedValueOnce(givenFileBase64Data);
        vi.spyOn(adjustImageSize, "default").mockResolvedValueOnce(givenResizedImageBase64Data);

        const result = await getUploadData(givenFile, 100, 200);

        expect(adjustImageSize.default).toHaveBeenCalledWith(givenFileBase64Data, 100, 200, givenFile.type);

        expect(result).toEqual({
            name: givenFile.name,
            type: givenFile.type,
            size: givenFile.size,
            data: givenResizedImageBase64Data.replace(/^data:.*?;base64,/, ""),
            foo: "bar",
        });
    });

    test("shows an error message when the file data cannot be retrieved", async () => {
        const givenFile = new File(["foo"], "foo.txt", { type: "text/plain" });
        vi.spyOn(fileToBase64, "default").mockRejectedValueOnce("foo");
        const elMessage = vi.spyOn(ElMessage, "error");

        await getUploadData(givenFile);

        expect(elMessage).toHaveBeenCalledWith("Failed to get file data");
    });
});
