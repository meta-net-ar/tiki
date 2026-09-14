import { test, expect } from '../../common/test';
import { login, goToEncryptionTab, createKey, getShares, assertKeyInTable, deleteKey, openCreateKeyTab } from './helpers';

test.describe('TC-02 — Create a key and verify shares', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await goToEncryptionTab(page);
  });

  test('Creating a key with empty name fails gracefully', async ({ page }) => {
    await openCreateKeyTab(page);
    await page.locator('input[type="submit"][value="Apply"]').last().click();
    await page.waitForLoadState('networkidle');

    // Check that no key with an empty name exists in the table
    // (Tiki silently rejects empty names — no key is created)
    await page.locator("a[href='#contentencryption-1']").click();
    await page.locator('#contentencryption-1').waitFor({ state: 'visible' });
    const nameCells = page.locator('#contentencryption-1 table td:first-child');
    const count = await nameCells.count();
    for (let i = 0; i < count; i++) {
      const text = await nameCells.nth(i).textContent();
      // Every key name cell must be non-empty (no blank-name key was created)
      expect(text?.trim()).not.toBe('');
    }
  });

  test('Creating a key shows shares only once after save', async ({ page }) => {
    const keyName = `TestKey-${Date.now()}`;
    // Must select at least one user — otherwise "Key must be shared with minimum of one user"
    await createKey(page, keyName, 'Automated test key', ['Test1']);

    const shares = await getShares(page);
    expect(shares.length).toBeGreaterThan(0);

    // Reload — shares must NOT be shown again
    await page.reload();
    await goToEncryptionTab(page);
    const sharesAfterReload = await getShares(page);
    expect(sharesAfterReload.length).toBe(0);

    // Cleanup
    await deleteKey(page, keyName);
  });

  test('Created key appears in Available keys table', async ({ page }) => {
    const keyName = `TestKey-Table-${Date.now()}`;
    await createKey(page, keyName, 'Table test', ['Test1']);
    await assertKeyInTable(page, keyName);

    // Cleanup
    await deleteKey(page, keyName);
  });

  test('Key table row has correct columns', async ({ page }) => {
    const keyName = `TestKey-Cols-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);
    await page.locator("a[href='#contentencryption-1']").click();

    const row = page.locator('#contentencryption-1 table tr').filter({ hasText: keyName });
    await expect(row).toBeVisible();
    // Edit pencil and delete button present
    await expect(row.locator('a[href*="encryption_key"]')).toBeVisible();
    await expect(row.locator('button[name="key_delete"]')).toBeVisible();

    await deleteKey(page, keyName);
  });
});
