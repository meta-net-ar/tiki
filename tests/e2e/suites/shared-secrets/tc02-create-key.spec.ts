import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import {
    login,
    goToEncryptionTab,
    openCreateKey,
    fillKeyForm,
    applyKeyForm,
    getShares,
    backToDashboard,
    assertKeyInTable,
    deleteKey,
    app,
} from "./helpers";

const KEY_NAME = `TC02-EncKey-${Date.now()}`;

// The paired teardown removes any key a failed journey left behind.
useSuiteFixtures(test);

test.describe("TC-02 — Create a key", () => {
    test("A created key shows its shares once, then appears in the table without them", async ({ page }) => {
        await login(page);
        await goToEncryptionTab(page);
        await openCreateKey(page);

        // Apply stays disabled until the form carries a name. The controller-side
        // duplicate and no-shares rules live in Encryption/ControllerTest.
        await expect(app(page).getByRole("button", { name: "Apply" })).toBeDisabled();

        await fillKeyForm(page, KEY_NAME, "Created by the e2e suite", ["Test1"]);
        await applyKeyForm(page);

        await expect(app(page).getByText(`Key “${KEY_NAME}” saved.`)).toBeVisible();
        await expect(app(page).getByText("Save your share now.")).toBeVisible();

        const shares = await getShares(page);
        expect(shares).toHaveLength(1);
        expect(shares[0].length).toBeGreaterThan(0);

        await backToDashboard(page);
        await assertKeyInTable(page, KEY_NAME);

        // Shares are shown once: coming back to the dashboard and reloading must
        // not surface them again anywhere on the page.
        await page.reload();
        await goToEncryptionTab(page);
        await expect(page.getByText("Save your share now.")).toHaveCount(0);
        await expect(page.locator("body")).not.toContainText(shares[0]);

        await deleteKey(page, KEY_NAME);
    });
});
