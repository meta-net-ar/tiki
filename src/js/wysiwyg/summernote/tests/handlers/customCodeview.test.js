import { afterEach, describe, test, vi } from "vitest";
import * as formSubmissionHelpers from "../../handlers/formSubmission.helpers";
import { customCodeview } from "../../handlers";
import $ from "jquery";

vi.mock("../../handlers/formSubmission.helpers", () => {
    return {
        parseData: vi.fn(),
    };
});

describe("customCodeview handler", () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    test.each([
        ["codeview is activated", true],
        ["codeview is not activated", false],
    ])("should call the 'parseData' helper with the correct arguments when the %s", (_, isActivated) => {
        const textarea = { summernote: vi.fn(() => isActivated), data: vi.fn(() => ({ layoutInfo: { codable: { on: vi.fn() } } })) };

        customCodeview(textarea);

        expect(formSubmissionHelpers.parseData).toHaveBeenCalledWith(textarea, null, isActivated);
    });

    test("should keep the textarea value in sync with the codable content when the codeview is activated", () => {
        const textarea = $("<textarea></textarea>");
        textarea.summernote = vi.fn(() => true);
        textarea.data("summernote", {
            layoutInfo: {
                codable: $("<textarea></textarea>"),
            },
        });

        $("body").append(textarea.data("summernote").layoutInfo.codable);

        customCodeview(textarea);

        const codable = textarea.data("summernote").layoutInfo.codable;
        codable.val("new value");
        codable.trigger("change");

        expect(textarea.val()).toBe("new value");
    });
});
