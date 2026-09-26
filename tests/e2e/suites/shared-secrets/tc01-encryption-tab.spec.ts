import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, goToEncryptionTab, app, assertKeyInTable } from "./helpers";

// tc01_setup.php creates one key so the dashboard renders its table rather than
// its empty state, whatever order the suites run in.
useSuiteFixtures(test);

test.describe("TC-01 — Encryption admin app loads", () => {
    test("The Encryption tab mounts the sss-admin app and renders the key table", async ({ page }) => {
        await login(page);
        await goToEncryptionTab(page);
        await assertKeyInTable(page, "TC01-EncKey");

        // goToEncryptionTab already waits on the dashboard heading. What is worth
        // asserting beyond that is that the app rendered a real table rather than
        // its empty state or an error alert, and that the page carries no PHP
        // error the mount would otherwise hide.
        const table = app(page).locator("table");
        await expect(table).toBeVisible();
        await expect(table.locator("th").filter({ hasText: "Key name" })).toBeVisible();
        await expect(table.locator("th").filter({ hasText: "Shares" })).toBeVisible();
        await expect(table.locator("th").filter({ hasText: "Users" })).toBeVisible();
        await expect(app(page).locator(".alert-danger")).toHaveCount(0);

        const body = await page.locator("body").textContent();
        expect(body).not.toContain("Fatal error");
        expect(body).not.toContain("Class not found");
        expect(body).not.toContain("Uncaught Error");
    });
});
