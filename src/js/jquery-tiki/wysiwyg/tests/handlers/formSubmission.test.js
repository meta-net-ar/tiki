import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import formSubmission from "../../handlers/formSubmission";
import * as formSubmissionHelpers from "../../handlers/formSubmission.helpers";

describe("formSubmission handler", () => {
    beforeAll(() => {
        window.$ = $;
        window.tr = vi.fn((str) => str);
        $.fn.tikiModal = vi.fn();
        $.service = vi.fn((service, action) => `${service}/${action}`);
        $.fn.showError = vi.fn();
        $.fn.summernote = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    test("succesfully handle the form submission", () => {
        // Given that the text editor is rendered within a form
        const textarea = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea);
        $("body").append(form);
        form.get(0).submit = vi.fn();

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData").mockImplementation(() => {});

        formSubmission(textarea);

        // When the form is submitted
        form.trigger("submit");

        expect(parseDataSpy).toHaveBeenCalledWith(textarea, expect.any(Function));

        parseDataSpy.mock.calls[0][1]();

        expect(textarea.data("parsed")).toBe(true);
        expect(form.get(0).submit).toHaveBeenCalled();
    });

    test("succesfully resubmit the form when the submission was triggered by a button", () => {
        // Given that the text editor is rendered within a form
        const textarea = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea);
        form.append('<button type="submit"></button>');
        $("body").append(form);

        const submitButton = form.find("button");
        const clickSpy = vi.spyOn(submitButton, "trigger");

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData").mockImplementation(() => {});

        formSubmission(textarea);

        // When the form is submitted
        submitButton.trigger("click");
        // ajaxSpy.mock.calls[0][0].success({ data: "Hello World!" });

        parseDataSpy.mock.calls[0][1]();

        expect(textarea.data("parsed")).toBe(true);
        // And the submit button should fire a click event
        expect(clickSpy).toHaveBeenCalledWith("click");
    });

    test("do not parse the content if the editor is in code view", () => {
        // Given that the text editor is rendered within a form
        const textarea = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea);
        $("body").append(form);

        // mock the editor interface
        $.fn.summernote.mockImplementationOnce(() => true);

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData");

        formSubmission(textarea);

        form.trigger("submit");

        expect(parseDataSpy).not.toHaveBeenCalled();
    });
});
