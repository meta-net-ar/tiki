import { test, expect } from '../../common/test';
import {
    login,
    goToEncryptionTab,
    createKey,
    deleteKey,
    openAvailableKeysTab,
    getKeyId,
    goToEditKeyPage,
} from './helpers';

// TC-05 — Edit key metadata (name + description, without share regeneration)
// Regeneration is covered by TC-11. This suite covers the non-regenerate edit path.

test.describe('TC-05 — Edit key metadata', () => {
    test.describe.configure({ mode: 'serial' });

    let keyId: number;
    let currentKeyName: string;

    test.beforeAll(async ({ browser }) => {
        const page = await browser.newPage();
        await login(page);
        await goToEncryptionTab(page);
        currentKeyName = `TC05-Base-${Date.now()}`;
        await createKey(page, currentKeyName, 'TC-05 initial description', ['Test1']);
        keyId = await getKeyId(page, currentKeyName);
        expect(keyId).toBeGreaterThan(0);
        await page.close();
    });

    test.afterAll(async ({ browser }) => {
        const page = await browser.newPage();
        await login(page);
        await goToEncryptionTab(page);
        try {
            await deleteKey(page, currentKeyName);
        } catch (_) {
            // Key may have been renamed; best-effort cleanup
        }
        await page.close();
    });

    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    // ── Test 1: Edit page loads with prefilled name + description ─────────────
    test('Edit page loads with prefilled name and description', async ({ page }) => {
        await goToEditKeyPage(page, keyId);
        await expect(
            page.locator('#contentencryption-2 input[name="name"]')
        ).toHaveValue(currentKeyName);
        await expect(
            page.locator('#contentencryption-2 textarea[name="description"]')
        ).toHaveValue('TC-05 initial description');
    });

    // ── Test 2: Updating description saves correctly ───────────────────────────
    test('Admin can update the key description', async ({ page }) => {
        await goToEditKeyPage(page, keyId);
        await page
            .locator('#contentencryption-2 textarea[name="description"]')
            .fill('TC-05 updated description');
        await page.locator('input[type="submit"][value="Apply"]').last().click();
        await page.waitForLoadState('load');

        // Navigate back and confirm description persisted
        await goToEditKeyPage(page, keyId);
        await expect(
            page.locator('#contentencryption-2 textarea[name="description"]')
        ).toHaveValue('TC-05 updated description');
    });

    // ── Test 3: Renaming the key ───────────────────────────────────────────────
    test('Admin can rename the key and new name appears in Available Keys table', async ({ page }) => {
        const newName = `TC05-Renamed-${Date.now()}`;
        await goToEditKeyPage(page, keyId);
        await page
            .locator('#contentencryption-2 input[name="name"]')
            .fill(newName);
        await page.locator('input[type="submit"][value="Apply"]').last().click();
        await page.waitForLoadState('load');

        await openAvailableKeysTab(page);
        await expect(
            page.locator('#contentencryption-1 table')
        ).toContainText(newName);
        await expect(
            page.locator('#contentencryption-1 table')
        ).not.toContainText(currentKeyName);

        currentKeyName = newName;
    });

    // ── Test 4: Duplicate name is rejected ────────────────────────────────────
    test('Duplicate key name is rejected when editing', async ({ page }) => {
        // Create a second key to attempt a name collision
        await goToEncryptionTab(page);
        const otherName = `TC05-Other-${Date.now()}`;
        await createKey(page, otherName, '', ['Test1']);

        // Try to rename the TC05 key to the same name as the other key
        await goToEditKeyPage(page, keyId);
        await page
            .locator('#contentencryption-2 input[name="name"]')
            .fill(otherName);
        await page.locator('input[type="submit"][value="Apply"]').last().click();
        await page.waitForLoadState('load');

        // The current name must still be in the table (rename rejected)
        await openAvailableKeysTab(page);
        await expect(
            page.locator('#contentencryption-1 table')
        ).toContainText(currentKeyName);

        // Cleanup the collision key
        await deleteKey(page, otherName);
    });

    // ── Test 5: Anti — edit without regenerate keeps existing shares ──────────
    test('Anti: Editing without regenerate does not alter share assignment', async ({ page }) => {
        await goToEditKeyPage(page, keyId);
        // Submit without touching regenerate checkbox or share fields
        await page.locator('input[type="submit"][value="Apply"]').last().click();
        await page.waitForLoadState('load');

        // Key should still be in the table with Test1 as user
        await openAvailableKeysTab(page);
        const row = page
            .locator('#contentencryption-1 table tr')
            .filter({ hasText: currentKeyName });
        await expect(row).toContainText('Test1');
        // Shares column contains "1" (one share for Test1)
        await expect(row).toContainText('1');
    });
});
