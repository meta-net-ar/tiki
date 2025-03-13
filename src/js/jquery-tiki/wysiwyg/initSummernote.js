import "summernote";
import formatTikiToolbars from "./formatTikiToolbars";
import * as Handlers from "./handlers/index";

export default function (areaId, toolbar, lang) {
    const target = $(`#${areaId}`);
    const value = target.val() || target.html();

    // encode to html entities, quotes found in the data-syntax attributes for plugins elements before rendering so that the browser does not take them as attribute delimiters
    const htmlEncoded = value.replace(/data-syntax="(\{.*?\})"/g, (_, p1) => {
        return `data-syntax="${p1.replace(/"/g, "&quot;")}"`;
    });

    if (target.is("textarea")) {
        target.val(htmlEncoded);
    } else {
        target.html(htmlEncoded);
    }

    const { tools, icons, customButtons, renderCallbacks } = formatTikiToolbars(toolbar);

    target.summernote({
        lang,
        toolbar: tools,
        icons,
        buttons: customButtons,
        callbacks: {
            onInit: function () {
                renderCallbacks.forEach((cbName) => {
                    window[cbName]();
                });
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
