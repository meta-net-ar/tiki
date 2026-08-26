const COMMENTING_PURPOSE = "commenting";

export function tr(key) {
    return typeof window.tr === "function" ? window.tr(key) : key;
}

export function notify(message, type = "error") {
    if (typeof window.showMessage === "function") {
        window.showMessage(message, type);
    }
}

function isRecogitoAnnotation(item) {
    if (!item || typeof item !== "object" || Array.isArray(item)) return false;

    const target = item.target;
    if (!target || !target.selector) return false;

    const selectors = Array.isArray(target.selector) ? target.selector : [target.selector];

    return selectors.some((s) => s && (typeof s.start === "number" || typeof s.end === "number" || s.quote));
}

export function getCommentBody(annotation) {
    const body = annotation?.bodies?.find((b) => b?.purpose === COMMENTING_PURPOSE);

    return body?.value ?? "";
}

export function setCommentBody(annotation, value) {
    if (!annotation) return annotation;

    if (!Array.isArray(annotation.bodies)) {
        annotation.bodies = [];
    }

    const body = annotation.bodies.find((b) => b?.purpose === COMMENTING_PURPOSE);

    if (body) {
        body.value = value;
    } else {
        annotation.bodies.push({ purpose: COMMENTING_PURPOSE, value });
    }

    return annotation;
}

export function getQuote(annotation) {
    const selector = annotation?.target?.selector;
    const first = Array.isArray(selector) ? selector[0] : selector;

    return first?.quote || "";
}

export function isAllowed(permissions, action) {
    const value = permissions?.[action];

    return value === undefined || value === null ? true : Boolean(value);
}

export function transformBackendResponse(data) {
    if (!data?.rows || !Array.isArray(data.rows)) return [];

    return data.rows
        .map((row) => {
            const annotation = { ...row?.annotation };

            if (typeof row?.text === "string" && row.text !== "") {
                setCommentBody(annotation, row.text);
            }

            annotation.threadId = row?.threadId ?? null;
            annotation.permissions = row?.permissions ?? {};
            annotation.commentDate = row?.commentDate ?? null;
            annotation.realName = row?.realName ?? null;

            return annotation;
        })
        .filter(isRecogitoAnnotation);
}

export function convertToLegacyPayload(w3cAnnotation, objectUri, threadId = null) {
    return {
        threadId,
        text: getCommentBody(w3cAnnotation),
        quote: getQuote(w3cAnnotation),
        params: {
            id: w3cAnnotation?.id,
            bodies: w3cAnnotation?.bodies ?? [],
            target: w3cAnnotation?.target ?? {},
            annotationlib: "recogito",
        },
        uri: String(objectUri || "").trim(),
    };
}

export function getSelectionRects() {
    const selection = window.getSelection();

    if (!selection || !selection.rangeCount || selection.isCollapsed) return null;

    const range = selection.getRangeAt(0);
    const bounds = range.getBoundingClientRect();

    if (!bounds.width && !bounds.height) return null;

    const rects = Array.from(range.getClientRects()).filter((rect) => rect.width || rect.height);

    return { bounds, last: rects.length ? rects[rects.length - 1] : bounds };
}

export function rectFromPoint(x, y) {
    return { left: x, top: y, right: x, bottom: y, width: 0, height: 0 };
}
