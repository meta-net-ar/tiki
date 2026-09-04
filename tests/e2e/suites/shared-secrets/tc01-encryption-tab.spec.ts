import { test, expect } from '../../common/test';
import { login, goToEncryptionTab } from './helpers';

test.describe('TC-01 — Encryption tab access and display', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('Encryption tab is visible and loads without error', async ({ page }) => {
    await goToEncryptionTab(page);
    await expect(page.locator('.remarksbox-note, .alert-info').filter({ hasText: 'About encryption' })).toBeVisible();
    await expect(page.locator('#contentencryption-1')).toBeVisible();
  });

  test('Available keys tab shows the keys table', async ({ page }) => {
    await goToEncryptionTab(page);
    const table = page.locator('#contentencryption-1 table');
    await expect(table).toBeVisible();
    await expect(table.locator('th').filter({ hasText: 'Name' })).toBeVisible();
    await expect(table.locator('th').filter({ hasText: 'Number of shares' })).toBeVisible();
    await expect(table.locator('th').filter({ hasText: 'Users' })).toBeVisible();
  });

  test('Create Key tab is accessible', async ({ page }) => {
    await goToEncryptionTab(page);
    await page.locator("a[href='#contentencryption-2']").click();
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('textarea[name="description"]')).toBeVisible();
    // User selector: present as underlying <select#user_selector_1> (enhanced by Select2 or Element Plus depending on version)
    await expect(page.locator('#user_selector_1')).toBeAttached();
  });

  test('No PHP errors visible on the page', async ({ page }) => {
    await goToEncryptionTab(page);
    const body = await page.locator('body').textContent();
    expect(body).not.toContain('Fatal error');
    expect(body).not.toContain('Class not found');
    expect(body).not.toContain('Uncaught Error');
  });
});
