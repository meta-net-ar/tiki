export default function (textarea) {
    let debounceTimer;
    return function (event) {
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
                            const currentRange = textarea.summernote("editor.getLastRange");
                            const nativeRange = document.createRange();
                            nativeRange.setStart(currentRange.ec, currentRange.eo - typedMention.length);
                            nativeRange.setEnd(currentRange.ec, currentRange.eo);
                            const sel = window.getSelection();
                            sel.removeAllRanges();
                            sel.addRange(nativeRange);
                            textarea.summernote("editor.saveRange");
                        }
                        // pasteHTML (inside insertAt's AJAX success) leaves cursor at the start;
                        // observe the editable for the insertion and move cursor to the end
                        const editable = textarea.data("summernote").layoutInfo.editable[0];
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
                        insertAt(textarea.attr("id"), `@${suggestion}`, false, false, true);
                        textarea.summernote("focus");
                        dropdown.remove();
                    });
                    dropdown.append(item[0]);
                });

                document.body.appendChild(dropdown);
            }
        }, 500);
    };
}
