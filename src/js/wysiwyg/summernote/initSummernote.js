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

        // onKeydown: function (event) {
        //     if (event.key === "@") {
        //         event.preventDefault();
        //         renderUserMentionModal(areaId);
        //     }
        // },

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
            onInput: function (event) {
                console.log("onInput triggered", event);
            },
            onKeydown: async function (event) {
                const range = $(target).summernote("editor.getLastRange");
                const editorNode = range.ec;
                if (event.key === "@" || editorNode.textContent.includes("@")) {
                    const editableNode = editorNode?.nodeType === Node.TEXT_NODE ? editorNode.parentNode : editorNode;

                    if (!editableNode || typeof editableNode.appendChild !== "function") {
                        return;
                    }

                    const text = (editorNode?.textContent || "").substring(0, range.eo);
                    console.log("range:", range, text);
                    const suggestions = await fetchMentions(text);

                    if (!suggestions.length) {
                        return;
                    }

                    const mentionTarget = $("<span />").text(text);
                    const end = $("<span />").text((editorNode?.textContent || "").substring(range.eo));
                    console.log("mentionTarget:", text, "end:", end.text(), "node:", editableNode.textContent, editableNode);
                    editableNode.childNodes.forEach((child) => {
                        if (child === editorNode) {
                            editableNode.replaceChild(mentionTarget[0], child);
                            editableNode.insertBefore(end[0], mentionTarget[0].nextSibling);
                        }
                    });

                    // put the cursor at the end of the mentionTarget
                    const newRange = document.createRange();
                    newRange.setStart(mentionTarget[0], mentionTarget[0].childNodes.length);
                    newRange.setEnd(mentionTarget[0], mentionTarget[0].childNodes.length);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(newRange);

                    const dropdown = getSuggestionsDropdown(mentionTarget[0].getBoundingClientRect());
                    suggestions.forEach((suggestion) => {
                        const item = $('<a class="dropdown-item" href="#">').text(suggestion);
                        item.on("click", (e) => {
                            e.preventDefault();
                            mentionTarget.text(`@${suggestion} `);
                            $(target).summernote("editor.setLastRange", range);
                            dropdown.remove();
                        });
                        dropdown.append(item[0]);
                    });

                    document.body.appendChild(dropdown);
                }
            },
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
