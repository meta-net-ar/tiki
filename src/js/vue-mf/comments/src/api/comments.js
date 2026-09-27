// Session-based client for Tiki's comment service. The browser already carries the
// Tiki session cookie, so no token handshake is involved.

// Without an explicit JSON Accept header the broker renders a Smarty template instead
// of serialising — an unauthenticated call then returns a full HTML login page.
const JSON_HEADERS = { Accept: "application/json" };

async function request(serviceUrl, params) {
    const query = new URLSearchParams({ controller: "comment", ...params });
    const response = await fetch(`${serviceUrl}?${query}`, {
        headers: JSON_HEADERS,
        credentials: "same-origin",
    });

    if (!response.ok) {
        // The service answers errors with {code, errortitle, message}; a session that
        // expired mid-session yields the same 403 shape as a permission denial.
        const body = await response.json().catch(() => ({}));
        throw new Error(body.message || `Comment service returned ${response.status}`);
    }

    return response.json();
}

export function listComments(serviceUrl, { type, objectId, offset = 0, maxRecords = 0 }) {
    return request(serviceUrl, {
        action: "list",
        type,
        objectId,
        offset: String(offset),
        maxRecords: String(maxRecords),
    });
}
