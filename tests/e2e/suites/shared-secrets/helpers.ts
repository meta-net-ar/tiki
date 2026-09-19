import { Page, Locator, expect } from "@playwright/test";
import { activateBootstrapTab } from "../../common/ui";

export { login, logout, ADMIN, TEST_USER } from "../../common/auth";
export { FIXTURE_CMD } from "../../common/fixtures";

// The Shared Secrets admin UI is the `sss-admin` Vue micro-frontend, mounted by
// templates/admin/include_security.tpl inside the Encryption tab. It routes in
// the client (dashboard / create / edit / shares) with no page reload, so every
// helper below waits on a rendered heading rather than on a navigation.

const ENCRYPTION_TAB_ANCHOR = "a[href='#contentadmin1-encryption']";
const ENCRYPTION_TAB_PANE = "#contentadmin1-encryption";
const APP_ROOT = "#single-spa-application\\:\\@vue-mf\\/sss-admin";

const DASHBOARD_HEADING = "Encryption Key Management";
const CREATE_HEADING = "Create Encryption Key";
const EDIT_HEADING = "Edit Key";

// ── Navigation ────────────────────────────────────────────────────────────────

export function app(page: Page): Locator {
    return page.locator(APP_ROOT);
}

export async function goToEncryptionTab(page: Page) {
    await page.goto("/tiki-admin.php?page=security");
    await page.waitForLoadState("load");
    await activateBootstrapTab(page, ENCRYPTION_TAB_ANCHOR, ENCRYPTION_TAB_PANE);
    await expectDashboard(page);
}

export async function expectDashboard(page: Page) {
    await expect(app(page).getByRole("heading", { name: DASHBOARD_HEADING })).toBeVisible();
    // The table only renders once loadKeys() resolves; waiting for the spinner
    // to disappear keeps every caller from racing the first fetch.
    await expect(app(page).locator(".spinner-border")).toHaveCount(0);
}

export async function openCreateKey(page: Page) {
    await app(page).getByRole("button", { name: "Create new key" }).click();
    await expect(app(page).getByRole("heading", { name: CREATE_HEADING })).toBeVisible();
}

export async function openEditKey(page: Page, keyName: string) {
    await keyRow(page, keyName).getByTitle("Edit key").click();
    await expect(app(page).getByRole("heading", { name: EDIT_HEADING })).toBeVisible();
}

export async function backToDashboard(page: Page) {
    await app(page).getByRole("button", { name: "Back to dashboard" }).click();
    await expectDashboard(page);
}

// ── The create / edit form ────────────────────────────────────────────────────
//
// The form's text inputs carry no id and their labels are not `for`-linked, so
// each field is reached through the Bootstrap row that holds its label. The user
// checkboxes do carry ids (`sss-user-<username>`) and are addressed directly.

function field(page: Page, labelText: string): Locator {
    return app(page).locator(".row", { hasText: labelText }).locator("input, textarea, select").first();
}

export async function fillKeyForm(page: Page, name: string, description = "", users: string[] = []) {
    if (name !== "") {
        await field(page, "Key name or domain").fill(name);
    }
    if (description) {
        await field(page, "Description").fill(description);
    }
    for (const user of users) {
        const box = app(page).locator(`#sss-user-${user}`);
        await box.waitFor({ state: "visible" });
        await box.check();
    }
}

export async function applyKeyForm(page: Page) {
    await app(page).getByRole("button", { name: "Apply" }).click();
}

export async function createKey(page: Page, name: string, description = "", users: string[] = []) {
    await openCreateKey(page);
    await fillKeyForm(page, name, description, users);
    await applyKeyForm(page);
    await expect(app(page).getByText(`Key “${name}” saved.`)).toBeVisible();
}

// ── Shares shown once, right after a save ─────────────────────────────────────

export async function getShares(page: Page): Promise<string[]> {
    // The post-save table lists one row per holder plus a first row for the
    // server's own share, which is never disclosed. Only the holder rows carry
    // a real share value.
    //
    // The table renders after save() resolves, so wait for it rather than
    // reading whatever is on screen the moment Apply was clicked.
    await expect(app(page).getByText("Save your share now.")).toBeVisible();

    const cells = app(page).locator("table tbody tr td code");
    const count = await cells.count();
    const shares: string[] = [];
    for (let i = 0; i < count; i++) {
        const text = ((await cells.nth(i).textContent()) ?? "").trim();
        if (text && text !== "Server share — not disclosed") {
            shares.push(text);
        }
    }
    return shares;
}

// ── The dashboard table ───────────────────────────────────────────────────────

export function keyRow(page: Page, keyName: string): Locator {
    return app(page).locator("tbody tr").filter({ hasText: keyName });
}

export async function assertKeyInTable(page: Page, keyName: string) {
    await expect(keyRow(page, keyName)).toHaveCount(1);
}

export async function assertKeyNotInTable(page: Page, keyName: string) {
    await expect(keyRow(page, keyName)).toHaveCount(0);
}

// ── Deletion ──────────────────────────────────────────────────────────────────
//
// KeyDashboardPage.deleteKey() gates on a native window.confirm(), so the
// dialog has to be answered before the click resolves.

export async function deleteKey(page: Page, keyName: string, accept = true) {
    page.once("dialog", (dialog) => (accept ? dialog.accept() : dialog.dismiss()));
    await keyRow(page, keyName).getByTitle("Delete key").click();
    await expect(app(page).locator(".spinner-border")).toHaveCount(0);
}
