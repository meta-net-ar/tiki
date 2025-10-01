import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import createCustomButton from "../createCustomButton";

describe("createCustomButton", () => {
    beforeAll(() => {
        window.$ = $;
        $.summernote = {
            ui: {
                button: vi.fn(() => ({
                    render: vi.fn(),
                })),
            },
        };
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    test("create a custom button with the given tool", () => {
        const givenTool = {
            icon: "colorIcon",
            label: "Color",
            callback: "colorCb",
        };

        const rendererOutpput = createCustomButton(givenTool)({});

        expect($.summernote.ui.button).toHaveBeenCalledWith({
            contents: "colorIcon",
            tooltip: "Color",
            click: expect.any(Function),
        });
        const expectedRenderedOutput = $("<div class='custom-btn-wrapper'></div>");
        expect($.summernote.ui.button.mock.results[0].value.render).toHaveBeenCalledWith(expectedRenderedOutput);
        expect(rendererOutpput).toEqual(expectedRenderedOutput);
    });

    test("button contents should be html entities decoded", () => {
        const givenTool = {
            icon: "&lt;span&gt;Color&lt;/span&gt;",
            label: "Color",
            callback: "colorCb",
        };

        createCustomButton(givenTool)({});

        expect($.summernote.ui.button).toHaveBeenCalledWith({
            contents: "<span>Color</span>",
            tooltip: "Color",
            click: expect.any(Function),
        });
    });

    window.colorCb = vi.fn();
    test.each([
        ["string callback", "colorCb"],
        ["function callback", vi.fn()],
    ])("call the tool's callback when the button is clicked", (_, givenCallback) => {
        const givenTool = {
            icon: "colorIcon",
            label: "Color",
            callback: givenCallback,
        };
        const context = {};
        createCustomButton(givenTool)(context);

        $.summernote.ui.button.mock.calls[0][0].click();

        if (typeof givenCallback === "string") {
            expect(window[`${givenCallback}`]).toHaveBeenCalled();
        } else {
            expect(givenCallback).toHaveBeenCalled();
        }
    });

    window.renderCb = vi.fn();
    test.each([
        ["string renderCallback", "renderCb"],
        ["function renderCallback", vi.fn()],
    ])("call the tool's renderCallback when the button is rendered", (_, givenRenderCallback) => {
        const givenTool = {
            icon: "colorIcon",
            label: "Color",
            renderCallback: givenRenderCallback,
        };
        const wrapper = $("<div></div>");
        createCustomButton(givenTool)(wrapper);

        if (typeof givenRenderCallback === "string") {
            expect(window[`${givenRenderCallback}`]).toHaveBeenCalled();
        } else {
            expect(givenRenderCallback).toHaveBeenCalled();
        }
    });
});
