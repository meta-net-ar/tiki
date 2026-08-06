// Escapes HTML special characters so the text is safe to inject via v-html.
function escapeHtml(text) {
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
