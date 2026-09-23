import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, logout, goToEncryptionTab, openEditKey, applyKeyForm, getShares, app } from "./helpers";

// TC-11 — Key regeneration through the sss-admin edit form.
//
// The form posts `regenerate=1` with no `old_share`, so the controller rebuilds the
// key from the acting user's stored share. The fixture therefore makes admin a
// holder. Regeneration from a supplied old_share, which no screen reaches today,
// is covered in Encryption/ControllerTest.

const fx = useSuiteFixtures(test);

let adminShare: string;
let testUserShare: string;

test.describe("TC-11 — Key regeneration", () => {
    test("Regenerating re-issues a different share to every holder", async ({ page }) => {
        adminShare = fx.data.adminShare;
        testUserShare = fx.data.testUserShare;
        expect(adminShare).toBeTruthy();
        expect(testUserShare).toBeTruthy();

        await login(page, "admin", "admin1234");
        await goToEncryptionTab(page);
        await openEditKey(page, "TC11-EncKey");

        const regenerate = app(page).locator(".row", { hasText: "Regenerate shares" }).locator("input[type='checkbox']");
        await expect(regenerate).toBeVisible();
        await regenerate.check();

        await applyKeyForm(page);
        await expect(app(page).getByText("Key “TC11-EncKey” saved.")).toBeVisible();

        const shares = await getShares(page);
        expect(shares).toHaveLength(2);
        expect(shares).not.toContain(adminShare);
        expect(shares).not.toContain(testUserShare);

        await logout(page);
    });
});
