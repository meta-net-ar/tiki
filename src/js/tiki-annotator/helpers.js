function isRecogitoAnnotation(item) {
    if (!item || typeof item !== "object" || Array.isArray(item)) return false;

    const target = item.target;
    if (!target || !target.selector) return false;

    const selectors = Array.isArray(target.selector) ? target.selector : [target.selector];

    return selectors.some((s) => s && (typeof s.start === "number" || typeof s.end === "number" || s.quote));
}

/**
 * Transform backend response to Recogito-compatible format
 * Recogito expects: [{id, bodies, target}, ...]
 */
export function transformBackendResponse(data) {
    if (!data?.rows || !Array.isArray(data.rows)) return [];

    return data.rows
        .map((row) => ({ ...row?.annotation, threadId: row?.threadId, commentDate: row?.commentDate ?? null, realName: row?.realName ?? null }))
        .filter(isRecogitoAnnotation);
}

/**
 * Convert W3C annotation back to legacy backend format
 */
export function convertToLegacyPayload(w3cAnnotation, objectUri, threadId = null) {
    const payload = {
        threadId,
        text: "",
        quote: w3cAnnotation?.target?.selector[0]?.quote || "",
        data: { ...w3cAnnotation, annotationlib: "recogito" },
        uri: String(objectUri || "").trim(),
    };

    return payload;
}
