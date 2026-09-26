import { test, expect } from "../../common/test";
import { login, logout, goToEncryptionTab, assertKeyNotInTable, TEST_USER } from "./helpers";

// TC-08 — Security: unauthorized access.
//
// One journey over the HTTP boundary. The per-action permission rules
// (save_key / get_keys / delete_key / get_algos / get_ticket denied to non-admins)
// are asserted in Encryption/ControllerTest, which runs on every merge request.
//
// The anonymous save_key case used to be test.fixme: action_save_key carried no
// permission check and an anonymous POST persisted a key. !10189 added
// Services_Exception_Denied::checkGlobal('tiki_p_admin') and a CSRF ticket gate.
// This journey posts an invalid ticket, so either gate alone keeps it green: it
// proves an anonymous POST persists nothing, not that the permission check exists.
// That check is asserted on its own in Encryption/ControllerTest.

test.describe("TC-08 — Security: unauthorized access", () => {
    test("Anonymous and non-admin users reach neither the admin page nor the key endpoints", async ({ page }) => {
        const hackName = `E2E-AnonHack-${Date.now()}`;

        await page.goto("/tiki-logout.php");
        await page.waitForLoadState("load");

        // Anonymous POST to save_key must not persist anything.
        const response = await page.request.post("/tiki-ajax_services.php?controller=encryption&action=save_key", {
            form: { name: hackName, shares: "1", ticket: "invalid" },
        });
        const body = await response.text();
        expect(body.includes('"keyId"') && body.includes('"status":"OK"')).toBe(false);

        // Anonymous access to the admin page is redirected to login.
        await page.goto("/tiki-admin.php?page=security");
        await page.waitForLoadState("load");
        expect(page.url()).toMatch(/tiki-login|tiki-index/);

        // A registered non-admin gets no Encryption tab.
        await login(page, TEST_USER.user, TEST_USER.pass);
        await page.goto("/tiki-admin.php?page=security");
        await page.waitForLoadState("load");
        await expect(page.locator("a[href='#contentadmin1-encryption']")).toHaveCount(0);
        await logout(page);

        // And the anonymous POST really did not create the key.
        await login(page);
        await goToEncryptionTab(page);
        await assertKeyNotInTable(page, hackName);

        const pageText = await page.locator("body").textContent();
        expect(pageText).not.toContain("Fatal error");
        expect(pageText).not.toContain("Uncaught Error");

        await logout(page);
    });
});
