import { test, expect } from '../../common/test';
import { login, goToEncryptionTab, createKey, getShares, assertKeyInTable, deleteKey, openCreateKeyTab } from './helpers';

// TC-03 — Create a key shared with Tiki users (User Encryption enabled)
// On v27.x: User Encryption IS enabled. Test users Test1 and Test2 are available.
// Shares are stored in user preferences and displayed once after creation.

test.describe('TC-03 — Create a key with users (User Encryption enabled)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await goToEncryptionTab(page);
  });

  test('Users selector is visible in Create Key form', async ({ page }) => {
    await openCreateKeyTab(page);
    // With User Encryption enabled, the users multi-select is shown.
    // Field is name="users" (not "users[]"); Select2/Element Plus enhances it.
    await expect(page.locator('#user_selector_1')).toBeAttached();
  });

  test('Creating a key with Test1 generates one share', async ({ page }) => {
    const keyName = `TC03-Single-${Date.now()}`;
    await createKey(page, keyName, 'TC-03 single user test', ['Test1']);

    // One share should be shown after creation
    const shares = await getShares(page);
    expect(shares.length).toBeGreaterThan(0);
    // Shares are non-empty strings
    shares.forEach(s => expect(s.trim()).not.toBe(''));

    // Cleanup
    await deleteKey(page, keyName);
  });

  test('Creating a key with Test1 and Test2 generates two shares', async ({ page }) => {
    const keyName = `TC03-Multi-${Date.now()}`;
    await createKey(page, keyName, 'TC-03 multi-user test', ['Test1', 'Test2']);

    const shares = await getShares(page);
    // Two users = two shares
    expect(shares.length).toBe(2);

    // Cleanup
    await deleteKey(page, keyName);
  });

  test('Key created with users appears in Available keys table with correct user', async ({ page }) => {
    const keyName = `TC03-Table-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);
    await assertKeyInTable(page, keyName);

    // Table row should mention Test1
    const row = page.locator('#contentencryption-1 table tr').filter({ hasText: keyName });
    await expect(row).toContainText('Test1');

    // Cleanup
    await deleteKey(page, keyName);
  });

  test('Shares are not shown after page reload', async ({ page }) => {
    const keyName = `TC03-Reload-${Date.now()}`;
    await createKey(page, keyName, '', ['Test1']);

    const sharesBeforeReload = await getShares(page);
    expect(sharesBeforeReload.length).toBeGreaterThan(0);

    // Reload — shares must be gone (they are one-time display only)
    await page.reload();
    await goToEncryptionTab(page);
    const sharesAfterReload = await getShares(page);
    expect(sharesAfterReload.length).toBe(0);

    // Cleanup
    await deleteKey(page, keyName);
  });
});
