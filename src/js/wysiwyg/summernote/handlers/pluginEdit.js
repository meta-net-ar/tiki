export default function (areaId) {
    $(document).on("click", `#${areaId} + .note-editor img.plugin_icon`, function (e) {
        const wrapper = $(this).closest(".tiki_plugin");
        const args = {};
        const argsStr = wrapper.data("args");
        if (argsStr) {
            argsStr.split("&").forEach((arg) => {
                const [key, value] = arg.split("=");
                args[key] = value;
            });
        }

        popupPluginForm(areaId, wrapper.data("plugin"), 0, "", args, false, wrapper.data("body") ?? "", null, null, null, {
            target: wrapper,
        });
    });
}
