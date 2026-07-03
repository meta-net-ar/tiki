import { render, screen, fireEvent, waitFor } from "@testing-library/vue";
import { describe, expect, test, vi, beforeEach, afterEach } from "vitest";
import KeyShareManagementPage from "../../src/components/KeyShareManagementPage.vue";

const KEY_RESPONSE = { key: { name: "Payroll Key", users: "alice,bob" } };
const USERS_RESPONSE = ["alice", "bob", "carol"];

function setupFetch(overrides = {}) {
    global.fetch = vi.fn().mockImplementation((url) => {
        if (url.includes("action=get_key")) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(overrides.key ?? KEY_RESPONSE) });
        }
        if (url.includes("action=get_users")) {
            return Promise.resolve({ ok: true, json: () => Promise.resolve(overrides.users ?? USERS_RESPONSE) });
        }
        if (url.includes("action=save_key")) {
            return Promise.resolve({ ok: overrides.saveOk ?? true, json: () => Promise.resolve({}) });
        }
        return Promise.resolve({ ok: true, json: () => Promise.resolve({}) });
    });
}

beforeEach(() => setupFetch());

afterEach(() => {
    vi.restoreAllMocks();
    document.body.innerHTML = "";
});

describe("KeyShareManagementPage — share list", () => {
    test("shows all users with access status", async () => {
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => expect(screen.getByText("alice")).toBeTruthy());
        expect(screen.getByText("bob")).toBeTruthy();
        expect(screen.getByText("carol")).toBeTruthy();
    });

    test("users in key.users have access, others do not", async () => {
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("alice"));

        // alice and bob have access (users: "alice,bob"), carol does not
        const rows = screen.getAllByRole("row");
        const aliceRow = rows.find((r) => r.textContent?.includes("alice"));
        const carolRow = rows.find((r) => r.textContent?.includes("carol"));

        expect(aliceRow?.textContent).toMatch(/has share/i);
        expect(carolRow?.textContent).toMatch(/grant access/i);
    });

    test("shows error on fetch failure", async () => {
        global.fetch = vi.fn().mockRejectedValue(new Error("network error"));
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => expect(screen.getByText(/failed to load/i)).toBeTruthy());
    });
});

describe("KeyShareManagementPage — toggle access", () => {
    test("calls save_key when grant button is clicked", async () => {
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("carol"));

        // Find the grant button for carol (does not have access)
        const grantButtons = screen.getAllByRole("button", { name: /grant/i });
        await fireEvent.click(grantButtons[0]);

        await waitFor(() =>
            expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.objectContaining({ method: "POST" }))
        );
    });

    test("calls save_key when revoke button is clicked", async () => {
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("alice"));

        // alice already has access — clicking should revoke
        const revokeButtons = screen.getAllByRole("button", { name: /revoke/i });
        await fireEvent.click(revokeButtons[0]);

        await waitFor(() =>
            expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.objectContaining({ method: "POST" }))
        );
    });
});

describe("KeyShareManagementPage — holders outside the get_users window are never dropped", () => {
    // get_users is server-capped (50 without a search term), so an authorized
    // holder can be absent from the visible table. The posted users[] list must
    // still preserve them, or a single grant/revoke silently revokes their share.
    function lastSaveKeyUsers() {
        const saveCalls = global.fetch.mock.calls.filter(([url]) => url.includes("action=save_key"));
        const [, opts] = saveCalls[saveCalls.length - 1];
        // body is a URLSearchParams built by the component
        return opts.body.getAll("users[]");
    }

    test("granting a new user keeps a holder that is not in the visible list", async () => {
        // bob holds a share but is beyond the get_users window (only alice + carol returned)
        setupFetch({ key: { key: { name: "Payroll Key", users: "alice,bob" } }, users: ["alice", "carol"] });
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("carol"));

        await fireEvent.click(screen.getByRole("button", { name: /grant/i }));

        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.anything()));
        const posted = lastSaveKeyUsers();
        expect(posted).toContain("bob"); // preserved even though invisible
        expect(posted).toContain("alice");
        expect(posted).toContain("carol"); // newly granted
    });

    test("revoking a visible user does not drop an invisible holder", async () => {
        setupFetch({ key: { key: { name: "Payroll Key", users: "alice,bob" } }, users: ["alice"] });
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("alice"));

        // Revoke alice (first row); bob (invisible holder) must survive.
        await fireEvent.click(screen.getAllByRole("button", { name: /revoke/i })[0]);

        await waitFor(() => expect(global.fetch).toHaveBeenCalledWith(expect.stringContaining("action=save_key"), expect.anything()));
        const posted = lastSaveKeyUsers();
        expect(posted).toContain("bob");
        expect(posted).not.toContain("alice");
    });

    test("an authorized holder outside the window is still shown so it can be revoked", async () => {
        setupFetch({ key: { key: { name: "Payroll Key", users: "alice,bob" } }, users: ["alice", "carol"] });
        render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("alice"));
        // bob is not in the get_users response but must appear as a holder row
        expect(screen.getByText("bob")).toBeTruthy();
    });
});

describe("KeyShareManagementPage — navigation", () => {
    test("emits back event on back button click", async () => {
        const { emitted } = render(KeyShareManagementPage, { props: { keyId: 1, keyName: "Payroll Key" } });
        await waitFor(() => screen.getByText("alice"));

        await fireEvent.click(screen.getByRole("button", { name: /back/i }));
        expect(emitted().back).toBeTruthy();
    });
});
