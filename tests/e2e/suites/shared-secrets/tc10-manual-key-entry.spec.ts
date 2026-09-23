import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, logout } from "./helpers";

// TC-10 — Manual key entry on the item VIEW page.
//
// The sibling journey in tc06 unlocks from a create form. This one unlocks from
// the view page, which is a different path: EnterKeyModal dispatches tiki:unlocked
// on the field host and @tiki/ui-utils fills the `encrypted-view-<field>-<item>`
// span in place, with no page reload.

const SECRET_VALUE = "TC10-secret-xk7q";

const fx = useSuiteFixtures(test);

let trackerId: number;
let fieldId: number;
let itemId: number;
let shareString: string;

test.describe("TC-10 — Manual key entry", () => {
    test.describe.configure({ mode: "serial" });

    test.beforeAll(async () => {
        ({ trackerId, fieldId } = fx.data);
        shareString = fx.data.share;
    });

    test("A share entered on the item view reveals the value in place", async ({ page }) => {
        // Test1 holds a share, so it can write the value.
        await login(page, "Test1", "test1pass");
        await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await page.locator('a:has-text("Add"), a:has-text("Create"), input[value*="Add"], input[value*="Create"]').first().click();
        await expect(page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`)).toBeVisible({ timeout: 20000 });
        await page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`).fill(SECRET_VALUE);
        await page.locator('.modal-footer button:has-text("Create"), button:has-text("Create")').last().click();
        await page.waitForSelector('a[href*="itemId"]', { timeout: 30000 });

        const itemHref = (await page.locator('a[href*="itemId"]').first().getAttribute("href")) ?? "";
        const m = itemHref.match(/itemId=(\d+)/);
        expect(m).not.toBeNull();
        itemId = parseInt(m![1]);
        await logout(page);

        // Admin holds none, so the view page shows the locked badge and the entry link.
        await login(page, "admin", "admin1234");
        await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        // Wait for the rendered locked badge first, so the negative check below
        // cannot pass on a page that has not rendered the field yet.
        await expect(page.locator("tiki-encrypted-field[locked]")).toBeVisible();
        await expect(page.locator("body")).not.toContainText(SECRET_VALUE);

        const entryLink = page.locator("a.encryption-key-entry");
        await expect(entryLink).toBeVisible();
        await entryLink.click();

        const input = page.locator(".enter-key-modal input[name='shared_key']");
        await input.waitFor({ state: "visible", timeout: 10000 });
        await input.fill(shareString);
        await page.locator('.enter-key-modal .modal-footer button:has-text("Submit")').click();

        // Revealed in place: the value appears without navigating away.
        await expect(page.locator(`#encrypted-view-${fieldId}-${itemId}`)).toHaveText(SECRET_VALUE);
        expect(page.url()).toContain("tiki-view_tracker_item");

        await logout(page);
    });
});
