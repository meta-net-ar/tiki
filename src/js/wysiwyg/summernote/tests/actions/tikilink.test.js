import { afterEach, describe, expect, test, vi } from "vitest";
import tikilink from "../../actions/tikilink";
import $ from "jquery";

describe("tikilink action", () => {
    beforeAll(() => {
        window.$ = $;
        $.openModal = vi.fn();
        window.tr = vi.fn();
        window.autocomplete = vi.fn();
        window.insertAt = vi.fn();
        window.jqueryTiki = {};
        $.closeModal = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    test("renders correctly the Wiki ling modal", () => {
        const givenContext = {
            $note: $("<div>"),
        };
        const expectedTranslatedText = "translated text";
        window.tr.mockReturnValue(expectedTranslatedText);

        tikilink(givenContext);

        expect($.openModal).toHaveBeenCalledWith({
            title: expectedTranslatedText,
            size: "modal-sm",
            content: expect.any(String),
            buttons: [
                {
                    text: expectedTranslatedText,
                    type: "primary btn-sm",
                    onClick: expect.any(Function),
                },
            ],
            open: expect.any(Function),
        });
        expect(window.tr).toHaveBeenCalledWith("Wiki Link");
        expect(window.tr).toHaveBeenCalledWith("Insert");

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);

        const formGroups = actualContent.find(".form-group");
        expect(formGroups.length).toBe(2);

        expect(formGroups.eq(0).find("label").text()).toBe(expectedTranslatedText);
        expect(window.tr).toHaveBeenCalledWith("Label");
        expect(formGroups.eq(0).find("input").attr("id")).toBe("label");

        expect(formGroups.eq(1).find("label").text()).toBe(expectedTranslatedText);
        expect(window.tr).toHaveBeenCalledWith("Page");
        expect(formGroups.eq(1).find("input").attr("id")).toBe("page");
    });

    test("apply the autcompletion to the page input when the modal is opened", () => {
        $("body").append($("<input id='page'>"));
        tikilink({});
        $.openModal.mock.calls[0][0].open();
        expect(window.autocomplete).toHaveBeenCalledWith($("#page")[0], "pagename");
    });

    test.each([
        ["the sefurl preference is enabled", true, "label", "page", "label"],
        ["the sefurl preference is disabled", false, "label", "tiki-index.php?page=page", "label"],
        ["the label is empty", false, "", "tiki-index.php?page=page", "page"],
    ])(`correctly handle the insert button click when %s`, (_, sefurl, givenLabel, expectedLink, expectedLinkLabel) => {
        window.jqueryTiki.sefurl = sefurl;

        const givenContext = {
            $note: $("<div id='note'>"),
        };
        $("body").html(`<input id='label' value='${givenLabel}'/><input id='page' value='page' />`);

        tikilink(givenContext);

        $.openModal.mock.calls[0][0].buttons[0].onClick();

        expect(window.insertAt).toHaveBeenCalledWith(givenContext.$note.attr("id"), `<a href="${expectedLink}">${expectedLinkLabel}</a>`);
        expect($.closeModal).toHaveBeenCalled();
    });

    test("correctly handle the insert button click when the page is empty", () => {
        const givenContext = {
            $note: $("<div id='note'>"),
        };
        $("body").html(`<input id='label' value='label'/><input id='page' value='' />`);

        tikilink(givenContext);

        $.openModal.mock.calls[0][0].buttons[0].onClick();

        expect(window.insertAt).not.toHaveBeenCalled();
        expect($.closeModal).not.toHaveBeenCalled();
    });
});
