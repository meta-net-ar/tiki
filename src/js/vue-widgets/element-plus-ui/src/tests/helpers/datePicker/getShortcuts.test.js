import { describe, expect, test } from "vitest";
import getShortcuts from "../../../helpers/datePicker/getShortcuts";
import moment from "moment-timezone";

describe("DatePicker getShortcuts helper", () => {
    test("returns the correct shortcuts when not requested in range mode", () => {
        const shortcuts = getShortcuts();

        expect(shortcuts).toEqual([
            { text: "Today", value: expect.any(Function) },
            { text: "Last week", value: expect.any(Function) },
            { text: "Last month", value: expect.any(Function) },
        ]);

        // ignore milliseconds in comparison
        expect(shortcuts[0].value()).toBeCloseTo(moment().toDate(), -10);
        expect(shortcuts[1].value()).toBeCloseTo(moment().subtract(1, "week").toDate(), -10);
        expect(shortcuts[2].value()).toBeCloseTo(moment().subtract(1, "month").toDate(), -10);
    });

    test("returns the correct shortcuts when requested in range mode", () => {
        const shortcuts = getShortcuts(true);

        expect(shortcuts).toEqual([
            { text: "Today", value: expect.any(Function) },
            { text: "Last week", value: expect.any(Function) },
            { text: "Last month", value: expect.any(Function) },
        ]);

        // ignore milliseconds in comparison
        expect(shortcuts[0].value()).toEqual([moment().startOf("day").toDate(), moment().endOf("day").toDate()]);
        expect(shortcuts[1].value()).toEqual([
            moment().subtract(1, "week").startOf("week").toDate(),
            moment().subtract(1, "week").endOf("week").toDate(),
        ]);
        expect(shortcuts[2].value()).toEqual([
            moment().subtract(1, "month").startOf("month").toDate(),
            moment().subtract(1, "month").endOf("month").toDate(),
        ]);
    });
});
