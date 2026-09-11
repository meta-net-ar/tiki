import { Page, expect } from "@playwright/test";
import { activateBootstrapTab } from "../../common/ui";

export { login, logout, ADMIN, TEST_USER } from "../../common/auth";
export { FIXTURE_CMD } from "../../common/fixtures";

// ── Navigation ────────────────────────────────────────────────────────────────

// The Encryption tab on tiki-admin.php?page=security
const ENCRYPTION_TAB_ANCHOR = "a[href='#contentadmin1-encryption']";
const ENCRYPTION_TAB_PANE = "#contentadmin1-encryption";

export async function goToEncryptionTab(page: Page) {
    await page.goto("/tiki-admin.php?page=security");
    await page.waitForLoadState("load");
    await activateBootstrapTab(page, ENCRYPTION_TAB_ANCHOR, ENCRYPTION_TAB_PANE);
}

export async function openCreateKeyTab(page: Page) {
    await page.locator("a[href='#contentencryption-2']").click();
    await page.locator("#contentencryption-2").waitFor({ state: "visible" });
}

export async function openAvailableKeysTab(page: Page) {
    await page.locator("a[href='#contentencryption-1']").click();
    await page.locator("#contentencryption-1").waitFor({ state: "visible" });
}

// ── Key creation ──────────────────────────────────────────────────────────────

export async function createKey(
    page: Page,
    name: string,
    description = "",
    users: string[] = []
) {
    await openCreateKeyTab(page);
    // Scope to the Create Key tab pane to avoid strict-mode collision
    // (multiple hidden input[name="name"] fields exist on the page)
    await page.locator('#contentencryption-2 input[name="name"]').fill(name);
    if (description) {
        await page
            .locator('#contentencryption-2 textarea[name="description"]')
            .fill(description);
    }
    if (users.length > 0) {
        // The PHP controller reads users via $input->users->text() then parses via
        // str_getcsv($value, ',') — it expects a single comma-separated string, NOT
        // multiple form fields. A native multi-select submits separate users=X&users=Y
        // pairs, and PHP only captures the last value for a non-array field name.
        // Fix: remove every element named "users" (native select + Select2 sentinels),
        // then inject one hidden input with the comma-joined list.
        await page.evaluate((userList: string[]) => {
            // Remove native select and any Select2 hidden inputs for "users"
            document
                .querySelectorAll('[name="users"]')
                .forEach((el) => el.remove());
            // Inject a single hidden input with comma-separated users
            // The admin security form spans the full page — the tab pane does not wrap it.
            const form =
                (document.querySelector(
                    'form#security, form[action*="tiki-admin"][id]'
                ) as HTMLFormElement | null) ??
                (document.querySelector(
                    'form[action*="tiki-admin"]'
                ) as HTMLFormElement | null);
            if (!form) throw new Error("Admin security form not found");
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "users";
            hidden.value = userList.join(",");
            form.appendChild(hidden);
        }, users);
    }
    await page.locator('input[type="submit"][value="Apply"]').last().click();
    await page.waitForLoadState("load");
}

// Select a user in the Select2 multi-picker via jQuery/Select2 API.
export async function selectUser(page: Page, username: string) {
    await page.evaluate((u: string) => {
        const el = document.querySelector(
            "#user_selector_1"
        ) as HTMLSelectElement | null;
        if (!el) throw new Error("#user_selector_1 not found");
        for (const opt of el.options) {
            if (opt.value === u) opt.selected = true;
        }
        if ((window as any).jQuery) {
            (window as any)
                .jQuery("#user_selector_1")
                .val(
                    Array.from(el.options)
                        .filter((o) => o.selected)
                        .map((o) => o.value)
                )
                .trigger("change");
        } else {
            el.dispatchEvent(new Event("change", { bubbles: true }));
        }
    }, username);
}

// ── Assertions ────────────────────────────────────────────────────────────────

export async function getShares(page: Page): Promise<string[]> {
    // After key creation, shares are shown in a warning remarksbox as an <ol>
    const items = page.locator(
        ".remarksbox-warning ol li, .alert-warning ol li"
    );
    const count = await items.count();
    const shares: string[] = [];
    for (let i = 0; i < count; i++) {
        shares.push((await items.nth(i).textContent()) ?? "");
    }
    return shares.map((s) => s.trim()).filter(Boolean);
}

export async function assertKeyInTable(page: Page, keyName: string) {
    await openAvailableKeysTab(page);
    await expect(page.locator("#contentencryption-1 table")).toContainText(
        keyName
    );
}

// ── Key ID lookup ─────────────────────────────────────────────────────────────

export async function getKeyId(page: Page, keyName: string): Promise<number> {
    await openAvailableKeysTab(page);
    const row = page
        .locator("#contentencryption-1 table tr")
        .filter({ hasText: keyName });
    const href =
        (await row.locator('a[href*="encryption_key"]').getAttribute("href")) ??
        "";
    const m = href.match(/encryption_key=(\d+)/);
    return m ? parseInt(m[1]) : 0;
}

// ── Edit key page ─────────────────────────────────────────────────────────────

export async function goToEditKeyPage(page: Page, keyId: number) {
    await page.goto(`/tiki-admin.php?page=security&encryption_key=${keyId}`);
    await page.waitForLoadState("load");
    await activateBootstrapTab(page, ENCRYPTION_TAB_ANCHOR, ENCRYPTION_TAB_PANE);
    // The inner Edit Key sub-tab is auto-activated by the template's {jq} snippet
    await page.locator("#contentencryption-2").waitFor({ state: "visible" });
}

// ── Delete key ─────────────────────────────────────────────────────────────────
// confirmPopup() shows a Bootstrap modal (NOT a native dialog).
// Modal: #bootstrap-modal  Cancel: button.btn-dismiss  Confirm: input[value="OK"]

export async function deleteKey(page: Page, keyName: string) {
    await openAvailableKeysTab(page);
    const row = page
        .locator("#contentencryption-1 table tr")
        .filter({ hasText: keyName });
    // Use .first() to be resilient against stale duplicate rows from prior failed test runs
    await row.locator('button[name="key_delete"]').first().click();
    // Wait for Bootstrap modal to become visible
    await page
        .locator("#bootstrap-modal.show")
        .waitFor({ state: "visible", timeout: 10000 });
    // Click OK to confirm deletion
    await page
        .locator('#bootstrap-modal input[type="submit"][value="OK"]')
        .click();
    await page.waitForLoadState("load");
}
