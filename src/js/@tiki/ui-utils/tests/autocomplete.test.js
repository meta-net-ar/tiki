import { afterEach, describe, expect, test, vi } from "vitest";
import { applyAutocomplete } from "@vue-widgets/el-autocomplete";
import autocomplete, { getAutocompleteResources } from "../autocomplete";

vi.mock("@vue-widgets/el-autocomplete", () => ({ applyAutocomplete: vi.fn() }));

describe("autocomplete minimum characters", () => {
    const originalJQuery = window.$;
    const originalJqueryTiki = window.jqueryTiki;

    const setup = (jqueryTiki = {}) => {
        window.$ = Object.assign(() => ({ attr: () => "" }), { service: () => "tiki-ajax_services.php?controller=search&action=lookup" });
        window.jqueryTiki = jqueryTiki;
    };

    afterEach(() => {
        window.$ = originalJQuery;
        window.jqueryTiki = originalJqueryTiki;
        vi.clearAllMocks();
    });

    test.each([
        ["search_module", 4],
        ["pagename", 2],
        ["username", 2],
        ["usersandcontacts", 2],
        ["userrealname", 2],
        ["groupname", 1],
        ["trackername", 1],
        ["calendarname", 1],
        ["tag", 1],
    ])("%s requires %i characters", (type, expected) => {
        setup({ autocomplete_search_min_chars: 4 });
        expect(getAutocompleteResources(type, { types: [] }).minChars).toBe(expected);
    });

    test("search_module falls back to 3 characters when the preference is not exposed", () => {
        setup();
        expect(getAutocompleteResources("search_module", {}).minChars).toBe(3);
    });

    test("passes minChars to applyAutocomplete, and lets options.minChars override it", () => {
        setup();
        const input = document.createElement("input");

        autocomplete(input, "username");
        autocomplete(input, "username", { minChars: 5 });

        expect(applyAutocomplete.mock.calls[0][1].minChars).toBe(2);
        expect(applyAutocomplete.mock.calls[1][1].minChars).toBe(5);
    });
});
