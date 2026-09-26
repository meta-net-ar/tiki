import { test } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import { login, goToEncryptionTab, createKey, backToDashboard, deleteKey, assertKeyInTable, assertKeyNotInTable } from "./helpers";

const KEY_NAME = `TC07-EncKey-${Date.now()}`;

// The paired teardown removes any key a failed journey left behind.
useSuiteFixtures(test);

test.describe("TC-07 — Key deletion", () => {
    test("Dismissing the confirmation keeps the key; accepting it removes the row", async ({ page }) => {
        await login(page);
        await goToEncryptionTab(page);
        await createKey(page, KEY_NAME, "", ["Test1"]);
        await backToDashboard(page);

        await deleteKey(page, KEY_NAME, false);
        await assertKeyInTable(page, KEY_NAME);

        await deleteKey(page, KEY_NAME, true);
        await assertKeyNotInTable(page, KEY_NAME);
    });
});
