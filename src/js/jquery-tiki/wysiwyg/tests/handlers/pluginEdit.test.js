import { beforeAll, describe, expect, test } from "vitest";
import pluginEdit from "../../handlers/pluginEdit";
import $ from "jquery";

describe("pluginEdit handler", () => {
    beforeAll(() => {
        window.$ = $;
        window.popupPluginForm = vi.fn();
    });

    test("correctly handle plugin edition", () => {
        // Given that a plugin is rendered within the editor
        const areaId = "editor";
        const area = $(`<div id="${areaId}"></div>`).appendTo("body");
        area.after('<div class="note-editor"><div class="tiki_plugin" data-plugin="plugin-type"><img class="plugin_icon"></div></div></div>');

        pluginEdit(areaId);

        $(".plugin_icon").trigger("click");

        expect(window.popupPluginForm).toHaveBeenCalledWith(areaId, "plugin-type", 0, "", {}, false, "", null, null, null, {
            target: expect.any(Object),
        });
    });

    test("correctly handle plugin edition with arguments", () => {
        // Given that a plugin is rendered within the editor
        const areaId = "editor";
        const area = $(`<div id="${areaId}"></div>`).appendTo("body");
        area.after(
            '<div class="note-editor"><div class="tiki_plugin" data-plugin="plugin-type" data-args="arg1=val1&arg2=val2"><img class="plugin_icon"></div></div></div>'
        );

        pluginEdit(areaId);

        $(".plugin_icon").trigger("click");

        expect(window.popupPluginForm).toHaveBeenCalledWith(
            areaId,
            "plugin-type",
            0,
            "",
            { arg1: "val1", arg2: "val2" },
            false,
            "",
            null,
            null,
            null,
            {
                target: expect.any(Object),
            }
        );
    });
});
