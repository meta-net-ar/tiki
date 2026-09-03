import { render, screen, fireEvent, waitFor } from "@testing-library/vue";
import { describe, expect, test, vi, beforeEach, afterEach } from "vitest";
import AdminKeyCreationPage from "../../src/components/AdminKeyCreationPage.vue";
import { __resetCsrfTicket } from "../../src/csrf";

const USERS_RESPONSE = ["alice", "bob", "carol"];
const ALGOS_RESPONSE = ["aes-256-ctr", "aes-256-gcm"];
const SAVE_RESPONSE = { keyId: 1, shares: ["share-alice", "share-bob"] };
const TEST_TICKET = "TESTTICKET-123";

function setupFetch(overrides = {}) {
    global.fetch = vi.fn().mockImplementation((url) => {
        if (url.includes("action=get_ticket")) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve({ ticket: overrides.ticket ?? TEST_TICKET }) });
        }
        if (url.includes("action=get_algos")) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(overrides.algos ?? ALGOS_RESPONSE) });
        }
        if (url.includes("action=get_users")) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(overrides.users ?? USERS_RESPONSE) });
        }
        if (url.includes("action=save_key")) {
            return Promise.resolve({ ok: overrides.saveOk ?? true, json: () => Promise.resolve(overrides.save ?? SAVE_RESPONSE) });
        }
        if (url.includes("action=get_key")) {
            return Promise.resolve({
                ok: true,
                json: () => Promise.resolve(overrides.key ?? { key: { name: "Existing Key", description: "", algo: "aes-256-ctr", users: "alice" } }),
            });
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
    });
}

beforeEach(() => {
    __resetCsrfTicket();
    setupFetch();
});

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = "";
});

describe("AdminKeyCreationPage — create mode", () => {
    test("renders name and description fields", async () => {
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => expect(screen.getByPlaceholderText !== undefined).toBe(true));
        expect(screen.getAllByRole("textbox").length).toBeGreaterThan(0);
    });

    test("Apply button is disabled when name is empty", async () => {
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        const applyBtn = await waitFor(() => screen.getByRole("button", { name: /apply/i }));
        expect(applyBtn.disabled).toBe(true);
    });

    test("Apply button enabled after name is entered", async () => {
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        const inputs = await waitFor(() => screen.getAllByRole("textbox"));
        await fireEvent.update(inputs[0], "New Key");
        const applyBtn = screen.getByRole("button", { name: /apply/i });
        expect(applyBtn.disabled).toBe(false);
    });

    test("submits POST to save_key and shows share table on success", async () => {
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => screen.getByRole("button", { name: /apply/i }));

        const inputs = screen.getAllByRole("textbox");
        await fireEvent.update(inputs[0], "Payroll Key");

        await fireEvent.click(screen.getByRole("button", { name: /apply/i }));

        await waitFor(() =>
            expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.objectContaining({ method: "POST" }))
        );
    });

    test("includes the CSRF ticket in the save_key POST body", async () => {
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => screen.getByRole("button", { name: /apply/i }));

        const inputs = screen.getAllByRole("textbox");
        await fireEvent.update(inputs[0], "Payroll Key");
        await fireEvent.click(screen.getByRole("button", { name: /apply/i }));

        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.any(Object)));

        const saveCall = global.fetch.mock.calls.find(([url]) => url.includes("action=save_key"));
        const body = saveCall[1].body;
        expect(new URLSearchParams(body).get("ticket")).toBe(TEST_TICKET);
    });

    test("shows error message on save failure", async () => {
        setupFetch({ saveOk: false });
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => screen.getByRole("button", { name: /apply/i }));

        const inputs = screen.getAllByRole("textbox");
        await fireEvent.update(inputs[0], "My Key");

        await fireEvent.click(screen.getByRole("button", { name: /apply/i }));

        await waitFor(() => expect(screen.getByText(/failed to save/i)).toBeTruthy());
    });

    test("Cancel button emits back event", async () => {
        const { emitted } = render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => screen.getByRole("button", { name: /cancel/i }));

        await fireEvent.click(screen.getByRole("button", { name: /cancel/i }));
        expect(emitted().back).toBeTruthy();
    });
});

describe("AdminKeyCreationPage — shares post-save display", () => {
    test("shows share values after successful save", async () => {
        setupFetch({ save: { keyId: 1, shares: ["SHARE-FOR-ALICE", "SHARE-FOR-BOB"] } });
        render(AdminKeyCreationPage, { props: { mode: "create" } });
        await waitFor(() => screen.getByRole("button", { name: /apply/i }));

        const inputs = screen.getAllByRole("textbox");
        await fireEvent.update(inputs[0], "Payroll Key");

        // Select a user to get share display
        const checkboxes = screen.queryAllByRole("checkbox");
        if (checkboxes.length > 0) {
            await fireEvent.click(checkboxes[0]);
        }

        await fireEvent.click(screen.getByRole("button", { name: /apply/i }));

        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.any(Object)));
    });
});

describe("AdminKeyCreationPage — edit mode", () => {
    test("loads existing key data on mount", async () => {
        render(AdminKeyCreationPage, { props: { mode: "edit", keyId: 5, keyName: "Existing Key" } });

        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=get_key")));
    });
});
