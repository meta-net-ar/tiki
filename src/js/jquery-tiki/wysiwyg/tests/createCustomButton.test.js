import { beforeAll, describe, expect, test, vi } from "vitest";
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

    test("create a custom button with the given tool", () => {
        const expectedRenderedButton = "<button/>";
        $.summernote.ui.button = vi.fn(() => ({
            render: vi.fn().mockReturnValue(expectedRenderedButton),
        }));
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
        expect(rendererOutpput).toBe(expectedRenderedButton);
    });

    test("button contents should be hmml entities decoded", () => {
        const expectedRenderedButton = "<button/>";
        $.summernote.ui.button = vi.fn(() => ({
            render: vi.fn().mockReturnValue(expectedRenderedButton),
        }));
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
        $.summernote.ui.button = vi.fn(({ click }) => ({
            render: () => ({ click }),
        }));

        const givenTool = {
            icon: "colorIcon",
            label: "Color",
            callback: givenCallback,
        };
        const context = {};
        const button = createCustomButton(givenTool)(context);

        button.click();

        if (typeof givenCallback === "string") {
            expect(window[`${givenCallback}`]).toHaveBeenCalled();
        } else {
            expect(givenCallback).toHaveBeenCalled();
        }
    });
});
