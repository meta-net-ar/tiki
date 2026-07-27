import { afterAll, describe, expect, test, vi } from "vitest";
import adjustImageSize from "../../../helpers/fileGalUploader/adjustImageSize";

describe("fileGalUploader adjustImageSize helper", () => {
    test.todo("returns the image data after resizing it to the ratio of the given max width and height", async () => {
        /*
        This requires the canvas context object, which is not yet available in happy-dom. Let's see if the ticket
        https://github.com/capricorn86/happy-dom/issues/241 gets any progress.
        */
    });

    afterAll(() => {
        vi.unstubAllGlobals();
    });

    test("rejects with an error when the image data cannot be loaded", async () => {
        const givenImageData = "data:image/jpeg;base64,base64 Mock data";
        const givenMaxWidth = 100;
        const givenMaxHeight = 200;
        const givenImageType = "image/jpeg";

        let imageInstance;
        class MockImage {
            constructor() {
                imageInstance = this;
            }

            set src(_value) {
                // Prevent implicit load in the test environment.
            }
        }
        vi.stubGlobal("Image", MockImage);

        const result = adjustImageSize(givenImageData, givenMaxWidth, givenMaxHeight, givenImageType);

        const givenError = new Error("foo");

        imageInstance.onerror(givenError);

        await expect(result).rejects.toEqual(givenError);
    });
});
