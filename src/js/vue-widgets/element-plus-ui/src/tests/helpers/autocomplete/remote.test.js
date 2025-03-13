import { describe, test, vi, expect } from "vitest";
import { fetchSuggestions } from "../../../helpers/autocomplete/remote";

describe("Autocomplete remote helper functions", () => {
    describe("fetchSuggestions", () => {
        test.each([
            ["foo", ["foo", "bar"], ["foo"]],
            ["foo", ["bar"], []],
            ["f", ["foo", "bar"], ["foo"]],
            ["o", ["foo", "bar"], ["foo"]],
            ["oo", ["foo", "bar"], ["foo"]],
            ["a", ["foo", "bar"], ["bar"]],
            ["a", ["foo", "bar", "baz"], ["bar", "baz"]],
        ])(`given a sourceList, calls the callback with items that match the query`, (query, sourceList, expected) => {
            sourceList = sourceList.map((item) => ({ value: item }));
            expected = expected.map((item) => ({ value: item }));
            const callback = vi.fn();

            fetchSuggestions(query, callback, null, sourceList);

            expect(callback).toHaveBeenCalledWith(expected);
        });

        test.each([
            ["an array of strings", ["foo", "bar"], [{ value: "foo" }, { value: "bar" }]],
            ["an array of objects", [{ value: "foo" }, { value: "bar" }], [{ value: "foo" }, { value: "bar" }]],
        ])(
            "calls the callback with the results fetched from the remote URL in the correct shape, when the result is %s",
            async (_, expectedSuggestions, expectedCallbackArg) => {
                const query = "foo";
                const callback = vi.fn();
                const sourceRemoteUrl = "https://foo/bar";
                const sourceList = [];

                vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn().mockResolvedValueOnce(expectedSuggestions) });

                fetchSuggestions(query, callback, sourceRemoteUrl, sourceList);

                await window.happyDOM.waitUntilComplete();

                expect(callback).toHaveBeenCalledWith(expectedCallbackArg);
            }
        );

        test("logs an error and stop execution when neither sourceRemoteUrl nor sourceList is provided", () => {
            const callback = vi.fn();

            vi.spyOn(console, "error");

            fetchSuggestions("foo", callback);

            expect(console.error).toHaveBeenCalledWith("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions");
            expect(callback).not.toHaveBeenCalled();
        });

        test("the URL is correctly constructed with the query parameter and Fetch is instructed to only accept json response data", async () => {
            const query = "foo";
            const callback = vi.fn();
            const sourceRemoteUrl = "https://foo/bar";
            const sourceList = [];

            vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn() });

            fetchSuggestions(query, callback, sourceRemoteUrl, sourceList);

            expect(window.fetch).toHaveBeenCalledWith(`${sourceRemoteUrl}?q=${query}`, {
                headers: {
                    Accept: "application/json",
                },
            });
        });
    });
});
