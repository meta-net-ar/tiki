import { test, expect } from '../../common/test';
import { useSuiteFixtures } from '../../common/fixtures';
import { login, logout } from './helpers';

const SECRET_VALUE = 'TC04-secret-data-xk7q';

// PHP fixture creates key + tracker + encrypted field (see fixtures/shared-secrets/).
const fx = useSuiteFixtures(test);

let trackerId: number;
let fieldId: number;
let itemId: number;

test.describe('TC-04 — Tracker field encryption', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async () => {
    ({ trackerId, fieldId } = fx.data);
  });

  // ── Test 1: Authorized user (Test1) can write an encrypted field ─────────────
  test('Authorized user can submit a value into an encrypted tracker field', async ({ page }) => {
    await login(page, 'Test1', 'test1pass');
    await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    // Click the "Add Item" / "Create Item" link or button
    const addBtn = page.locator('a:has-text("Add"), a:has-text("Create"), input[value*="Add"], input[value*="Create"]').first();
    await addBtn.click();
    await page.waitForLoadState('networkidle');

    // Fill the encrypted text field (Tiki uses name="ins_{fieldId}" for tracker item fields)
    await page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`).fill(SECRET_VALUE);

    // The form opens as a Bootstrap modal — the submit button says "Create"
    await page.locator('.modal-footer button:has-text("Create"), button:has-text("Create")').last().click();
    // After modal closes, we're back on the tracker list page showing the new item
    await page.waitForSelector('a[href*="itemId"]', { timeout: 30000 });

    // The decrypted value is visible in the list for authorized Test1
    await expect(page.locator('body')).toContainText(SECRET_VALUE);

    // Capture itemId from the first item link
    const itemHref = await page.locator('a[href*="itemId"]').first().getAttribute('href') ?? '';
    const m = itemHref.match(/itemId=(\d+)/);
    expect(m).not.toBeNull();
    itemId = parseInt(m![1]);
    expect(itemId).toBeGreaterThan(0);

    await logout(page);
  });

  // ── Test 2: Authorized user (Test1) reads back the decrypted value ───────────
  test('Authorized user reads decrypted value from encrypted tracker field', async ({ page }) => {
    await login(page, 'Test1', 'test1pass');
    await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    // The decrypted value must appear on the page
    await expect(page.locator('body')).toContainText(SECRET_VALUE);
    await logout(page);
  });

  // ── Test 3: Unauthorized user (admin) cannot read the decrypted value ────────
  test('Unauthorized user cannot read decrypted value from encrypted tracker field', async ({ page }) => {
    // Admin has no key share — decryption fails → field shows error, value is empty
    await login(page, 'admin', 'admin1234');
    await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    // The plaintext secret must NOT appear
    await expect(page.locator('body')).not.toContainText(SECRET_VALUE);

    // Some indicator that the field is encrypted / error is shown
    const bodyText = await page.locator('body').textContent() ?? '';
    const hasEncryptionIndicator = bodyText.includes('encrypted') || bodyText.includes('TC04-EncKey') || bodyText.includes('manually entered key');
    expect(hasEncryptionIndicator).toBe(true);

    await logout(page);
  });
});
