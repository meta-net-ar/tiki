const NOT_ANNOTATABLE = ".not-annotatable";

function unescapeQuote(quote) {
    return String(quote || "").replace(/\\(.)/g, "$1");
}

function collectAnnotatableText(container) {
    const walker = container.ownerDocument.createTreeWalker(container, NodeFilter.SHOW_TEXT, (node) =>
        node.parentElement?.closest(NOT_ANNOTATABLE) ? NodeFilter.FILTER_SKIP : NodeFilter.FILTER_ACCEPT
    );

    let text = "";

    for (let node = walker.nextNode(); node !== null; node = walker.nextNode()) {
        text += node.textContent || "";
    }

    return text;
}

function squashWhitespace(text) {
    let out = "";
    const offsets = [];
    let gap = false;

    for (let i = 0; i < text.length; i++) {
        if (/\s/.test(text[i])) {
            gap = true;
            continue;
        }

        if (gap && out.length) {
            out += " ";
            offsets.push(i);
        }

        gap = false;
        out += text[i];
        offsets.push(i);
    }

    return { text: out, offsets };
}

export function isLegacyAnnotation(annotation) {
    return !annotation || Array.isArray(annotation) || !annotation.target || !annotation.target.selector;
}

/**
 * Re-anchor a pre-recogito annotation by finding its stored quote in the page.
 *
 * The old library saved XPath ranges, which no longer resolve once the markup changes.
 * The quote survives in the comment body, and reviveSelector() only needs character offsets.
 * So the quote is what we anchor from.
 */
export function reanchorByQuote(quote, container) {
    const needle = squashWhitespace(unescapeQuote(quote));

    if (!needle.text) return null;

    const raw = collectAnnotatableText(container);
    const haystack = squashWhitespace(raw);
    const at = haystack.text.indexOf(needle.text);

    if (at === -1) return null;

    const start = haystack.offsets[at];
    const end = haystack.offsets[at + needle.text.length - 1] + 1;

    return { quote: raw.slice(start, end), start, end };
}

export function legacyToRecogito(row, container) {
    const selector = reanchorByQuote(row?.quote, container);

    if (!selector) return null;

    return {
        id: `legacy-${row.threadId}`,
        bodies: [{ purpose: "commenting", value: row.text ?? "" }],
        target: { selector: [selector] },
    };
}
