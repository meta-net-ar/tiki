import "summernote";
import formatTikiToolbars from "./formatTikiToolbars";
import * as Handlers from "./handlers/index";
import { parseData } from "./handlers/formSubmission.helpers";

export default function (areaId, toolbar, options) {
    const target = $(`#${areaId}`);

    const { tools, icons, customButtons } = formatTikiToolbars(toolbar);

    target.summernote({
        lang: options.lang,
        toolbar: tools,
        icons,
        buttons: customButtons,
        height: options.height,
        callbacks: {
            onInit: function () {
                const toolbar = $(this).data("summernote").layoutInfo.toolbar;
                toolbar.find(".custom-btn-wrapper").each(function () {
                    $(this).children().unwrap();
                });
                if (!options.inline) {
                    parseData(target, Handlers.dirtyCheck.bind(null, target), false);
                } else {
                    Handlers.dirtyCheck(target);
                }
            },
            onKeydown: function (event) {
                if (event.key === "@") {
                    event.preventDefault();
                    renderUserMentionModal(areaId);
                }
            },
            onCodeviewToggled: function () {
                Handlers.customCodeview(target);
            },
        },
    });

    Handlers.formSubmission(target);
    Handlers.pluginEdit(areaId);
}
