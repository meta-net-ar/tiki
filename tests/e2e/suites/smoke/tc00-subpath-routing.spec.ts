// Subpath routing smoke test
//
// Verifies that TIKI_BASE_URL subpaths are correctly prepended to every
// leading-slash navigation by the test suite fixtures.
//
// The expected URL is always derived from TIKI_BASE_URL itself (not BASE_PATH),
// so the assertion is independent of the fixture under test.
//
// Run with a fake subpath to prove the fix works:
//   TIKI_BASE_URL=https://tiki-9887.ddev.site/tiki npx playwright test suites/smoke/
//   → 3 passed
//
// Reproduce the pre-fix bug (fixture disabled via explicit empty BASE_PATH):
//   TIKI_BASE_URL=https://tiki-9887.ddev.site/tiki TIKI_BASE_PATH="" npx playwright test suites/smoke/ --reporter=line
//   → test 1 FAILS: received ".../tiki-login.php", expected ".../tiki/tiki-login.php"

import { test, expect } from '../../common/test';

const rawBaseURL = process.env.TIKI_BASE_URL ?? 'http://localhost';
const parsedBaseURL = new URL(rawBaseURL);
const origin = parsedBaseURL.origin;
const subpath = parsedBaseURL.pathname.replace(/\/$/, ''); // e.g. "/tiki" or ""

test.describe('TC-00 — Subpath routing', () => {
    test.describe.configure({ mode: 'serial' });

    test('page.goto prepends subpath to leading-slash URLs', async ({ page }) => {
        const intercepted: string[] = [];
        await page.route('**/*', (route) => {
            intercepted.push(route.request().url());
            route.fulfill({ status: 200, body: '' });
        });

        await page.goto('/tiki-login.php').catch(() => null);

        // Always expect origin + full subpath from TIKI_BASE_URL + the navigated path
        expect(intercepted[0]).toBe(`${origin}${subpath}/tiki-login.php`);
    });

    test('page.goto does NOT prefix absolute URLs', async ({ page }) => {
        const intercepted: string[] = [];
        await page.route('**/*', (route) => {
            intercepted.push(route.request().url());
            route.fulfill({ status: 200, body: '' });
        });

        await page.goto('https://example.com/page').catch(() => null);

        expect(intercepted[0]).toBe('https://example.com/page');
    });

    test('page.goto does NOT prefix relative paths (no leading slash)', async ({ page }) => {
        const intercepted: string[] = [];
        await page.route('**/*', (route) => {
            intercepted.push(route.request().url());
            route.fulfill({ status: 200, body: '' });
        });

        // No leading slash → resolves relative to origin, no BASE_PATH prefix
        await page.goto('tiki-login.php').catch(() => null);

        // Must not contain the subpath + /tiki-login.php combination
        if (subpath) {
            expect(intercepted[0]).not.toContain(`${subpath}/tiki-login.php`);
        }
        expect(intercepted[0]).toContain(origin);
    });
});
