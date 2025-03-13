import { afterEach, describe, expect, test, vi } from "vitest";
import { parseData } from "../../handlers/formSubmission.helpers";
import $ from "jquery";

describe("formSubmission helpers", () => {
    beforeAll(() => {
        window.$ = $;
        window.tr = vi.fn((str) => str);
        $.fn.tikiModal = vi.fn();
        $.service = vi.fn(() => `parser/endoint`);
        $.fn.showError = vi.fn();
        $.fn.summernote = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    describe("parseData", () => {
        test.each([
            ["towiki", true, "<h1>Hello World!</h1>", "Hello World!"],
            ["tohtml", false, "Hello World!", "<h1>Hello World!</h1>"],
        ])("sucessfully parse the data %s", (_, toWiki, givenValue, expectedValue) => {
            // Given that the text editor is rendered within a form
            const textarea = $("<textarea></textarea>");
            $("body").append(textarea);

            // mock the editor interface
            const summernoteDataMock = {
                code: vi.fn(() => givenValue),
                layoutInfo: {
                    editor: textarea,
                },
            };
            textarea.data("summernote", summernoteDataMock);

            const ajaxSpy = vi.spyOn($, "ajax").mockImplementation(() => {});

            const successCallback = vi.fn();

            parseData(textarea, successCallback, toWiki);

            expect($.fn.tikiModal).toHaveBeenCalledWith("Please wait...");
            // And the parse request should be sent to convert the content to wiki format
            expect($.service).toHaveBeenCalledWith("edit", toWiki ? "towiki" : "tohtml");

            const expectedPayload = { data: givenValue };
            if (!toWiki) {
                expectedPayload.htmleditor = 1;
            }

            expect(ajaxSpy).toHaveBeenCalledWith({
                url: "parser/endoint",
                type: "POST",
                dataType: "json",
                data: expectedPayload,
                success: expect.any(Function),
                error: expect.any(Function),
                complete: expect.any(Function),
            });
            ajaxSpy.mock.calls[0][0].success({ data: expectedValue });

            expect(summernoteDataMock.code).toHaveBeenCalledWith(expectedValue);
            expect(successCallback).toHaveBeenCalled();

            // upon request completion
            ajaxSpy.mock.calls[0][0].complete();

            expect($.fn.tikiModal).toHaveBeenCalledTimes(2);
            expect($.fn.tikiModal.mock.calls[1][0]).toBe(undefined);
        });

        test("succesfully parse the data when no callback is provided", () => {
            // Given that the text editor is rendered within a form
            const textarea = $("<textarea></textarea>");
            $("body").append(textarea);

            // mock the editor interface
            const summernoteDataMock = {
                code: vi.fn(() => "<h1>Hello World!</h1>"),
                layoutInfo: {
                    editor: textarea,
                },
            };
            textarea.data("summernote", summernoteDataMock);

            const ajaxSpy = vi.spyOn($, "ajax").mockImplementation(() => {});

            parseData(textarea);

            ajaxSpy.mock.calls[0][0].success({ data: "Hello World!" });

            expect(summernoteDataMock.code).toHaveBeenCalledWith("Hello World!");
        });

        test("show an error message when the parse request fails", () => {
            const textarea = $("<textarea></textarea>");
            $("body").append(textarea);

            // mock the editor interface
            textarea.data("summernote", {
                code: vi.fn(() => "<h1>Hello World!</h1>"),
                layoutInfo: {
                    editor: textarea,
                },
            });

            const ajaxSpy = vi.spyOn($, "ajax").mockImplementation(() => {});
            const successCallback = vi.fn();

            parseData(textarea, successCallback);

            ajaxSpy.mock.calls[0][0].error();
            expect($.fn.showError).toHaveBeenCalledWith("An error occured while parsing the content.");

            ajaxSpy.mock.calls[0][0].complete();

            expect($.fn.tikiModal).toHaveBeenCalledTimes(2);
            expect(successCallback).not.toHaveBeenCalled();
        });
    });
});
