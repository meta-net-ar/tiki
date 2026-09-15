import { test, expect } from '../../common/test';
import { login, logout, createWikiPage, deleteWikiPage } from "./helpers";

const PAGE_NAME = `E2E-Wiki-${Date.now()}`;
const PAGE_CONTENT = "This page was created by the Playwright E2E test suite.";

test.describe("TC-01 — Wiki page lifecycle", () => {
    test.describe.configure({ mode: "serial" });

    test.afterAll(async ({ browser }) => {
        // Safety-net cleanup — also runs if an earlier test failed mid-way.
        const ctx = await browser.newContext();
        const p = await ctx.newPage();
        try {
            await login(p);
            await deleteWikiPage(p, PAGE_NAME);
        } catch (_) {
            // Ignore — page may already be deleted by TC-01-3 or may never have been created
        } finally {
            await ctx.close();
        }
    });

    test("Admin can create a wiki page", async ({ page }) => {
        await login(page);
        await createWikiPage(page, PAGE_NAME, PAGE_CONTENT);
        // A successful save redirects to the page view; assert we left the editor
        // (which would also echo the name/content) and the content actually rendered.
        await expect(page).not.toHaveURL(/tiki-editpage/);
        await expect(page.locator("body")).toContainText(PAGE_CONTENT);
    });

    test("Created page is accessible and shows content", async ({ page }) => {
        await login(page);
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(PAGE_NAME)}`);
        await page.waitForLoadState("networkidle");
        await expect(page.locator("body")).toContainText(PAGE_CONTENT);
    });

    test("Admin can delete the wiki page", async ({ page }) => {
        await login(page);
        await deleteWikiPage(page, PAGE_NAME);

        // After deletion Tiki redirects away (e.g. listpages). Navigate back to verify the page is gone.
        await page.goto(`/tiki-index.php?page=${encodeURIComponent(PAGE_NAME)}`, {
            waitUntil: "networkidle",
        });
        const bodyText = (await page.locator("body").textContent()) ?? "";
        const finalUrl = page.url();
        // v27.x shows "does not exist" text; master redirects to edit mode for nonexistent pages
        expect(
            bodyText.includes("does not exist") ||
            bodyText.includes("not exist yet") ||
            bodyText.includes("Create page") ||
            finalUrl.includes("editpage") ||
            finalUrl.includes("tiki-editpage") ||
            !bodyText.includes(PAGE_CONTENT) // content no longer present = page gone
        ).toBe(true);
    });
});
