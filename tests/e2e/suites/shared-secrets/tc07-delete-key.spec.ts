import { test, expect } from '../../common/test';
import { login, goToEncryptionTab, createKey, openAvailableKeysTab } from './helpers';

// confirmPopup() in Tiki shows a Bootstrap modal, NOT a native browser dialog.
// Modal id: #bootstrap-modal  Cancel: button.btn-dismiss  Confirm: input[value="OK"]

test.describe('TC-07 — Key deletion', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await goToEncryptionTab(page);
  });

  test('Cancel on delete confirmation keeps the key', async ({ page }) => {
    const keyName = `TestKey-Del-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);
    await openAvailableKeysTab(page);

    const row = page.locator('#contentencryption-1 table tr').filter({ hasText: keyName });
    await row.locator('button[name="key_delete"]').first().click();

    // Wait for Bootstrap modal
    await page.locator('#bootstrap-modal.show').waitFor({ state: 'visible', timeout: 5000 });

    // Click Close (cancel) — key must stay
    await page.locator('#bootstrap-modal button.btn-dismiss').click();
    await page.locator('#bootstrap-modal.show').waitFor({ state: 'hidden', timeout: 5000 });

    // Key must still be there
    await expect(page.locator('#contentencryption-1 table')).toContainText(keyName);

    // Cleanup — now actually delete
    await row.locator('button[name="key_delete"]').first().click();
    await page.locator('#bootstrap-modal.show').waitFor({ state: 'visible', timeout: 5000 });
    await page.locator('#bootstrap-modal input[type="submit"][value="OK"]').click();
    await page.waitForLoadState('networkidle');
  });

  test('Confirming delete removes key from table', async ({ page }) => {
    const keyName = `TestKey-Confirm-Del-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);
    await openAvailableKeysTab(page);

    const row = page.locator('#contentencryption-1 table tr').filter({ hasText: keyName });
    await row.locator('button[name="key_delete"]').first().click();

    await page.locator('#bootstrap-modal.show').waitFor({ state: 'visible', timeout: 5000 });
    await page.locator('#bootstrap-modal input[type="submit"][value="OK"]').click();

    // The confirm submits a POST that deletes the key server-side, but the admin security
    // page does not refresh the Available keys table in place. Reload the tab fresh so the
    // assertion reads post-delete server state — waiting on 'networkidle' alone races the
    // stale in-page DOM and fails non-deterministically.
    await page.waitForLoadState('networkidle').catch(() => {});
    await goToEncryptionTab(page);
    await openAvailableKeysTab(page);

    await expect(page.locator('#contentencryption-1 table')).not.toContainText(keyName);
  });

  test('Delete confirmation modal mentions data loss warning', async ({ page }) => {
    const keyName = `TestKey-Warning-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);
    await openAvailableKeysTab(page);

    const row = page.locator('#contentencryption-1 table tr').filter({ hasText: keyName });
    await row.locator('button[name="key_delete"]').first().click();

    await page.locator('#bootstrap-modal.show').waitFor({ state: 'visible', timeout: 5000 });

    // The modal title should mention data loss
    const modalTitle = await page.locator('#bootstrap-modal .modal-title').textContent();
    expect(modalTitle?.toLowerCase()).toContain('lost');

    // Cleanup — confirm deletion
    await page.locator('#bootstrap-modal input[type="submit"][value="OK"]').click();
    await page.waitForLoadState('networkidle');
  });
});
