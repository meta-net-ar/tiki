import { test, expect } from '../../common/test';
import { login } from "./helpers";

test.describe("TC-02 — Wiki page access", () => {
    test("Wiki page listing is accessible to admin", async ({ page }) => {
        await login(page);
        await page.goto("/tiki-listpages.php");
        await page.waitForLoadState("networkidle");
        // Page must load and contain the listing table
        await expect(page.locator("body")).not.toContainText("Permission denied");
        expect(await page.locator("table, .table").count()).toBeGreaterThan(0);
    });

    test("Nonexistent page leads to create/edit flow for admin", async ({ page }) => {
        await login(page);
        const fakePage = `E2E-DoesNotExist-${Date.now()}`;
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(fakePage)}`);
        await page.waitForLoadState("networkidle");

        // Tiki's behavior for nonexistent pages varies by version/config:
        //   - v27.x: shows "This page does not exist yet" with a create link
        //   - master: may redirect directly to the edit form
        // Accept any of these outcomes.
        const bodyText = (await page.locator("body").textContent()) ?? "";
        const finalUrl = page.url();

        // Tiki's behavior for nonexistent pages varies by version/config:
        //   - v27.x: shows "This page does not exist yet" with a create link
        //   - master: redirects to the edit form, or shows a create action link
        const isEditRedirect =
            finalUrl.includes("tiki-editpage.php") ||
            finalUrl.includes("editpage");
        const hasCreateHint =
            bodyText.includes("does not exist") ||
            bodyText.includes("not exist yet") ||
            bodyText.includes("Create page");
        const hasEditForm =
            (await page.locator('textarea[name="edit"], input[name="save"], [contenteditable="true"]').count()) > 0;
        // A missing page offers a concrete link into the editor (tiki-editpage.php).
        const hasEditLink =
            (await page.locator('a[href*="tiki-editpage"]').count()) > 0;

        expect(isEditRedirect || hasCreateHint || hasEditForm || hasEditLink).toBe(true);
    });
});
