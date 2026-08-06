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
            onKeydown: (function () {
                let debounceTimer;
                return function (event) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(async () => {
                        const range = $(target).summernote("editor.getLastRange");
                        const editorNode = range.ec;

                        $("#user-mentions-dropdown").remove();

                        if (event.key === "@" || editorNode.textContent.includes("@")) {
                            const text = (editorNode?.textContent || "").substring(0, range.eo);
                            const suggestions = await fetchMentions(text);

                            if (!suggestions.length) {
                                return;
                            }

                            // Use the live selection rect — no DOM mutation needed
                            const sel = window.getSelection();
                            const caretRect = sel?.rangeCount ? sel.getRangeAt(0).getBoundingClientRect() : null;
                            if (!caretRect) {
                                return;
                            }

                            const dropdown = getSuggestionsDropdown(caretRect);
                            suggestions.forEach((suggestion) => {
                                const item = $('<a class="dropdown-item" href="#">').text(suggestion);
                                item.on("click", (e) => {
                                    e.preventDefault();
                                    // Select the typed @partial so pasteHTML replaces it instead of appending
                                    const typedMention = text.match(/@\w*$/)?.[0] ?? "";
                                    if (typedMention) {
                                        const currentRange = $(target).summernote("editor.getLastRange");
                                        const nativeRange = document.createRange();
                                        nativeRange.setStart(currentRange.ec, currentRange.eo - typedMention.length);
                                        nativeRange.setEnd(currentRange.ec, currentRange.eo);
                                        const sel = window.getSelection();
                                        sel.removeAllRanges();
                                        sel.addRange(nativeRange);
                                        $(target).summernote("editor.saveRange");
                                    }
                                    // pasteHTML (inside insertAt's AJAX success) leaves cursor at the start;
                                    // observe the editable for the insertion and move cursor to the end
                                    const editable = $(target).data("summernote").layoutInfo.editable[0];
                                    const observer = new MutationObserver((mutations) => {
                                        observer.disconnect();
                                        let lastAdded = null;
                                        for (const mutation of mutations) {
                                            for (const node of mutation.addedNodes) {
                                                lastAdded = node;
                                            }
                                        }
                                        if (lastAdded) {
                                            const r = document.createRange();
                                            r.setStartAfter(lastAdded);
                                            r.collapse(true);
                                            const sel = window.getSelection();
                                            sel.removeAllRanges();
                                            sel.addRange(r);
                                            $(target).summernote("editor.saveRange");
                                        }
                                    });
                                    observer.observe(editable, { childList: true, subtree: true });
                                    insertAt(target.attr("id"), `@${suggestion}`, false, false, true);
                                    target.summernote("focus");
                                    dropdown.remove();
                                });
                                dropdown.append(item[0]);
                            });

                            document.body.appendChild(dropdown);
                        }
                    }, 500);
                };
            })(),
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
