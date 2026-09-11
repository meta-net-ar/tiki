// CSRF ticket helper for the SSS admin front-end.
//
// The encryption controller's mutating actions (save_key, delete_key) require a
// valid CSRF ticket. The ticket is the stable per-session token; we fetch it once
// from the admin-gated `get_ticket` service action and cache the in-flight promise
// so concurrent callers share a single request. A failed fetch clears the cache so
// a later action can retry.

let ticketPromise: Promise<string> | null = null;

export function getCsrfTicket(): Promise<string> {
    if (!ticketPromise) {
        ticketPromise = fetch("tiki-ajax_services.php?controller=encryption&action=get_ticket")
            .then((res) => (res.ok ? res.json() : Promise.reject(new Error("ticket fetch failed"))))
            .then((data) => String(data?.ticket ?? ""))
            .catch(() => {
                ticketPromise = null;
                return "";
            });
    }
    return ticketPromise;
}

// Test-only: drop the cached ticket so each test starts from a clean slate.
export function __resetCsrfTicket(): void {
    ticketPromise = null;
}
