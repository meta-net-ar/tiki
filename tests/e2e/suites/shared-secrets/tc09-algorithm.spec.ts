import { test, expect } from '../../common/test';
import { login, goToEncryptionTab, openCreateKeyTab } from './helpers';

// TC-09 — Algorithm selection
// On v27.x: The algorithm dropdown is NOT shown when $encryption_algos is empty.
// This test documents the v27.x behavior and verifies the Create Key form loads
// without error in the absence of an algorithm selector.
//
// If algorithm support is added or enabled, these tests will need updating.

test.describe('TC-09 — Algorithm selection (v27.x)', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
    await goToEncryptionTab(page);
  });

  test('Algorithm dropdown is absent on v27.x (expected behavior)', async ({ page }) => {
    await openCreateKeyTab(page);
    // On v27.x, $encryption_algos is empty so no algorithm <select> is rendered
    const algoSelect = page.locator('#contentencryption-2 select[name="algo"]');
    // The selector should not be present
    await expect(algoSelect).toHaveCount(0);
  });

  test('Create Key form loads correctly without algorithm field', async ({ page }) => {
    await openCreateKeyTab(page);
    // Core fields are still present
    await expect(page.locator('#contentencryption-2 input[name="name"]')).toBeVisible();
    await expect(page.locator('#contentencryption-2 textarea[name="description"]')).toBeVisible();
    // User selector present (underlying <select>, enhanced by Select2/Element Plus)
    await expect(page.locator('#user_selector_1')).toBeAttached();
  });

  test('No PHP errors related to algorithm processing on the page', async ({ page }) => {
    await openCreateKeyTab(page);
    const body = await page.locator('body').textContent();
    expect(body).not.toContain('Fatal error');
    expect(body).not.toContain('Undefined index: algo');
    expect(body).not.toContain('Uncaught Error');
  });

  test.skip('Algorithm dropdown is selectable when $encryption_algos is populated', () => {
    // Skip: Not applicable on v27.x — $encryption_algos is empty.
    // This test would apply if Tiki is configured with explicit algo list.
    // To enable: configure $encryption_algos in tiki-setup.php or local config.
  });
});
