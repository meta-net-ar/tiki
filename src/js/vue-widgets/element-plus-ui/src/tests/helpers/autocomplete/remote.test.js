import { describe, test, vi, expect, afterEach, beforeEach } from "vitest";
import { fetchSuggestions } from "../../../helpers/autocomplete/remote";

describe("Autocomplete remote helper functions", () => {
    describe("fetchSuggestions", () => {
        beforeEach(() => {
            window.jqueryTiki = {};
        });

        afterEach(() => {
            vi.clearAllMocks();
        });

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

                await new Promise((resolve) => setTimeout(resolve, 0));

                expect(callback).toHaveBeenCalledWith(expectedCallbackArg);
            }
        );

        test("passes the abort signal to fetch and ignores aborted requests", async () => {
            const callback = vi.fn();
            const controller = new AbortController();
            const abortError = new Error("aborted");
            abortError.name = "AbortError";
            vi.spyOn(window, "fetch").mockRejectedValueOnce(abortError);

            fetchSuggestions("foo", callback, "https://foo/bar", [], "q", controller.signal);
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(window.fetch).toHaveBeenCalledWith(expect.any(String), expect.objectContaining({ signal: controller.signal }));
            expect(callback).not.toHaveBeenCalled();
        });

        test.each([
            ["the request fails", () => vi.spyOn(window, "fetch").mockRejectedValueOnce(new Error("network"))],
            [
                "the server answers with an error status",
                () => vi.spyOn(window, "fetch").mockResolvedValueOnce({ ok: false, status: 500, json: vi.fn() }),
            ],
        ])("calls the callback with an empty list when %s", async (_, mockFetch) => {
            const callback = vi.fn();
            vi.spyOn(console, "error").mockImplementation(() => {});
            mockFetch();

            fetchSuggestions("foo", callback, "https://foo/bar", []);
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(callback).toHaveBeenCalledWith([]);
        });

        test.each([
            ["ab", "ab"],
            ["abc", "*abc*"],
        ])("with wildcard search enabled, %s is sent as %s", async (query, expected) => {
            window.jqueryTiki = { tiki_object_selector_wildcardsearch: true };
            vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn().mockResolvedValueOnce([]) });

            fetchSuggestions(query, vi.fn(), "https://foo/bar", []);

            const sentUrl = new URL(window.fetch.mock.calls[0][0]);
            expect(sentUrl.searchParams.get("q")).toBe(expected);
        });

        test("logs an error and stop execution when neither sourceRemoteUrl nor sourceList is provided", () => {
            const callback = vi.fn();

            vi.spyOn(console, "error");

            fetchSuggestions("foo", callback);

            expect(console.error).toHaveBeenCalledWith("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions"); // eslint-disable-line no-console
            expect(callback).not.toHaveBeenCalled();
        });

        test.each([
            ["without a custom remote query key", undefined, "q"],
            ["with a custom remote query key", "foo", "foo"],
        ])(
            "(%s), the URL is correctly constructed with the query parameter and Fetch is instructed to only accept json response data",
            async (_, customRemoteQueryKey, expectedQueryKey) => {
                const query = "foo";
                const callback = vi.fn();
                const sourceRemoteUrl = "https://foo/bar";
                const sourceList = [];

                vi.spyOn(window, "fetch").mockResolvedValueOnce({ json: vi.fn() });

                fetchSuggestions(query, callback, sourceRemoteUrl, sourceList, customRemoteQueryKey);

                await window.happyDOM.waitUntilComplete();

                const expectedUrl = new URL(sourceRemoteUrl);
                expectedUrl.searchParams.append(expectedQueryKey, query);

                expect(window.fetch).toHaveBeenCalledWith(expectedUrl.href, {
                    headers: {
                        Accept: "application/json",
                    },
                });
            }
        );
    });
});
