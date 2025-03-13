import { afterEach, describe, expect, test, vi } from "vitest";
import inlineEdit from "../inlineEdit";
import $ from "jquery";
import initSummernote from "../initSummernote";

vi.mock("../initSummernote.js", () => {
    return {
        default: vi.fn((id) => {
            const target = $(`#${id}`);
            target.data("summernote", {
                layoutInfo: {
                    editor: $("<div></div>").attr("id", "editor-" + id),
                },
            });
            target.after(target.data("summernote").layoutInfo.editor);
        }),
    };
});

describe("inlineEdit", () => {
    beforeAll(() => {
        window.$ = $;
        window.tr = vi.fn().mockReturnValue("Translated");

        $.notify = vi.fn();
        $.service = vi.fn().mockReturnValue("service-url");

        $.fn.summernote = vi.fn();
        $.fn.tikiModal = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    describe("inlineEdit toggler button", () => {
        test("should show appropriate content based on the inline editor toggler state", () => {
            const inlineEditToggler = $("<button></button>").attr("id", "wysiwyg_inline_edit").addClass("active");
            inlineEditToggler.appendTo("body");

            const givenToggleOnIcon = $("<span></span>").addClass("icon-toggle-on d-none");
            givenToggleOnIcon.appendTo(inlineEditToggler);
            const givenToggleOffIcon = $("<span></span>").addClass("icon-toggle-off");
            givenToggleOffIcon.appendTo(inlineEditToggler);

            const pageData = $("<div></div>").attr("id", "page-data");
            pageData.appendTo("body");

            const inlineEditorContent = $("<div></div>").addClass("inline-editor-content");
            inlineEditorContent.appendTo(pageData);
            const content = $("<div></div>").addClass("content");
            content.appendTo(pageData);

            inlineEdit([], "en", "page");

            expect(inlineEditorContent.css("display")).toBe("block");
            expect(content.css("display")).toBe("none");

            // When deactivating the inline editor
            inlineEditToggler.trigger("click");

            expect(inlineEditorContent.css("display")).toBe("none");
            expect(content.css("display")).toBe("block");
            expect(inlineEditToggler.hasClass("active")).toBe(false);
            expect(inlineEditToggler.hasClass("highlight")).toBe(false);

            expect(givenToggleOnIcon.css("display")).toBe("none");
            expect(givenToggleOffIcon.css("display")).not.toBe("none");

            // When activating the inline editor
            inlineEditToggler.trigger("click");

            expect(inlineEditorContent.css("display")).toBe("block");
            expect(content.css("display")).toBe("none");

            expect(inlineEditToggler.hasClass("active")).toBe(true);
            expect(inlineEditToggler.hasClass("highlight")).toBe(true);
            expect(givenToggleOnIcon.css("display")).not.toBe("none");
            expect(givenToggleOffIcon.css("display")).toBe("none");
        });
    });

    describe("Editing inline content", () => {
        test("should initialize summernote for each inline editor content", () => {
            const givenPageData = $(`
                <div id="page-data">
                    <div class="inline-editor-content">
                        <span class="icon_edit_section"></span>
                        <h1>Heading <a href="#heading" class="heading-link">#</a></h1>
                        <h2>Subheading</h2>
                        <div class="editplugin"></div>
                        <div>Content</div>
                    </div>
                </div>`);
            givenPageData.appendTo("body");

            inlineEdit([], "en", "page");

            expect(givenPageData.find(".heading-link").length).toBe(0);
            const inlineEditors = givenPageData.find(".inline-editor");
            expect(inlineEditors.length).toBe(3);
            expect(inlineEditors.eq(0).html()).toBe("<h1>Heading </h1>");
            expect(inlineEditors.eq(1).html()).toBe("<h2>Subheading</h2>");
            expect(inlineEditors.eq(2).html()).toBe("<div>Content</div>");

            inlineEditors.each(function (index) {
                $(this).children().first().trigger("click");
                expect(initSummernote).toHaveBeenCalledWith(this.id, [], "en");

                const editor = $(`#editor-${this.id}`);
                expect(editor.length).toBe(1);
                const actions = $(`#editor-${this.id} + .inline-editor-actions`);
                expect(actions.length).toBe(1);
                expect(actions.find("button").length).toBe(2);
                expect(actions.find("button").eq(0).text()).toBe("Translated");
                expect(actions.find("button").eq(1).text()).toBe("Translated");
                expect(window.tr).toHaveBeenNthCalledWith(1, "Save");
                expect(window.tr).toHaveBeenNthCalledWith(2, "Cancel");

                const initalValue = $(this).html();

                // When the value has been changed
                $(this).html("New content");

                // Destroy the editor when the cancel button is clicked
                actions.find("button").eq(0).trigger("click");
                expect($(this).summernote).toHaveBeenCalledWith("destroy");
                expect($(`#editor-${this.id} + .inline-editor-actions`).length).toBe(0);
                expect($(this).html()).toBe(initalValue);

                // Clicking again the content should show the editor
                $(this).children().first().trigger("click");
                expect(initSummernote).toHaveBeenCalledTimes((index + 1) * 2);
            });
        });

        test("should correctly handle saving the content", () => {
            const givenPageData = $(`
                <div id="page-data">
                    <div class="inline-editor-content">
                        <div>Initial content</div>
                    </div>
                </div>`);
            givenPageData.appendTo("body");
            const ajaxFailMock = vi.fn();
            const ajaxDoneMock = vi.fn().mockReturnValue({
                fail: ajaxFailMock,
            });
            const ajaxPostSpy = vi.spyOn($, "post").mockImplementation(() => ({
                done: ajaxDoneMock,
            }));

            inlineEdit([], "en", "page");

            const inlineEditor = givenPageData.find(".inline-editor");

            // Open the editor
            inlineEditor.children().first().trigger("click");

            // Edit the content
            inlineEditor.html("<div>Edited content</div>");

            // Save the content
            $(`#editor-${inlineEditor[0].id} + .inline-editor-actions`).find("button").eq(1).trigger("click");

            expect(inlineEditor.tikiModal).toHaveBeenCalledWith("Translated");
            expect($.service).toHaveBeenCalledWith("edit", "inlinesave");
            expect(ajaxPostSpy).toHaveBeenCalledWith("service-url", {
                data: "<div>Edited content</div>",
                page: "page",
            });

            // When the request is successful
            ajaxDoneMock.mock.calls[0][0]();
            // hide the loading indicator
            expect(inlineEditor.tikiModal).toHaveBeenCalledWith();
            // should close the editor
            expect(inlineEditor.summernote).toHaveBeenCalledWith("destroy");
            // No error message should be shown
            expect($.notify).not.toHaveBeenCalled();

            // When the request fails
            ajaxFailMock.mock.calls[0][0]();
            // hide the loading indicator
            expect(inlineEditor.tikiModal).toHaveBeenCalledWith();
            // show an error message
            expect($.notify).toHaveBeenCalledWith("Translated");
        });
    });
});
