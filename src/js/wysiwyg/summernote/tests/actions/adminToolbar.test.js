import { describe, expect, test, vi } from "vitest";
import adminToolbar from "../../actions/admintoolbar";
import $ from "jquery";
import { bindToolbarData, handleFilterPlugins, initializeSortable } from "../../actions/adminToolbar.helpers";
import showMessage from "../../../../vue-widgets/element-plus-ui/src/utils/showMessage";
import initSummernote from "../../initSummernote";

vi.mock("../../initSummernote", () => {
    return {
        default: vi.fn(),
    };
});

vi.mock("../../actions/adminToolbar.helpers", () => {
    return {
        bindToolbarData: vi.fn(),
        handleFilterPlugins: vi.fn(),
        initializeSortable: vi.fn(),
    };
});

vi.mock("../../../../vue-widgets/element-plus-ui/src/utils/showMessage", () => {
    return { default: vi.fn() };
});

describe("adminToolbar action", () => {
    beforeAll(() => {
        window.$ = $;
        window.tr = vi.fn().mockReturnValue("translated text");
        $.openModal = vi.fn();
        $.closeModal = vi.fn();
        $.tikiModal = vi.fn();
        $.service = vi.fn().mockReturnValue("service");
        $.editorSection = "editorSection";
    });

    afterEach(() => {
        vi.clearAllMocks();
        $("body").empty();
    });

    test("renders correctly the admin toolbar interface", () => {
        const context = {
            layoutInfo: {
                toolbar: $("<div>toolbar</div>"),
            },
        };

        adminToolbar(context);

        expect($.openModal).toHaveBeenCalledWith({
            title: "translated text",
            size: "modal-xl",
            content: expect.any(String),
            buttons: [
                {
                    text: "translated text",
                    type: "primary",
                    onClick: expect.any(Function),
                },
            ],
            open: expect.any(Function),
        });
        expect(window.tr).toHaveBeenCalledWith("Admin Toolbar");
        expect(window.tr).toHaveBeenCalledWith("Save");

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);

        const noteEditorAdmin = actualContent.find(".note-editor.note-editor--admin");
        expect(noteEditorAdmin.html()).toBe(context.layoutInfo.toolbar.prop("outerHTML"));

        const tools = actualContent.find("#tools");
        expect(tools.parent().find("div").eq(0).text()).toBe("translated text");
        expect(window.tr).toHaveBeenCalledWith("Formatting Tools");

        const plugins = actualContent.find("#plugins-container");
        expect(plugins.parent().find("div").eq(0).text()).toBe("translated text");
        expect(window.tr).toHaveBeenCalledWith("Plugin Tools");

        const pluginsFilter = plugins.find("el-input");
        expect(pluginsFilter.attr("placeholder")).toBe("translated text");
        expect(pluginsFilter.attr("suffix-icon")).toBe("Search");
        expect(pluginsFilter.attr("clearable")).toBe("true");
        expect(window.tr).toHaveBeenCalledWith("Search");

        expect(plugins.find("#plugins").length).toBe(1);
    });

    test("should succesfully execute the open modal callback", () => {
        const context = {
            layoutInfo: {
                toolbar: $("<div class='note-toolbar'>toolbar</div>"),
            },
            options: { toolbar: [] },
        };

        adminToolbar(context);

        const getJSONSpy = vi.spyOn($, "getJSON").mockImplementation(() => {});

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);
        $.openModal.mock.calls[0][0].open.call(actualContent[0]);

        expect($.tikiModal).toHaveBeenCalledWith("translated text");
        expect(window.tr).toHaveBeenCalledWith("Loading...");
        expect($.service).toHaveBeenCalledWith("edit", "ListWysiwygHTMLTools", { section: "editorSection" });

        expect(getJSONSpy).toHaveBeenCalledWith("service", expect.any(Function));

        // when the request is successful
        const expectedData = {
            active: ["foo"],
            tools: [
                { token: "token1", name: "name1", label: "label1", icon: "icon1" },
                { token: "token2", name: "name2", label: "label2", icon: "icon2" },
            ],
            plugins: [
                { token: "token3", name: "name3", label: "label3", icon: "icon3" },
                { token: "token4", name: "name4", label: "label4", icon: "icon4" },
            ],
        };
        getJSONSpy.mock.calls[0][1](expectedData);
        expect($.tikiModal).toHaveBeenCalledWith();

        expect(bindToolbarData).toHaveBeenCalledWith(context.options.toolbar, expectedData.active, actualContent.find(".note-toolbar"));

        expectedData.tools.forEach((tool) => {
            const button = actualContent.find("#tools").find(`button[data-token="${tool.token}"]`);
            expect(button.data("name")).toBe(tool.name);
            expect(button.html().trim()).toBe(tool.icon);
            expect(button.next().text()).toBe(tool.label);
        });

        expectedData.plugins.forEach((plugin) => {
            const button = actualContent.find("#plugins").find(`button[data-token="${plugin.token}"]`);
            expect(button.data("name")).toBe(plugin.name);
            expect(button.html().trim()).toBe(plugin.icon);
            expect(button.next().text()).toBe(plugin.label);
        });

        expect(handleFilterPlugins).toHaveBeenCalledWith(actualContent.find("#plugins").parent());
        expect(initializeSortable).toHaveBeenCalledWith(actualContent);
    });

    test("should succesfully save the toolbar", () => {
        const groups = [["tool-1", "tool-2"], ["tool-3", "tool-4"], ["tool-5"]];
        const context = {
            layoutInfo: {
                toolbar: $(`<div class='note-toolbar'></div>`),
            },
            options: { lang: "en" },
            $note: $("<div id='note-id' />"),
            invoke: vi.fn(),
        };

        groups.forEach((group) => {
            const groupEl = $("<div/>").addClass("note-btn-group");
            group.forEach((tool) => {
                groupEl.append($(`<button data-name="${tool}"></button>`));
            });
            context.layoutInfo.toolbar.append(groupEl);
        });

        adminToolbar(context);

        const ajaxPostSpy = vi.spyOn($, "post").mockImplementation(() => {
            return {
                always: (cb) => {
                    if (cb) cb();
                    return { fail: vi.fn() };
                },
            };
        });

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);

        $.openModal.mock.calls[0][0].buttons[0].onClick.call(actualContent[0]);

        expect(ajaxPostSpy).toHaveBeenCalledWith(
            "service",
            {
                section: "editorSection",
                tools: "tool-1,tool-2,-,tool-3,tool-4,-,tool-5",
            },
            expect.any(Function)
        );

        const expectedToolbar = [
            { token: "tool-1", callback: "tool1cb", renderCallback: "tool1renderCb" },
            [
                {
                    token: "tool2",
                    callback: "tool2cb",
                    renderCallback: "tool2renderCb",
                    callbackContent: "tool2cbContent",
                    renderCallbackContent: "tool2renderCbContent",
                },
                { token: "tool-3", callbackContent: "tool3cbContent", renderCallbackContent: "tool3renderCbContent" },
            ],
            "-",
            { token: "tool-4", callback: "tool4cb", renderCallback: "tool4renderCb" },
        ];

        // when the request is successful
        ajaxPostSpy.mock.calls[0][2]({ toolbar: expectedToolbar });
        expect($.closeModal).toHaveBeenCalled();
        // expect($.tikiModal).toHaveBeenCalledWith();
        expect(showMessage).toHaveBeenCalledWith("translated text", "success");
        expect(window.tr).toHaveBeenCalledWith("Toolbar updated.");

        expect(context.invoke).toHaveBeenCalledWith("destroy");
        expect(initSummernote).toHaveBeenCalledWith("note-id", expectedToolbar, { lang: "en" });

        ajaxPostSpy.mock.results[0].value.always();
        expect($.tikiModal).toHaveBeenCalledWith();
    });

    test("should show an error message when the save request does not output the updated toolbar", () => {
        const context = {
            layoutInfo: {
                toolbar: $(`<div class='note-toolbar'></div>`),
            },
            options: { lang: "en" },
            $note: $("<div id='note-id' />"),
            invoke: vi.fn(),
        };

        adminToolbar(context);

        const ajaxPostSpy = vi.spyOn($, "post").mockImplementation(() => {
            return {
                always: (cb) => {
                    if (cb) cb();
                    return { fail: vi.fn() };
                },
            };
        });

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);

        $.openModal.mock.calls[0][0].buttons[0].onClick.call(actualContent[0]);

        // when the request is successful
        ajaxPostSpy.mock.calls[0][2]({});
        expect($.closeModal).not.toHaveBeenCalled();
        expect(showMessage).toHaveBeenCalledWith("translated text", "error");
        expect(window.tr).toHaveBeenCalledWith("Could not update the toolbar. Please try again.");
    });

    test("should show an error message when the save request fails", () => {
        const context = {
            layoutInfo: {
                toolbar: $(`<div class='note-toolbar'></div>`),
            },
            options: { lang: "en" },
            $note: $("<div id='note-id' />"),
            invoke: vi.fn(),
        };

        adminToolbar(context);

        const ajaxPostSpy = vi.spyOn($, "post").mockImplementation(() => {
            return {
                always: (cb) => {
                    if (cb) cb();
                    return { fail: (cb) => cb && cb() };
                },
            };
        });

        const actualContent = $("<div/>").html($.openModal.mock.calls[0][0].content);

        $.openModal.mock.calls[0][0].buttons[0].onClick.call(actualContent[0]);

        // when the request fails
        ajaxPostSpy.mock.results[0].value.always().fail();
        expect($.closeModal).not.toHaveBeenCalled();
        expect(showMessage).toHaveBeenCalledWith("translated text", "error");
        expect(window.tr).toHaveBeenCalledWith("An error occurred. Please try again.");
    });
});
