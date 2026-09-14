import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import replace from "../../actions/replace";
import * as Helpers from "../../actions/replace.helpers";

describe("replace action", () => {
    beforeAll(() => {
        window.$ = $;
        $.openModal = vi.fn();
        window.tr = vi.fn();
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    test("renders correctly the replace modal", () => {
        // mock summernote editor context
        const context = {
            layoutInfo: {
                editable: $("<div></div>"),
            },
        };
        const expectedTranslatedText = "translated text";
        window.tr.mockReturnValue(expectedTranslatedText);

        replace(context);

        expect(window.tr).toHaveBeenCalledWith("Find and replace");
        expect($.openModal).toHaveBeenCalledWith({
            title: expectedTranslatedText,
            size: "modal-sm",
            backdrop: false,
            content: expect.any(String),
            buttons: [
                {
                    text: expectedTranslatedText,
                    type: "primary btn-sm",
                    onClick: expect.any(Function),
                },
                {
                    text: expectedTranslatedText,
                    type: "primary btn-sm",
                    onClick: expect.any(Function),
                },
            ],
            open: expect.any(Function),
        });

        expect(window.tr).toHaveBeenCalledWith("Replace");
        expect(window.tr).toHaveBeenCalledWith("Replace all");

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);
        const formGroups = actualContent.find(".form-group");
        expect(formGroups.length).toBe(3);
        const findInput = formGroups.eq(0).find("input");
        expect(findInput.attr("id")).toBe("find");
        expect(findInput.is("[autofocus]")).toBe(true);
        expect(formGroups.eq(0).find("label").text()).toBe(expectedTranslatedText);
        expect(window.tr).toHaveBeenCalledWith("Find");

        expect(formGroups.eq(1).find("input").attr("id")).toBe("replace");
        expect(formGroups.eq(1).find("label").text()).toBe(expectedTranslatedText);
        expect(window.tr).toHaveBeenCalledWith("Replace");

        const formCheck = formGroups.eq(2).find(".form-check");
        expect(formCheck.find("input").attr("id")).toBe("caseSensitive");
        expect(formCheck.find("label").text()).toBe(expectedTranslatedText);
        expect(window.tr).toHaveBeenCalledWith("Case sensitive");
    });

    test.each([
        ["signle match", "<mark>foo</mark>", "bar"],
        ["multiple matches", "<mark>foo</mark> <mark>foo</mark>", "bar <mark>foo</mark>"],
    ])(`correctly handle the replace button click for %s`, (_, editableHtml, expectHtml) => {
        const context = {
            layoutInfo: {
                editable: $(`<div>${editableHtml}</div>`),
            },
        };
        replace(context);

        $("body").html($.openModal.mock.calls[0][0].content);

        $("#replace").val("bar");

        const replaceButton = $.openModal.mock.calls[0][0].buttons[0];
        replaceButton.onClick();

        expect(context.layoutInfo.editable.html()).toBe(expectHtml);
    });

    test("correctly handle the replace all button click", () => {
        const context = {
            layoutInfo: {
                editable: $("<div><mark>foo</mark> <mark>foo</mark></div>"),
            },
        };
        replace(context);

        $("body").html($.openModal.mock.calls[0][0].content);

        $("#replace").val("bar");

        const replaceAllButton = $.openModal.mock.calls[0][0].buttons[1];
        replaceAllButton.onClick();

        expect(context.layoutInfo.editable.html()).toBe("bar bar");
    });

    test.each([[0], [1]])("should not do any replacement when the replace input is empty", (buttonIndex) => {
        const context = {
            layoutInfo: {
                editable: $("<div><mark>foo</mark></div>"),
            },
        };
        replace(context);

        $("body").html($.openModal.mock.calls[0][0].content);

        $("#replace").val("");

        const button = $.openModal.mock.calls[0][0].buttons[buttonIndex];
        button.onClick();

        expect(context.layoutInfo.editable.html()).toBe("<mark>foo</mark>");
    });

    test("reset markers when the modal is closed", () => {
        const context = {
            layoutInfo: {
                editable: $("<div><mark>foo</mark></div>"),
            },
        };

        const resetMarkersSpy = vi.spyOn(Helpers, "resetMarkers");

        const modalMock = $("<div class='modal'>modal</div>");
        $("body").append(modalMock);

        replace(context);

        $.openModal.mock.calls[0][0].open.call(modalMock.get(0));

        modalMock.trigger("hidden.bs.modal");

        expect(resetMarkersSpy).toHaveBeenCalledWith(context);
    });

    test("set markers when the find input is updated", () => {
        const context = {
            layoutInfo: {
                editable: $("<div><mark>foo</mark></div>"),
            },
        };

        const setMarkersSpy = vi.spyOn(Helpers, "setMarkers");

        replace(context);

        $("body").html($.openModal.mock.calls[0][0].content);

        $.openModal.mock.calls[0][0].open();

        $("#find").val("bar").trigger("keyup");

        expect(setMarkersSpy).toHaveBeenCalledWith(context, "bar");
    });

    test("set markers when the case sensitive checkbox is updated", () => {
        const context = {
            layoutInfo: {
                editable: $("<div><mark>foo</mark></div>"),
            },
        };

        const setMarkersSpy = vi.spyOn(Helpers, "setMarkers");

        replace(context);

        $("body").html($.openModal.mock.calls[0][0].content);

        $.openModal.mock.calls[0][0].open();

        $("#caseSensitive").prop("checked", true).trigger("change");

        expect(setMarkersSpy).toHaveBeenCalledWith(context, "");
    });
});
