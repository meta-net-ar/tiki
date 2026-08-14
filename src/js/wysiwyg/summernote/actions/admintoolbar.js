import { decodeHtmlEntities } from "../createCustomButton";
import showMessage from "../../../vue-widgets/element-plus-ui/src/utils/showMessage";
import initSummernote from "../initSummernote";
import { bindToolbarData, handleFilterPlugins, initializeSortable } from "./adminToolbar.helpers";

export default function (context) {
    const toolbar = context.layoutInfo.toolbar.clone();
    toolbar.find("button + .dropdown-toggle, button > div:hidden").remove();

    $.openModal({
        title: tr("Admin Toolbar"),
        size: "modal-xl",
        content: `
            <div class="note-editor note-editor--admin">${toolbar.prop("outerHTML")}</div>
            <div class="d-flex justify-content-between mt-3 gap-2">
                <div class="w-50">
                    <div>${tr("Formatting Tools")}</div>
                    <div class="d-flex flex-column gap-1 bg-light p-3 rounded-3" id="tools"></div>
                </div>
                <div class="w-50">
                    <div>${tr("Plugin Tools")}</div>
                    <div class="bg-light p-3 rounded-3" id="plugins-container">
                        <el-input placeholder="${tr("Search")}" suffix-icon="Search" clearable="true"></el-input>
                        <div class="d-flex flex-column gap-1 mt-2" id="plugins"></div>
                    </div>
                </div>
            </div>
        `,
        buttons: [
            {
                text: tr("Save"),
                type: "primary",
                onClick: function () {
                    $.tikiModal(tr("Saving..."));
                    const tools = $(this)
                        .find(".note-editor--admin")
                        .find(".note-btn-group")
                        .map(function () {
                            return $(this)
                                .children("button")
                                .map(function () {
                                    return $(this).data("name");
                                })
                                .get()
                                .join(",");
                        })
                        .get()
                        .join(",-,");

                    $.post(
                        $.service("edit", "saveTools"),
                        {
                            section: $.editorSection,
                            tools,
                        },
                        (data) => {
                            if (data.toolbar) {
                                showMessage(tr("Toolbar updated."), "success");
                                $.closeModal();

                                const lang = context.options.lang;
                                const areaId = context.$note.attr("id");

                                context.invoke("destroy");

                                const groupCb = (tool) => {
                                    if (!Array.isArray(tool)) {
                                        if (typeof tool !== "string") {
                                            tool.callback = new Function(tool.callbackContent);
                                            tool.renderCallback = new Function(tool.renderCallbackContent);
                                        }
                                        return tool;
                                    } else {
                                        return tool.map(groupCb);
                                    }
                                };
                                data.toolbar.forEach(groupCb);
                                initSummernote(areaId, data.toolbar, { lang, minHeight: context.options.minHeight });
                            } else {
                                showMessage(tr("Could not update the toolbar. Please try again."), "error");
                            }
                        }
                    )
                        .always(() => $.tikiModal())
                        .fail(() => showMessage(tr("An error occurred. Please try again."), "error"));
                },
            },
        ],
        open: function () {
            $.tikiModal(tr("Loading..."));
            $.getJSON($.service("edit", "ListWysiwygHTMLTools", { section: $.editorSection }), (data) => {
                $.tikiModal();
                const toolsContainer = $(this).find("#tools");
                const pluginsContainer = $(this).find("#plugins");

                const getToolMarkup = (tool) => {
                    return `
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm rounded-3" data-name="${tool.name}" data-token="${tool.token}">
                                ${decodeHtmlEntities(tool.icon)}
                            </button>
                            <span class="label">${tool.label}</span>
                        </div>
                    `;
                };

                bindToolbarData(context.options.toolbar, data.active, $(this).find(".note-toolbar"));

                data.tools.forEach((tool) => {
                    toolsContainer.append(getToolMarkup(tool));
                });

                data.plugins.forEach((plugin) => {
                    pluginsContainer.append(getToolMarkup(plugin));
                });

                handleFilterPlugins(pluginsContainer.parent());
                initializeSortable($(this));
            });
        },
    });
}
