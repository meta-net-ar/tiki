// Escapes HTML special characters so the text is safe to inject via v-html.
export function escapeHtml(text) {
    return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

// Escapes regex special characters so arbitrary user input can be used as a literal search pattern.
function escapeRegExp(text) {
    return text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

// Escapes the source text for safe use with v-html, then wraps any
// occurrence of the (also escaped) search query in <mark> tags.
export function highlightMatches(text, query) {
    const escapedText = escapeHtml(text ?? "");
    const trimmedQuery = (query ?? "").trim();
    if (!trimmedQuery) {
        return escapedText;
    }

    const pattern = new RegExp(escapeRegExp(escapeHtml(trimmedQuery)), "gi");
    return escapedText.replace(pattern, (match) => `<mark>${match}</mark>`);
}

// Wraps matches in <mark> inside already-rendered HTML. A plain string replace would
// also hit tag names and attribute values, so this only ever rewrites text nodes and
// builds the replacements through the DOM, leaving the original markup untouched.
export function highlightHtml(html, query) {
    const holder = document.createElement("div");
    holder.innerHTML = html ?? "";

    const trimmedQuery = (query ?? "").trim();
    if (!trimmedQuery) {
        return holder.innerHTML;
    }

    const pattern = new RegExp(escapeRegExp(trimmedQuery), "gi");
    const walker = document.createTreeWalker(holder, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    while (walker.nextNode()) {
        textNodes.push(walker.currentNode);
    }

    for (const node of textNodes) {
        const text = node.nodeValue;
        pattern.lastIndex = 0;
        if (!pattern.test(text)) {
            continue;
        }

        pattern.lastIndex = 0;
        const fragment = document.createDocumentFragment();
        let cursor = 0;
        let match;
        while ((match = pattern.exec(text)) !== null) {
            if (match.index > cursor) {
                fragment.appendChild(document.createTextNode(text.slice(cursor, match.index)));
            }
            const mark = document.createElement("mark");
            mark.textContent = match[0];
            fragment.appendChild(mark);
            cursor = match.index + match[0].length;
        }
        if (cursor < text.length) {
            fragment.appendChild(document.createTextNode(text.slice(cursor)));
        }
        node.parentNode.replaceChild(fragment, node);
    }

    return holder.innerHTML;
}
