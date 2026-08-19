export default function (textarea) {
    let debounceTimer;
    return function (event) {
        const dropdown = document.getElementById("user-mentions-dropdown");
        if (dropdown) {
            if (handleArrowNavigation(event, dropdown)) {
                return; // Stop further processing if navigation was handled to prevent unwanted dropdown refreshes.
            }
        }

        if (!window.jqueryTiki.user_mention_enabled) {
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const range = textarea.summernote("editor.getLastRange");
            const editorNode = range.ec;

            $("#user-mentions-dropdown").remove();

            if (event.key === "@" || editorNode.textContent.includes("@")) {
                const text = (editorNode?.textContent || "").substring(0, range.eo);
                const suggestions = await fetchMentions(text);

                if (!suggestions.length) {
                    return;
                }

                const sel = window.getSelection();
                const caretRect = sel?.rangeCount ? sel.getRangeAt(0).getBoundingClientRect() : null;
                if (!caretRect) {
                    return;
                }

                const dropdown = getSuggestionsDropdown(caretRect);
                suggestions.forEach(({ username, realname, avatar }, index) => {
                    const item = getSuggestionDropdownItem(username, realname, avatar, index === 0);
                    $(item).on("click", (e) => {
                        e.preventDefault();
                        const typedMention = text.match(/@\w*$/)?.[0] ?? "";

                        // Wrap the typed @partial in a temp span passed as extra.target so
                        // insertAt uses replaceWith() instead of pasteHTML(), bypassing summernote's range entirely which causes cursor position issues.
                        let mentionSpan = null;
                        if (typedMention) {
                            const nativeRange = document.createRange();
                            nativeRange.setStart(editorNode, range.eo - typedMention.length);
                            nativeRange.setEnd(editorNode, range.eo);
                            mentionSpan = document.createElement("span");
                            nativeRange.surroundContents(mentionSpan);

                            // Keep cursor at the end of the span to avoid a visual jump while AJAX is pending
                            const caretAfter = document.createRange();
                            caretAfter.selectNodeContents(mentionSpan);
                            caretAfter.collapse(false);
                            window.getSelection().removeAllRanges();
                            window.getSelection().addRange(caretAfter);
                        }

                        const editable = textarea.data("summernote").layoutInfo.editable[0];

                        // Reposition the cursor after the mention is inserted
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
                                textarea.summernote("editor.saveRange");
                            }
                        });

                        observer.observe(editable, { childList: true, subtree: true });

                        const extra = mentionSpan ? { target: $(mentionSpan) } : undefined;
                        insertAt(textarea.attr("id"), `@${username}`, false, false, true, extra);

                        textarea.summernote("focus");

                        dropdown.remove();
                    });
                    dropdown.append(item);
                });

                document.body.appendChild(dropdown);
            }
        }, 500);
    };
}

function handleArrowNavigation(event, dropdown) {
    const items = Array.from(dropdown.querySelectorAll(".dropdown-item"));
    const activeItem = dropdown.querySelector(".dropdown-item.active");
    const activeIndex = activeItem ? items.indexOf(activeItem) : -1;

    if (event.key === "ArrowDown") {
        event.preventDefault();
        activeItem?.classList.remove("active");
        items[(activeIndex + 1) % items.length]?.classList.add("active");
        return true;
    }
    if (event.key === "ArrowUp") {
        event.preventDefault();
        activeItem?.classList.remove("active");
        items[(activeIndex - 1 + items.length) % items.length]?.classList.add("active");
        return true;
    }
    if (["Enter", "Tab"].includes(event.key) && activeItem) {
        event.preventDefault();
        activeItem.click();
        return true;
    }
    if (event.key === "Escape") {
        event.preventDefault();
        dropdown.remove();
        return true;
    }
}
