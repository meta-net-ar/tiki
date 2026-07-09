import { render, screen, fireEvent, waitFor } from "@testing-library/vue";
import { describe, expect, test, vi, beforeEach, afterEach } from "vitest";
import KeyDashboardPage from "../../src/components/KeyDashboardPage.vue";
import { __resetCsrfTicket } from "../../src/csrf";

const KEYS_RESPONSE = [
    { keyId: 1, name: "Payroll Key", description: "HR payroll data", shares: 2, users: "alice,bob", atRisk: false },
    { keyId: 2, name: "Legal Key", description: "", shares: 0, users: "", atRisk: true },
];
const TEST_TICKET = "TESTTICKET-123";

function mockFetch(responses) {
    let callIndex = 0;
    return vi.fn().mockImplementation(() => {
        const response = responses[callIndex] ?? responses[responses.length - 1];
        callIndex++;
        return Promise.resolve({
            ok: true,
            json: () => Promise.resolve(response),
        });
    });
}

beforeEach(() => {
    __resetCsrfTicket();
    global.fetch = mockFetch([KEYS_RESPONSE]);
});

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = "";
});

describe("KeyDashboardPage — key list", () => {
    test("loads and displays keys", async () => {
        render(KeyDashboardPage);
        await waitFor(() => expect(screen.getByText("Payroll Key")).toBeTruthy());
        expect(screen.getByText("Legal Key")).toBeTruthy();
    });

    test("shows at-risk warning for key with 0 shares", async () => {
        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Legal Key"));
        expect(screen.getByText(/at.?risk/i)).toBeTruthy();
    });

    test("shows error message on fetch failure", async () => {
        global.fetch = vi.fn().mockRejectedValue(new Error("network error"));
        render(KeyDashboardPage);
        await waitFor(() => expect(screen.getByText(/failed to load/i)).toBeTruthy());
    });
});

describe("KeyDashboardPage — delete key", () => {
    test("calls delete endpoint on confirmed delete", async () => {
        global.confirm = vi.fn().mockReturnValue(true);
        global.fetch = vi
            .fn()
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(KEYS_RESPONSE) })
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve({}) })
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve([]) });

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const deleteButtons = screen.getAllByTitle(/delete/i);
        await fireEvent.click(deleteButtons[0]);

        // The delete POST is now issued after an async getCsrfTicket() hop, so wait for it.
        await waitFor(() =>
            expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=delete_key"), expect.objectContaining({ method: "POST" }))
        );
    });

    test("includes the CSRF ticket in the delete_key POST body", async () => {
        global.confirm = vi.fn().mockReturnValue(true);
        global.fetch = vi.fn().mockImplementation((url) => {
            if (url.includes("action=get_ticket")) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({ ticket: TEST_TICKET }) });
            }
            if (url.includes("action=delete_key")) {
                return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
            }
            return Promise.resolve({ ok: true, json: () => Promise.resolve(KEYS_RESPONSE) });
        });

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const deleteButtons = screen.getAllByTitle(/delete/i);
        await fireEvent.click(deleteButtons[0]);

        await waitFor(() =>
            expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=delete_key"), expect.objectContaining({ method: "POST" }))
        );

        const deleteCall = global.fetch.mock.calls.find(([url]) => url.includes("action=delete_key"));
        expect(new URLSearchParams(deleteCall[1].body).get("ticket")).toBe(TEST_TICKET);
    });

    test("does not call delete endpoint when user cancels", async () => {
        global.confirm = vi.fn().mockReturnValue(false);
        global.fetch = mockFetch([KEYS_RESPONSE]);

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const deleteButtons = screen.getAllByTitle(/delete/i);
        await fireEvent.click(deleteButtons[0]);

        expect(global.fetch).toHaveBeenCalledTimes(1); // only the initial load
    });
});

describe("KeyDashboardPage — get my share modal", () => {
    test("shows share value in modal after fetching", async () => {
        const shareValue = "abc123sharetoken";
        global.fetch = vi
            .fn()
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(KEYS_RESPONSE) })
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(shareValue) });

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const shareButtons = screen.getAllByTitle(/my share/i);
        await fireEvent.click(shareButtons[0]);

        await waitFor(() => expect(screen.getByDisplayValue(shareValue)).toBeTruthy());
    });

    test("shows error when share not found", async () => {
        global.fetch = vi
            .fn()
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(KEYS_RESPONSE) })
            .mockResolvedValueOnce({ ok: false, json: () => Promise.resolve({}) });

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const shareButtons = screen.getAllByTitle(/my share/i);
        await fireEvent.click(shareButtons[0]);

        await waitFor(() => expect(screen.getByText(/failed to retrieve/i)).toBeTruthy());
    });

    test("closes modal on Close button click", async () => {
        const shareValue = "mysharetoken";
        global.fetch = vi
            .fn()
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(KEYS_RESPONSE) })
            .mockResolvedValueOnce({ ok: true, json: () => Promise.resolve(shareValue) });

        render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        const shareButtons = screen.getAllByTitle(/my share/i);
        await fireEvent.click(shareButtons[0]);
        await waitFor(() => screen.getByDisplayValue(shareValue));

        // Click the footer Close button (not the aria-label X button)
        const closeButtons = screen.getAllByRole("button", { name: /^close$/i });
        await fireEvent.click(closeButtons[closeButtons.length - 1]);
        expect(screen.queryByDisplayValue(shareValue)).toBeNull();
    });
});

describe("KeyDashboardPage — navigation events", () => {
    test("emits create event on New Key button click", async () => {
        const { emitted } = render(KeyDashboardPage);
        await waitFor(() => screen.getByText("Payroll Key"));

        await fireEvent.click(screen.getByRole("button", { name: /new key/i }));
        expect(emitted().create).toBeTruthy();
    });
});
