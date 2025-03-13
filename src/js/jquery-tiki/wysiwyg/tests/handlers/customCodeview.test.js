import { describe, test, vi } from "vitest";
import * as formSubmissionHelpers from "../../handlers/formSubmission.helpers";
import { customCodeview } from "../../handlers";

describe("customCodeview handler", () => {
    test.each([
        ["codeview is activated", true],
        ["codeview is not activated", false],
    ])("should call the 'parseData' helper with the correct arguments when the %s", (_, isActivated) => {
        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData").mockImplementation(() => {});

        const textarea = { summernote: vi.fn(() => isActivated) };

        customCodeview(textarea);

        expect(parseDataSpy).toHaveBeenCalledWith(textarea, null, isActivated);
    });
});
