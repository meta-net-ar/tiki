import { test, expect } from '../../common/test';
import { login, logout, goToEncryptionTab, openAvailableKeysTab, TEST_USER } from './helpers';

// TC-08 — Security: unauthorized access attempts
// Tests that the encryption admin endpoints are properly protected.
//
// v27.x notes:
// - Tiki AJAX endpoints always return HTTP 200; errors are in the JSON body.
// - The admin page (tiki-admin.php) requires login but the redirect URL pattern
//   may vary by Tiki version and session cookie behavior.

test.describe('TC-08 — Security: unauthorized access', () => {
  // FIXME: CONFIRMED Tiki security gap. action_save_key (lib/core/Services/Encryption/Controller.php:27)
  // has no permission check and no CSRF ticket enforcement, so an anonymous POST creates a persisted
  // encryption key. The endpoint returns HTTP 200 with an EMPTY body, which is why the original
  // body-only assertion passed while keys were silently created (and accumulated) in the DB.
  // Marked test.fixme until the endpoint rejects unauthorized key creation; the assertions below are
  // the correct spec and will pass once the gap is fixed.
  test.fixme('Anonymous user POST to save_key must not create a key', async ({ page }) => {
    const hackName = `E2E-AnonHack-${Date.now()}`;
    await page.goto('/tiki-logout.php');
    await page.waitForLoadState('networkidle');

    const response = await page.request.post(
      '/tiki-ajax_services.php?controller=encryption&action=save_key',
      { form: { name: hackName, shares: '1', ticket: 'invalid' } }
    );
    // The endpoint must reject the request without a success payload...
    const body = await response.text();
    expect(body.includes('"keyId"') && body.includes('"status":"OK"')).toBe(false);

    // ...and, critically, must not have persisted a key server-side.
    await login(page);
    await goToEncryptionTab(page);
    await openAvailableKeysTab(page);
    await expect(page.locator('#contentencryption-1')).not.toContainText(hackName);
    await logout(page);
  });

  test('Anonymous user accessing admin page is redirected to login', async ({ page }) => {
    // Ensure logged out
    await page.goto('/tiki-logout.php');
    await page.waitForURL(/tiki-login\.php|tiki-index\.php/, { timeout: 10000 }).catch(() => {});

    // Navigate to admin security page as anonymous
    await page.goto('/tiki-admin.php?page=security');
    await page.waitForLoadState('networkidle');
    // Anonymous users are redirected to the login screen, and the encryption admin
    // panel (#contentencryption-1, which an admin sees) must not render.
    expect(page.url()).toMatch(/tiki-login(_scr)?\.php/);
    await expect(page.locator('#contentencryption-1')).toHaveCount(0);
  });

  test('Regular non-admin user cannot access Encryption admin tab', async ({ page }) => {
    await login(page, TEST_USER.user, TEST_USER.pass);
    await page.goto('/tiki-admin.php?page=security');
    await page.waitForLoadState('networkidle');

    // A registered-only user must never see the encryption admin panel that an
    // admin sees at #contentencryption-1.
    await expect(page.locator('#contentencryption-1')).toHaveCount(0);

    await logout(page);
  });

  test('No PHP fatal errors on the encryption admin page (admin)', async ({ page }) => {
    await login(page);
    await goToEncryptionTab(page);
    const body = await page.locator('body').textContent();
    expect(body).not.toContain('Fatal error');
    expect(body).not.toContain('Uncaught Error');
    expect(body).not.toContain('Class not found');
  });

  // FIXME: CONFIRMED — same gap as above. An authenticated admin POST with a deliberately bogus CSRF
  // ticket still creates a key, so the ticket is not enforced on this endpoint. Un-fixme once
  // action_save_key validates the ticket.
  test.fixme('CSRF protection: save_key with bogus ticket must not create a key', async ({ page }) => {
    await login(page);
    const csrfName = `E2E-CsrfTest-${Date.now()}`;
    const response = await page.request.post(
      '/tiki-ajax_services.php?controller=encryption&action=save_key',
      { form: { name: csrfName, shares: '1', ticket: 'bogus_ticket_value' } }
    );
    const body = await response.text();
    expect(body.includes('"keyId"') && body.includes('"status":"OK"')).toBe(false);

    // The bogus-ticket request must not have persisted a key.
    await goToEncryptionTab(page);
    await openAvailableKeysTab(page);
    await expect(page.locator('#contentencryption-1')).not.toContainText(csrfName);
  });
});
