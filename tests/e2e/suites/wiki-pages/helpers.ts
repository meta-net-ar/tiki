import { Page } from "@playwright/test";

export { login, logout, ADMIN, TEST_USER } from "../../common/auth";

// ── Page creation ──────────────────────────────────────────────────────────────

export async function createWikiPage(
    page: Page,
    pageName: string,
    content: string
) {
    await page.goto(`/tiki-editpage.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");

    // Tiki master uses textarea[name="edit"] (id="editwiki"), not textarea[name="content"]
    await page.locator('textarea[name="edit"], textarea#editwiki').fill(content);

    await page.locator('input[name="save"]').first().click();
    await page.waitForLoadState("networkidle");
}

// ── Page deletion ──────────────────────────────────────────────────────────────

export async function deleteWikiPage(page: Page, pageName: string) {
    // In Tiki master, tiki-removepage.php is gone.
    // Deletion is via the page bar Bootstrap modal on the page view.
    await page.goto(`/tiki-index.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");

    // The delete action is inside the "More" dropdown in the page bar
    const moreBtn = page.locator('button:has-text("More"), .dropdown-toggle:has-text("More")');
    if (await moreBtn.isVisible()) {
        await moreBtn.click();
        await page.waitForTimeout(300); // let dropdown open
    }

    const deleteLink = page.locator('a[href*="action=remove_pages"]');
    if (!(await deleteLink.isVisible())) return; // page may not exist or no permission

    await deleteLink.click();

    // Wait for the Bootstrap confirm modal
    const modal = page.locator('.modal.show, .modal[style*="display: block"]');
    await modal.waitFor({ state: "visible", timeout: 8000 });

    // Confirm deletion and wait for the redirect to settle
    const confirmBtn = modal.locator(
        'button:has-text("Remove"), button:has-text("Delete"), button:has-text("OK"), input[value="OK"]'
    );
    await Promise.all([
        page.waitForNavigation({ waitUntil: "networkidle", timeout: 15000 }).catch(() => null),
        confirmBtn.first().click(),
    ]);
}

// ── Assertions ─────────────────────────────────────────────────────────────────

export async function pageExists(page: Page, pageName: string): Promise<boolean> {
    await page.goto(`/tiki-index.php?page=${encodeURIComponent(pageName)}`);
    await page.waitForLoadState("networkidle");
    const bodyText = (await page.locator("body").textContent()) ?? "";
    return (
        !bodyText.includes("does not exist") &&
        !bodyText.includes("not exist yet")
    );
}
