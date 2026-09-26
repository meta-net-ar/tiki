import { describe, expect, test } from "vitest";
import { getSearchDelay } from "../../../helpers/autocomplete/delay";

describe("getSearchDelay", () => {
    test.each([
        [0, 1, null],
        [1, 1, 1500],
        [2, 1, 1500],
        [3, 1, 1000],
        [4, 1, 1000],
        [5, 1, 750],
        [6, 1, 500],
        [30, 1, 500],
        [2, 3, null],
        [3, 3, 1000],
        [1, 0, 1500],
    ])("length %i with minChars %i gives %s", (length, minChars, expected) => {
        expect(getSearchDelay(length, minChars)).toBe(expected);
    });
});
