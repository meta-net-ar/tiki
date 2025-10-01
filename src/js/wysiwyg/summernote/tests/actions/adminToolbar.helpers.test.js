import { afterEach, beforeAll, describe, expect, test, vi } from "vitest";
import { bindToolbarData, handleFilterPlugins, initializeSortable } from "../../actions/adminToolbar.helpers";
import $ from "jquery";
import Sortable from "sortablejs";

vi.mock("sortablejs", () => {
    return {
        default: vi.fn(),
    };
});

describe("adminToolbar.helpers", () => {
    beforeAll(() => {
        window.$ = $;
    });

    afterEach(() => {
        vi.resetAllMocks();
        vi.clearAllMocks();
    });

    describe("bindToolbarData", () => {
        test("should bind data to the existing toolbar buttons", () => {
            const givenToolSet = [
                ["group-0", ["color", "font"]],
                ["last-1", ["style"]],
            ];
            const givenData = [
                { token: "color", name: "Color", label: "Color" },
                { token: "font", name: "Font", label: "Font" },
                { token: "style", name: "Style", label: "Style" },
            ];
            const givenToolbar = $(`
                <div>
                    <div class="note-btn-group note-group-0">
                        <button></button>
                        <button></button>
                    </div>
                    <div class="note-btn-group note-last-1">
                        <div class="note-btn-group">
                            <button></button>
                            <button class="dropdown"></button>
                        </div>
                    </div>
                </div>`);

            bindToolbarData(givenToolSet, givenData, givenToolbar);

            givenData.forEach((active) => {
                const button = givenToolbar.find(`button[data-token="${active.token}"]`);
                expect(button.attr("data-name")).toBe(active.name);
                expect(button.attr("aria-label")).toBe(active.label);
            });

            expect(givenToolbar.html()).toMatchSnapshot();
        });
    });

    describe("handleFilterPlugins", () => {
        test.each([
            ["matching the data-name", "olor-na", ["color"]],
            ["matching the data-name", "o", ["color", "font"]],
            ["matching the data-name", "-name", ["color", "font", "style"]],
            ["matching the data-token", "sty", ["style"]],
            ["matching the data-token", "o", ["color", "font"]],
            ["matching the data-token", "r", ["color"]],
            ["matching the button text", "Pick", ["color"]],
            ["matching the button text", "Choose", ["font"]],
            ["matching the button text", "Change", ["style"]],
            ["matching the button text but in a different case", "change", ["style"]],
        ])("should correctly handle plugins filtering when a user types in the search input, a query %s", (_, input, expectedButtonTokens) => {
            const givenInput = $(`<el-input>`);
            const givenContainer = $(`
                <div>
                    <div class="row"><button data-token="color" data-name="color-name">Pick a color</button></div>
                    <div class="row"><button data-token="font" data-name="font-name">Choose a font</button></div>
                    <div class="row"><button data-token="style" data-name="style-name">Change style</button></div>
                </div>`);
            givenContainer.prepend(givenInput);

            handleFilterPlugins(givenContainer);

            givenInput.val(input).trigger("input");
            const visibleRows = givenContainer.find("div.row").filter(function () {
                return $(this).css("display") !== "none";
            });
            expect(visibleRows.length).toBe(expectedButtonTokens.length);
            expectedButtonTokens.forEach((token) => {
                expect(visibleRows.find(`button[data-token="${token}"]`).length).toBe(1);
            });
        });
    });

    describe("initializeSortable", () => {
        test("should instantiate a Sortable object for the #tools, #plugins, and each of the .note-btn-group elements found in the given container", () => {
            const givenContainer = $(`
                <div>
                    <div class="note-toolbar">
                        <div class="note-btn-group note-group-0"></div>
                        <div class="note-btn-group note-last-1"></div>
                    </div>
                    <div id="tools">
                        <button data-name="Wikiplugin_Color" data-token="color">Pick a color</button>
                        <button data-name="Wikiplugin_Font" data-token="font">Choose a font</button>
                    </div>
                    <div id="plugins">
                        <button data-name="Wikiplugin_Style" data-token="style">Change style</button>
                    </div>
                </div>`);

            initializeSortable(givenContainer);

            expect(Sortable).toHaveBeenCalledTimes(6);
            [givenContainer.find("#tools")[0], givenContainer.find("#plugins")[0]].forEach((el) => {
                expect(Sortable).toHaveBeenCalledWith(el, {
                    sort: false,
                    group: {
                        name: "toolbar",
                        pull: true,
                        put: expect.any(Function),
                    },
                    onAdd: expect.any(Function),
                });
            });
            [
                givenContainer.find(".note-btn-group.note-group-0")[0],
                givenContainer.find(".note-btn-group.note-last-1")[0],
                givenContainer.find(".note-btn-group.btn-group")[0],
            ].forEach((el) => {
                expect(Sortable).toHaveBeenCalledWith(el, {
                    sort: true,
                    group: "toolbar",
                    onAdd: expect.any(Function),
                });
            });

            expect(Sortable).toHaveBeenCalledWith(givenContainer.find(".note-toolbar")[0], {
                sort: true,
                animation: 150,
            });
        });

        test("should disable moving items between the #tools and #plugins containers", () => {
            const givenContainer = $(`
                <div>
                    <div id="tools"></div>
                    <div id="plugins"></div>
                </div>`);

            initializeSortable(givenContainer);

            const tool = givenContainer.find("#tools");
            const plugin = givenContainer.find("#plugins");

            expect(Sortable).toHaveBeenCalledTimes(3);

            Sortable.mock.calls.slice(0, 2).forEach(([, options]) => {
                expect(options.group.put(tool[0], { el: plugin[0] })).toBe(false);
                expect(options.group.put(plugin[0], { el: tool[0] })).toBe(false);
            });
        });

        test("should automatically move the plugin tool to the #plugins container when it is dragged to the #tools container by the user", () => {
            const givenContainer = $(`
                <div>
                    <div id="tools"></div>
                    <div id="plugins"></div>
                </div>`);
            const givenItem = $(`<button data-name="wikiplugin_Color" aria-label="Pick a color">Icon</button>`);

            initializeSortable(givenContainer);

            const tool = givenContainer.find("#tools");
            const plugin = givenContainer.find("#plugins");

            const onAddCb = Sortable.mock.calls[0][1].onAdd;
            onAddCb({ item: givenItem, to: tool[0] });

            expect(tool.find("button").length).toBe(0);
            expect(plugin.find("button").length).toBe(1);

            expect(givenItem.parent().prop("outerHTML")).toMatchSnapshot();
        });

        test("should automatically move the non-plugin tool to the #tools container when it is dragged to the #plugins container by the user", () => {
            const givenContainer = $(`
                <div>
                    <div id="tools"></div>
                    <div id="plugins"></div>
                </div>`);
            const givenItem = $(`<button data-name="color" data-token="color" aria-label="Pick a color">Icon</button>`);

            initializeSortable(givenContainer);

            const tool = givenContainer.find("#tools");
            const plugin = givenContainer.find("#plugins");

            const onAddCb = Sortable.mock.calls[0][1].onAdd;
            onAddCb({ item: givenItem, to: plugin[0] });

            expect(tool.find("button").length).toBe(1);
            expect(plugin.find("button").length).toBe(0);

            expect(givenItem.parent().prop("outerHTML")).toMatchSnapshot();
        });

        test("should unwrap the button element when adding a tool to the actual note-toolbar", () => {
            const givenContainer = $(`
                <div>
                    <div class="note-toolbar">
                        <div class="note-btn-group note-group-0"></div>
                    </div>
                </div>`);
            const givenItem = $(`<div></div>`);
            const button = $(`<button data-name="color" data-token="color" class="btn btn-outline-secondary btn-sm rounded-3">Icon</button>`);
            givenItem.append(button);
            givenItem.append(`<span class="label">Pick a color</span>`);

            initializeSortable(givenContainer);

            const onAddCb = Sortable.mock.calls[2][1].onAdd;
            onAddCb({ item: givenItem[0] });

            expect(givenItem.prop("outerHTML")).toMatchSnapshot();
            expect(button.prop("outerHTML")).toMatchSnapshot();
        });

        test("should form a new group when the previous empty group has gained a new tool, and instantiate a Sortable object for the new group", () => {
            const givenContainer = $(`
                <div>
                    <div class="note-toolbar">
                    </div>
                </div>`);
            const givenItem = $(`<button data-name="color" data-token="color" aria-label="Pick a color">Icon</button>`);

            $("body").append(givenContainer);

            initializeSortable(givenContainer);

            const onAddCb = Sortable.mock.calls[2][1].onAdd;
            onAddCb({ item: givenItem });

            expect(givenContainer.find(".note-toolbar").prop("outerHTML")).toMatchSnapshot();
            expect(Sortable).toHaveBeenCalledWith(givenContainer.find(".note-btn-group")[0], {
                sort: true,
                group: "toolbar",
                onAdd: expect.any(Function),
            });
        });
    });
});
