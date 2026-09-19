import { test, expect } from "../../common/test";
import { useSuiteFixtures } from "../../common/fixtures";
import {
    login,
    goToEncryptionTab,
    createKey,
    backToDashboard,
    openEditKey,
    fillKeyForm,
    applyKeyForm,
    assertKeyInTable,
    assertKeyNotInTable,
    deleteKey,
    app,
} from "./helpers";

// The two names must not share a prefix: the dashboard row is matched by
// substring, so `X` and `X-renamed` would both match the same row.
const STAMP = Date.now();
const KEY_NAME = `TC05-Before-${STAMP}`;
const RENAMED = `TC05-After-${STAMP}`;

// The paired teardown removes any key a failed journey left behind.
useSuiteFixtures(test);

test.describe("TC-05 — Edit key metadata", () => {
    test("Renaming a key through the edit form updates the dashboard row", async ({ page }) => {
        await login(page);
        await goToEncryptionTab(page);
        await createKey(page, KEY_NAME, "Original description", ["Test1"]);
        await backToDashboard(page);

        await openEditKey(page, KEY_NAME);
        // The edit form arrives prefilled from get_key.
        await expect(app(page).locator(".row", { hasText: "Key name or domain" }).locator("input")).toHaveValue(KEY_NAME);

        await fillKeyForm(page, RENAMED);
        await applyKeyForm(page);
        await expect(app(page).getByText(`Key “${RENAMED}” saved.`)).toBeVisible();

        await backToDashboard(page);
        await assertKeyInTable(page, RENAMED);
        await assertKeyNotInTable(page, KEY_NAME);

        await deleteKey(page, RENAMED);
    });
});
