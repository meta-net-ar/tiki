import { Page, expect } from "@playwright/test";

// Ensures login() always receives both fields — catches missing-pass mistakes at edit time.
export type User = {
    user: string;
    pass: string;
};

export const ADMIN: User = {
    user: process.env.TIKI_ADMIN_USER ?? "admin",
    pass: process.env.TIKI_ADMIN_PASS ?? "admin1234",
};

export const TEST_USER: User = {
    user: process.env.TIKI_TEST_USER ?? "Test1",
    pass: process.env.TIKI_TEST_PASS ?? "test1pass",
};

export async function login(page: Page, user = ADMIN.user, pass = ADMIN.pass) {
    await page.goto("/tiki-login.php");
    await page.locator('input[name="user"]').fill(user);
    await page.locator('input[name="pass"]').fill(pass);
    await page
        .locator(
            'button[name="login"], input[name="login"], button:has-text("Log in")'
        )
        .first()
        .click();
    // A successful login renders a logout link; a rejected login re-renders the
    // login form (tiki-login_scr.php) with no logout link. Assert the session is
    // real so downstream "logged in as X" tests cannot silently run as anonymous.
    await expect(page.locator('a[href*="tiki-logout"]')).not.toHaveCount(0, {
        timeout: 15000,
    });
}

export async function logout(page: Page) {
    await page.goto("/tiki-logout.php");
}
