import { afterEach, beforeAll, beforeEach, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import initSummernote, { loadLanguage } from "..";
import * as formatTikiToolbarsModule from "../formatTikiToolbars";
import * as Handlers from "../handlers/index";

vi.mock("summernote", () => {
    $.fn.summernote = vi.fn();
    return {};
});

vi.mock("../handlers/index", () => ({
    formSubmission: vi.fn(),
    pluginEdit: vi.fn(),
    customCodeview: vi.fn(),
}));

describe("initSummernote", () => {
    beforeAll(() => {
        window.$ = $;
        $.notify = vi.fn();
        window.renderUserMentionModal = vi.fn();
    });

    beforeEach(() => {
        $("body").empty();
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    test("should initialize summernote", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        $("body").append(givenTextarea);
        const givenToolbar = ["color", "font"];
        const expectedFormattedTools = [["group", ["color", "font"]]];
        const expectedIcons = {
            color: "fa fa-font",
            font: "fa fa-font",
        };
        const expectedCustomButtons = {
            plugin: function () {},
        };
        const expectedRenderCallbacks = ["plugin"];
        window.plugin = vi.fn();

        vi.spyOn(formatTikiToolbarsModule, "default").mockReturnValue({
            tools: expectedFormattedTools,
            icons: expectedIcons,
            customButtons: expectedCustomButtons,
            renderCallbacks: expectedRenderCallbacks,
        });

        initSummernote(id, givenToolbar, "en-US");

        expect(formatTikiToolbarsModule.default).toHaveBeenCalledWith(givenToolbar);
        expect(givenTextarea.summernote).toHaveBeenCalledWith({
            lang: "en-US",
            toolbar: expectedFormattedTools,
            icons: expectedIcons,
            buttons: expectedCustomButtons,
            callbacks: {
                onInit: expect.any(Function),
                onKeydown: expect.any(Function),
                onCodeviewToggled: expect.any(Function),
            },
        });

        givenTextarea.summernote.mock.calls[0][0].callbacks.onInit();

        expect(plugin).toHaveBeenCalled();
        expect(Handlers.formSubmission).toHaveBeenCalledWith(givenTextarea);
        expect(Handlers.pluginEdit).toHaveBeenCalledWith(id);
    });

    test("should encode quotes in data-syntax attributes found in the textarea html value", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`).val(`<div data-syntax="{QUOTE(replyto="John Doe")}lorem{QUOTE}"></div>`);
        $("body").append(givenTextarea);

        initSummernote(id, [], "en-US");

        expect(givenTextarea.val()).toBe(`<div data-syntax="{QUOTE(replyto=&quot;John Doe&quot;)}lorem{QUOTE}"></div>`);
    });

    test("should encode quotes in data-syntax attributes found in the target html value", () => {
        const id = "foo";
        const givenElement = $(`<div id="${id}"></div>`).append(`<div data-syntax='{QUOTE(replyto="John Doe")}lorem{QUOTE}'></div>`);
        $("body").append(givenElement);

        initSummernote(id, [], "en-US");

        expect(givenElement.html()).toBe(`<div data-syntax="{QUOTE(replyto=&quot;John Doe&quot;)}lorem{QUOTE}"></div>`);
    });

    test("should render the user mention modal when the @ key is pressed", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        $("body").append(givenTextarea);

        initSummernote(id, [], "en-US");

        const givenEvent = { key: "@", preventDefault: vi.fn() };
        givenTextarea.summernote.mock.calls[0][0].callbacks.onKeydown(givenEvent);

        expect(window.renderUserMentionModal).toHaveBeenCalledWith(id);
        expect(givenEvent.preventDefault).toHaveBeenCalled();
    });

    test("should not render the user mention modal when a key other than @ is pressed", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        $("body").append(givenTextarea);

        initSummernote(id, [], "en-US");

        const givenEvent = { key: "a", preventDefault: vi.fn() };
        givenTextarea.summernote.mock.calls[0][0].callbacks.onKeydown(givenEvent);

        expect(window.renderUserMentionModal).not.toHaveBeenCalled();
        expect(givenEvent.preventDefault).not.toHaveBeenCalled();
    });

    test("should call the customCodeview handler when the codeview is toggled", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        $("body").append(givenTextarea);

        initSummernote(id, [], "en-US");

        givenTextarea.summernote.mock.calls[0][0].callbacks.onCodeviewToggled();

        expect(Handlers.customCodeview).toHaveBeenCalledWith(givenTextarea);
    });
});

describe("loadLanguage", () => {
    const scriptElement = document.createElement("script");
    scriptElement.setAttribute = vi.fn();
    vi.spyOn(document, "createElement").mockImplementation(() => scriptElement);

    test("should load the language file and call the callback on success", () => {
        const callback = vi.fn();

        loadLanguage("/foo", callback);

        scriptElement.onload();

        expect(callback).toHaveBeenCalled();
        expect($.notify).not.toHaveBeenCalled();
    });

    test("should call the callback and $.notify on error", () => {
        const callback = vi.fn();

        loadLanguage("/foo", callback);

        scriptElement.onerror();

        expect(callback).toHaveBeenCalled();
        expect($.notify).toHaveBeenCalledWith("Failed to load the language file");
    });
});
