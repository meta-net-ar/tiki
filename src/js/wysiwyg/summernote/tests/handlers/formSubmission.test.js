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
        $.fn.validate = vi.fn();

        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.clearAllMocks();
        vi.resetAllMocks();
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

        expect(form.data("should-parse-editor-data")).toBe(true);

        // When the form is submitted
        form.trigger("submit");

        expect(parseDataSpy).toHaveBeenCalledWith(textarea, expect.any(Function));

        parseDataSpy.mock.calls[0][1]();

        expect(form.data("should-parse-editor-data")).toBe(false);
        expect(form.data("submitted")).toBe(false);
        expect(textarea.data("is-submitting")).toBe(true);
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

        expect(form.data("should-parse-editor-data")).toBe(false);
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
        expect(textarea.data("is-submitting")).toBe(true);
    });

    test("do not parse the content if the form does not have the submitted flag, but has some validation pending request", () => {
        // Given that the text editor is rendered within a form
        const textarea = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea);
        form.data("submitted", false);
        $.fn.validate.mockReturnValue({
            pendingRequest: 1,
        });

        $("body").append(form);

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData");

        formSubmission(textarea);

        form.trigger("submit");

        expect(parseDataSpy).not.toHaveBeenCalled();
        expect(textarea.data("is-submitting")).toBe(true);
    });

    test("do not parse the content if the form has the should-parse-editor-data flag set to false", () => {
        // Given that the text editor is rendered within a form
        const textarea = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea);
        form.data("should-parse-editor-data", false);
        $("body").append(form);

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData");

        formSubmission(textarea);

        form.trigger("submit");

        expect(parseDataSpy).not.toHaveBeenCalled();
        expect(textarea.data("is-submitting")).toBe(true);
    });

    test("do not submit the form when the parseing has been requested many times, and yet all to be completed", () => {
        const textarea = $("<textarea></textarea>");
        const textarea2 = $("<textarea></textarea>");
        const form = $("<form></form>").append(textarea).append(textarea2);
        $("body").append(form);

        const formSubmitSpy = vi.fn();
        form.get(0).submit = formSubmitSpy;

        const parseDataSpy = vi.spyOn(formSubmissionHelpers, "parseData").mockImplementationOnce((_, cb) => {
            setTimeout(cb, 200);
        });

        parseDataSpy.mockImplementationOnce((_, cb) => {
            setTimeout(cb, 400);
        });

        formSubmission(textarea);
        formSubmission(textarea2);

        form.trigger("submit");

        vi.advanceTimersByTime(200);

        expect(parseDataSpy).toHaveBeenCalledWith(textarea, expect.any(Function));
        expect(parseDataSpy).toHaveBeenCalledWith(textarea2, expect.any(Function));
        expect(textarea.data("is-submitting")).toBe(true);
        expect(form.data("should-parse-editor-data")).toBe(true);
        expect(formSubmitSpy).not.toHaveBeenCalled();

        vi.advanceTimersByTime(200);
        expect(textarea2.data("is-submitting")).toBe(true);
        expect(form.data("should-parse-editor-data")).toBe(false);
        expect(formSubmitSpy).toHaveBeenCalled();

        expect(textarea.data("is-submitting")).toBe(true);
    });
});
