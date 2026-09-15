// Environment smoke test
//
// Unlike tc00 (pure harness logic, no server needed), this suite hits the live
// Tiki instance to prove a tester's environment is actually usable BEFORE they
// spend time debugging a domain suite. It validates the three prerequisites every
// domain suite silently assumes:
//   1. TIKI_BASE_URL points at a reachable Tiki instance
//   2. the admin credentials log in successfully
//   3. the "User Encryption" feature is enabled (global-setup.ts seeded it)
//
// Run just these checks after setup:
//   npx playwright test suites/smoke/tc01-environment.spec.ts

import { test, expect } from "../../common/test";
import { login, logout, ADMIN } from "../../common/auth";

test.describe("TC-01 — Environment readiness", () => {
    test.describe.configure({ mode: "serial" });

    test("Tiki instance is reachable", async ({ page }) => {
        const response = await page.goto("/tiki-index.php");
        expect(response, "no HTTP response — is TIKI_BASE_URL correct and the server up?").not.toBeNull();
        expect(response!.status(), `unexpected HTTP status from ${page.url()}`).toBeLessThan(400);
    });

    test("admin credentials log in", async ({ page }) => {
        // login() throws if no logout link renders, i.e. the credentials were rejected.
        await login(page, ADMIN.user, ADMIN.pass);
        await logout(page);
    });

    test("User Encryption feature is enabled", async ({ page }) => {
        await login(page, ADMIN.user, ADMIN.pass);
        await page.goto("/tiki-admin.php?page=security");
        await page.waitForLoadState("load");
        // The encryption tab anchor only renders when feature_user_encryption='y'.
        // Its absence means global-setup.ts could not enable the feature.
        await expect(
            page.locator("a[href='#contentadmin1-encryption']"),
            "encryption tab missing — feature_user_encryption is off; check global-setup output"
        ).toHaveCount(1);
        await logout(page);
    });
});
