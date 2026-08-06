import "summernote";
import formatTikiToolbars from "./formatTikiToolbars";
import * as Handlers from "./handlers/index";
import { parseData } from "./handlers/formSubmission.helpers";
import { setupLanguageCheckTrigger, initializeLanguageCheck } from "./languageCheck";

// Set up global trigger function for toolbar callbacks
setupLanguageCheckTrigger();

export default function (areaId, toolbar, options) {
    const target = $(`#${areaId}`);

    const { tools, icons, customButtons } = formatTikiToolbars(toolbar);

    // Build callbacks normally
    const callbacks = {
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

        onCodeviewToggled: function () {
            Handlers.customCodeview(target);
        },
    };

    // Add onDialogShown ONLY when ElementPlus is active
    if (window.elementPlus) {
        callbacks.onDialogShown = function () {
            setTimeout(() => {
                const inputs = target.data("summernote").layoutInfo.editor.find(".modal-body").find("input, select");

                inputs.each(function () {
                    const elementPlusInput = $(`#${$(this).attr("element-plus-ref")}`);
                    elementPlusInput.attr("value", $(this).val());

                    // Sync back sanitized URL value after blur
                    $(this).on("blur", () => {
                        elementPlusInput.attr("value", $(this).val());
                    });
                });
            }, 0);
        };
    }

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

                // Initialize language checker if enabled via options
                initializeLanguageCheck(target, options);
            },
            onKeydown: Handlers.userMention(target),
            onCodeviewToggled: function () {
                Handlers.customCodeview(target);
            },
            onDestroy: function () {
                const languageChecker = target.data("languageChecker");
                if (languageChecker) {
                    languageChecker.destroy();
                }
            },
        },
    });

    Handlers.formSubmission(target);
    Handlers.pluginEdit(areaId);
}
