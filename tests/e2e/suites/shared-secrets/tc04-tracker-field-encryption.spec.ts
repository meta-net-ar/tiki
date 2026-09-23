import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, logout } from "./helpers";

const SECRET_VALUE = "TC04-secret-data-xk7q";

// PHP fixture creates key + tracker + encrypted field (see fixtures/shared-secrets/).
const fx = useSuiteFixtures(test);

let trackerId: number;
let fieldId: number;

test.describe("TC-04 — Tracker field encryption", () => {
    test("A holder writes and reads the field; a non-holder sees neither", async ({ page }) => {
        ({ trackerId, fieldId } = fx.data);

        await login(page, "Test1", "test1pass");
        await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await page.locator('a:has-text("Add"), a:has-text("Create"), input[value*="Add"], input[value*="Create"]').first().click();
        await expect(page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`)).toBeVisible({ timeout: 20000 });

        // Tiki names tracker item inputs ins_{fieldId}.
        await page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`).fill(SECRET_VALUE);
        await page.locator('.modal-footer button:has-text("Create"), button:has-text("Create")').last().click();
        await page.waitForSelector('a[href*="itemId"]', { timeout: 30000 });
        await expect(page.locator("body")).toContainText(SECRET_VALUE);

        const itemHref = (await page.locator('a[href*="itemId"]').first().getAttribute("href")) ?? "";
        const m = itemHref.match(/itemId=(\d+)/);
        expect(m).not.toBeNull();
        const itemId = parseInt(m![1]);
        expect(itemId).toBeGreaterThan(0);

        // The holder reads the plaintext back on the item page.
        await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await expect(page.locator("body")).toContainText(SECRET_VALUE);
        await logout(page);

        // Admin holds no share: no plaintext, and the field says so.
        // The service-level refusal is Encryption/ControllerTest's
        // testFailDecryptionWhenMissingSharedKey.
        await login(page, "admin", "admin1234");
        await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        // Wait for the rendered locked badge first, so the negative check below
        // cannot pass on a page that has not rendered the field yet.
        await expect(page.locator("tiki-encrypted-field[locked]")).toBeVisible();
        await expect(page.locator("body")).not.toContainText(SECRET_VALUE);
        await logout(page);
    });
});
