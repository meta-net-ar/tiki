import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, goToEncryptionTab, openCreateKey, fillKeyForm, applyKeyForm, getShares, backToDashboard, keyRow, deleteKey } from "./helpers";

const KEY_NAME = `TC03-EncKey-${Date.now()}`;

// The paired teardown removes any key a failed journey left behind.
useSuiteFixtures(test);

test.describe("TC-03 — Create a key shared with users", () => {
    test("Two authorized users produce two shares and both appear on the key's row", async ({ page }) => {
        await login(page);
        await goToEncryptionTab(page);
        await openCreateKey(page);
        await fillKeyForm(page, KEY_NAME, "", ["Test1", "Test2"]);
        await applyKeyForm(page);

        const shares = await getShares(page);
        expect(shares).toHaveLength(2);
        expect(shares[0]).not.toEqual(shares[1]);

        await backToDashboard(page);
        const row = keyRow(page, KEY_NAME);
        await expect(row).toContainText("Test1");
        await expect(row).toContainText("Test2");
        await expect(row.locator(".badge").first()).toHaveText("2");

        await deleteKey(page, KEY_NAME);
    });
});
