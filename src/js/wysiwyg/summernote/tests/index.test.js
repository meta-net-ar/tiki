import { afterEach, beforeAll, beforeEach, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import initSummernote, { loadLanguage } from "..";
import * as formatTikiToolbarsModule from "../formatTikiToolbars";
import * as Handlers from "../handlers/index";
import showMessage from "../../../vue-widgets/element-plus-ui/src/utils/showMessage";
import { parseData } from "../handlers/formSubmission.helpers";

vi.mock("summernote", () => {
    $.fn.summernote = vi.fn();
    return {};
});

vi.mock("../handlers/index", () => ({
    formSubmission: vi.fn(),
    pluginEdit: vi.fn(),
    customCodeview: vi.fn(),
    dirtyCheck: vi.fn(),
}));

vi.mock("../handlers/formSubmission.helpers", () => ({
    parseData: vi.fn(),
}));

vi.mock("../../../vue-widgets/element-plus-ui/src/utils/showMessage", () => {
    return {
        default: vi.fn(),
    };
});

describe("initSummernote", () => {
    beforeAll(() => {
        window.$ = $;
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
        givenTextarea.data("summernote", {
            layoutInfo: {
                toolbar: $("<div></div>"),
            },
        });

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

        vi.spyOn(formatTikiToolbarsModule, "default").mockReturnValue({
            tools: expectedFormattedTools,
            icons: expectedIcons,
            customButtons: expectedCustomButtons,
        });

        initSummernote(id, givenToolbar, { lang: "en-US", height: 300 });

        expect(formatTikiToolbarsModule.default).toHaveBeenCalledWith(givenToolbar);
        expect(givenTextarea.summernote).toHaveBeenCalledWith({
            lang: "en-US",
            height: 300,
            toolbar: expectedFormattedTools,
            icons: expectedIcons,
            buttons: expectedCustomButtons,
            callbacks: {
                onInit: expect.any(Function),
                onKeydown: expect.any(Function),
                onCodeviewToggled: expect.any(Function),
                onDestroy: expect.any(Function),
            },
        });

        givenTextarea.summernote.mock.calls[0][0].callbacks.onInit.call(givenTextarea[0]);

        parseData.mock.calls[0][1].call(givenTextarea[0]);

        expect(Handlers.formSubmission).toHaveBeenCalledWith(givenTextarea);
        expect(Handlers.dirtyCheck).toHaveBeenCalledWith(givenTextarea);
        expect(Handlers.pluginEdit).toHaveBeenCalledWith(id);
    });

    test("on init, should unwrap custom buttons", () => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        const toolbar = $(`<div>
            <div class="custom-btn-wrapper">
                <button>tool1</button>
                <span>tool1 custom element</span>
            </div>
            <div class="custom-btn-wrapper">
                <button>tool2</button>
                <span>tool2 custom element</span>
            </div>
        </div>`);
        givenTextarea.data("summernote", {
            layoutInfo: {
                toolbar,
            },
        });
        $("body").append(givenTextarea);
        const givenToolbar = [
            {
                icon: "colorIcon",
                label: "Color",
                callback: "colorCb",
            },
        ];

        vi.spyOn(formatTikiToolbarsModule, "default");

        initSummernote(id, givenToolbar, { lang: "en-US" });

        givenTextarea.summernote.mock.calls[0][0].callbacks.onInit.call(givenTextarea[0]);

        expect(toolbar.html()).toMatchSnapshot();
    });

    test.each([
        ["not parse but call the dirtyCheck handler", true],
        ["parse and call the dirtyCheck handler afterward", false],
    ])("on init, should %s when editing inline is %s", (_, inline) => {
        const id = "foo";
        const givenTextarea = $(`<textarea id="${id}"></textarea>`);
        givenTextarea.data("summernote", {
            layoutInfo: {
                toolbar: $("<div></div>"),
            },
        });
        $("body").append(givenTextarea);

        initSummernote(id, [], { lang: "en-US", inline });

        givenTextarea.summernote.mock.calls[0][0].callbacks.onInit.call(givenTextarea[0]);

        if (inline) {
            expect(parseData).not.toHaveBeenCalled();
            expect(Handlers.dirtyCheck).toHaveBeenCalledWith(givenTextarea);
        } else {
            expect(parseData).toHaveBeenCalledWith(givenTextarea, expect.any(Function), false);
            parseData.mock.calls[0][1].call(givenTextarea[0]);
            expect(Handlers.dirtyCheck).toHaveBeenCalledWith(givenTextarea);
        }
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
        expect(showMessage).not.toHaveBeenCalled();
    });

    test("should call the callback and show the error message when the language fails to load", () => {
        const callback = vi.fn();

        loadLanguage("/foo", callback);

        scriptElement.onerror();

        expect(callback).toHaveBeenCalled();
        expect(showMessage).toHaveBeenCalledWith("Failed to load the language file", "error");
    });
});
