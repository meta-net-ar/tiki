import { test, expect } from '../../common/test';
import { useSuiteFixtures } from '../../common/fixtures';
import { login, logout } from './helpers';

const SECRET_VALUE = 'TC10-secret-xk7q';

const fx = useSuiteFixtures(test);

let trackerId: number;
let fieldId: number;
let itemId: number;
let shareString: string;

test.describe('TC-10 — Manual key entry', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async () => {
    ({ trackerId, fieldId } = fx.data);
    shareString = fx.data.share;
  });

  // ── Test 1: Test1 writes a value into the encrypted field ────────────────────
  test('Authorized user (Test1) creates a tracker item with an encrypted field', async ({ page }) => {
    await login(page, 'Test1', 'test1pass');
    await page.goto(`/tiki-view_tracker.php?trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    const addBtn = page.locator('a:has-text("Add"), a:has-text("Create"), input[value*="Add"], input[value*="Create"]').first();
    await addBtn.click();
    await page.waitForLoadState('networkidle');

    await page.locator(`input[name="ins_${fieldId}"], textarea[name="ins_${fieldId}"]`).fill(SECRET_VALUE);
    await page.locator('.modal-footer button:has-text("Create"), button:has-text("Create")').last().click();
    await page.waitForSelector('a[href*="itemId"]', { timeout: 30000 });
    await expect(page.locator('body')).toContainText(SECRET_VALUE);

    const itemHref = await page.locator('a[href*="itemId"]').first().getAttribute('href') ?? '';
    const m = itemHref.match(/itemId=(\d+)/);
    expect(m).not.toBeNull();
    itemId = parseInt(m![1]);
    expect(itemId).toBeGreaterThan(0);

    await logout(page);
  });

  // ── Test 2: Admin sees encrypted indicator and manual entry link ──────────────
  test('Unauthorized user (admin) sees error indicator and manual entry link', async ({ page }) => {
    await login(page, 'admin', 'admin1234');
    await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    // Plaintext secret must NOT appear
    await expect(page.locator('body')).not.toContainText(SECRET_VALUE);

    // Encryption error indicator must be present
    const bodyText = await page.locator('body').textContent() ?? '';
    expect(
      bodyText.includes('encrypted') || bodyText.includes('manually entered key')
    ).toBe(true);

    // The manual entry link must exist
    await expect(page.locator('a.encryption-key-entry')).toBeVisible();

    await logout(page);
  });

  // ── Test 3: Admin enters share manually → field decrypts ─────────────────────
  test('Admin enters share string manually and reads decrypted value', async ({ page }) => {
    await login(page, 'admin', 'admin1234');
    await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
    await page.waitForLoadState('networkidle');

    // Click the "Try with a manually entered key." link (opens Bootstrap modal)
    await page.locator('a.encryption-key-entry').click();

    // Wait for modal form OR standalone page with shared_key input
    await page.locator('input[name="shared_key"]').waitFor({ state: 'visible', timeout: 10000 });

    // Fill the share string
    await page.locator('input[name="shared_key"]').fill(shareString);

    // Submit the form — Bootstrap modal renders the submit as a button in .modal-footer
    // After submit, Tiki stores the share in $_SESSION and redirects to the tracker item page
    await page.locator('.modal.show .modal-footer button:has-text("Submit"), .modal.show button:has-text("Submit")').first().click();

    // Wait for the redirect/reload to settle — do NOT call page.goto() here;
    // the form submission already navigates to the tracker item page
    await page.waitForLoadState('networkidle', { timeout: 30000 });

    // If not already on the item page, navigate there now
    if (!page.url().includes('tiki-view_tracker_item')) {
      await page.goto(`/tiki-view_tracker_item.php?itemId=${itemId}&trackerId=${trackerId}`);
      await page.waitForLoadState('networkidle');
    }

    // The decrypted value must now appear
    await expect(page.locator('body')).toContainText(SECRET_VALUE);

    await logout(page);
  });
});
