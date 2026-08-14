import { beforeEach, describe, expect, test, vi } from "vitest";
import formatTikiToolbars, { CUSTOM_ACTIONS } from "../formatTikiToolbars";
import * as createCustomButtonModule from "../createCustomButton";

vi.mock("../actions/admintoolbar", () => {
    return {
        default: vi.fn(),
    };
});

describe("formatTikiToolbars", () => {
    beforeEach(() => {
        vi.resetAllMocks();
        vi.clearAllMocks();
    });

    test.each([
        [
            [[[{ token: "color", icon: "colorIcon" }, { token: "font", icon: "fontIcon" }, "-", { token: "style", icon: "styleIcon" }]]],
            [
                ["group-0", ["color", "font"]],
                ["last-1", ["style"]],
            ],
            { color: "colorIcon", font: "fontIcon", style: "styleIcon" },
            {},
        ],
        [
            [[[{ token: "color", icon: "colorIcon" }, "-", { token: "font", icon: "fontIcon" }], [{ token: "style", icon: "styleIcon" }]]],
            [
                ["group-0", ["color"]],
                ["last-1", ["font"]],
                ["last-2", ["style"]],
            ],
            { color: "colorIcon", font: "fontIcon", style: "styleIcon" },
            {},
        ],
        [[[[{ token: "color" }, { token: "font" }, { token: "style" }]]], [["last-0", ["color", "font", "style"]]], {}, {}],
    ])(
        "generate toolbar given Tiki tools that are in the default summernote tools set",
        (givenTikiFormattedTools, expectedTools, expectedIcons, expectedCustomButtons) => {
            const result = formatTikiToolbars(givenTikiFormattedTools);

            expect(result.tools).toEqual(expectedTools);
            expect(result.icons).toEqual(expectedIcons);
            expect(result.customButtons).toEqual(expectedCustomButtons);
        }
    );

    test.each([
        ["defined callback", { token: "tikiplugin", type: "Wikiplugin", callback: "pluginCb" }],
        ["custom action", { token: "tikilink", type: "Wikiplugin" }, CUSTOM_ACTIONS.tikilink],
    ])("generate toolbar given a wikiplugin Tiki tool with a %s", (_, givenTool, expectedCustomAction) => {
        const givenTikiFormattedTools = [[[givenTool]]];
        const expectedTools = [["last-0", [givenTool.token]]];
        const createCustomButtonSpy = vi.spyOn(createCustomButtonModule, "default").mockReturnValue("pluginCustomButton");
        const expectedButtonParams = { ...givenTool };

        const result = formatTikiToolbars(givenTikiFormattedTools);

        if (expectedCustomAction) {
            expectedButtonParams.callback = expectedCustomAction;
        }

        expect(result.tools).toEqual(expectedTools);
        expect(createCustomButtonSpy).toBeCalledWith(expectedButtonParams);
        expect(result.customButtons).toEqual({ [givenTool.token]: "pluginCustomButton" });
    });

    test.each([
        ["defined callback", { token: "tikiimage", callback: "tikiimageCb" }],
        ["custom action", { token: "tikilink" }, CUSTOM_ACTIONS.tikilink],
    ])("generate toolbar given a Tiki custom tool with a %s", (_, givenTool, expectedCustomAction) => {
        const givenTikiFormattedTools = [[[givenTool]]];
        const expectedTools = [["last-0", [givenTool.token]]];
        const createCustomButtonSpy = vi.spyOn(createCustomButtonModule, "default").mockReturnValue("customButton");
        const expectedButtonParams = { ...givenTool };

        const result = formatTikiToolbars(givenTikiFormattedTools);

        if (expectedCustomAction) {
            expectedButtonParams.callback = expectedCustomAction;
        }

        expect(result.tools).toEqual(expectedTools);
        expect(createCustomButtonSpy).toBeCalledWith(expectedButtonParams);
        expect(result.customButtons).toEqual({ [givenTool.token]: "customButton" });
    });
});
