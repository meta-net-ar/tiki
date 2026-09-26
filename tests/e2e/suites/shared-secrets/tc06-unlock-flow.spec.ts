import { test, expect, type Page } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, logout } from "./helpers";

// TC-06 — Encrypted field unlock flow
//
// Two journeys, both on a tracker create form:
//   1. A share holder writes the field; a user holding no share sees it locked,
//      with the entry link that opens the `tiki-enter-key-modal` Vue widget.
//   2. In that widget a wrong share is refused and the field stays locked; the
//      right share unlocks it, and the item view renders the plaintext.
//
// The per-endpoint assertions this file used to carry live in
// lib/test/Core/Services/Encryption/ControllerTest.php, where they run on every
// merge request instead of behind a manual pipeline button.

const SECRET_VALUE = "TC06-secret-yp3m";
const WRONG_SHARE = "wrong-share-definitely-invalid-xxxx1234";

const fx = useSuiteFixtures(test);

let trackerId: number;
let fieldId: number;
let shareString: string;
let itemId: number;

test.describe("TC-06 — Encrypted field unlock flow", () => {
    test.describe.configure({ mode: "serial" });

    test.beforeAll(async () => {
        ({ trackerId, fieldId } = fx.data);
        shareString = fx.data.share;
    });

    /** Open the tracker's Create Item modal and wait for its AJAX content. */
    async function openTrackerCreateForm(page: Page) {
        await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await page.locator('a:has-text("Create Item"), a:has-text("Add"), input[value*="Add"]').first().click();
        await page.locator(".modal.show").waitFor({ state: "visible", timeout: 15000 });
        await page.locator('.modal.show .modal-footer button:has-text("Create")').waitFor({ state: "visible", timeout: 20000 });
    }

    function createFormField(page: Page) {
        return page.locator(`.modal.show input[name="ins_${fieldId}"], .modal.show textarea[name="ins_${fieldId}"]`);
    }

    /**
     * Open the enter-key modal from the tracker LIST row rather than from inside the
     * Create Item modal: headless Chrome does not reliably stack two Bootstrap modals,
     * and the session share the modal stores is what the assertions are really about.
     */
    async function openEnterKeyModalFromList(page: Page) {
        await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await page.locator("a.encryption-key-entry").first().click();
        await page.locator(".enter-key-modal input[name='shared_key']").waitFor({ state: "visible", timeout: 15000 });
    }

    async function submitShare(page: Page, share: string) {
        const response = page.waitForResponse((r) => r.url().includes("action=enter_key") && r.request().method() === "POST", { timeout: 20000 });
        await page.locator(".enter-key-modal input[name='shared_key']").fill(share);
        await page.locator('.enter-key-modal .modal-footer button:has-text("Submit")').click();
        return response;
    }

    // ── Journey 1 ─────────────────────────────────────────────────────────────

    test("An authorized user writes the field; an unauthorized one sees it locked", async ({ page }) => {
        await login(page, "Test1", "test1pass");
        await openTrackerCreateForm(page);

        const field = createFormField(page);
        await expect(field).toBeVisible();
        await expect(field).toBeEnabled();
        await expect(page.locator(".modal.show tiki-encrypted-field[locked]")).toHaveCount(0);

        // Create the item so the second journey has something to decrypt.
        await field.fill(SECRET_VALUE);
        await page.locator('.modal-footer button:has-text("Create"), button:has-text("Create")').last().click();
        await page.waitForSelector('a[href*="itemId"]', { timeout: 30000 });

        const itemHref = (await page.locator('a[href*="itemId"]').first().getAttribute("href")) ?? "";
        const m = itemHref.match(/itemId=(\d+)/);
        expect(m).not.toBeNull();
        itemId = parseInt(m![1]);
        expect(itemId).toBeGreaterThan(0);

        await logout(page);

        // Admin holds no share for this key, so the same field renders locked.
        await login(page, "admin", "admin1234");
        await openTrackerCreateForm(page);

        const locked = page.locator(".modal.show tiki-encrypted-field[locked]");
        await expect(locked).toBeVisible();
        await expect(locked).toHaveAttribute("data-item-id", "0");
        await expect(createFormField(page)).toBeDisabled();
        await expect(page.locator(".modal.show a.encryption-key-entry")).toBeVisible();

        await logout(page);
    });

    // ── Journey 2 ─────────────────────────────────────────────────────────────

    test("A wrong share is refused and the right one unlocks the session", async ({ page }) => {
        await login(page, "admin", "admin1234");

        await openEnterKeyModalFromList(page);
        await submitShare(page, WRONG_SHARE);

        // The modal stays open and surfaces the refusal rather than closing on error.
        await expect(page.locator(".enter-key-modal")).toBeVisible();
        await expect(page.locator(".enter-key-modal .alert-danger")).toBeVisible();

        // The refusal left nothing usable in the session: the field is still locked.
        await openTrackerCreateForm(page);
        await expect(page.locator(".modal.show tiki-encrypted-field[locked]")).toBeVisible();
        await expect(createFormField(page)).toBeDisabled();

        // The correct share unlocks it.
        await openEnterKeyModalFromList(page);
        await submitShare(page, shareString);
        await expect(page.locator(".enter-key-modal")).toHaveCount(0);

        await openTrackerCreateForm(page);
        await expect(createFormField(page)).toBeEnabled();
        await expect(page.locator(".modal.show tiki-encrypted-field[locked]")).toHaveCount(0);

        // And the stored item now reads back in plaintext.
        await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await expect(page.locator("body")).toContainText(SECRET_VALUE);

        // A correct entry is not session-scoped: action_enter_key stores the share
        // under the user's login phrase (storeCurrentUserShare), so admin keeps
        // access across sessions until the key is regenerated. Asserting the
        // opposite is the easy mistake here.
        await logout(page);
        await login(page, "admin", "admin1234");
        await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
        await page.waitForLoadState("load");
        await expect(page.locator("body")).toContainText(SECRET_VALUE);
        await expect(page.locator("tiki-encrypted-field[locked]")).toHaveCount(0);

        await logout(page);
    });
});
