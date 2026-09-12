import { afterEach, describe, expect, test, vi } from "vitest";
import {
    startDrag,
    endDrag,
    getDraggedItem,
    isDragging,
    onDragStateChange,
    setRefusal,
    refusalFor,
    isRefusing,
    isCopyRequested,
} from "../../../helpers/select/dragBetweenFields";

describe("Select drag between fields helper functions", () => {
    afterEach(() => {
        endDrag();
    });

    test("startDrag when called, keeps the value of the dragged item", () => {
        startDrag({ value: "foo", label: "Foo" });

        expect(getDraggedItem()).toEqual({ value: "foo" });
        expect(isDragging()).toBe(true);
    });

    test("startDrag when called without a value, keeps nothing", () => {
        startDrag({ label: "Foo" });

        expect(getDraggedItem()).toBe(null);
        expect(isDragging()).toBe(false);
    });

    test("startDrag when called, draws the carrying cursor over the whole page", () => {
        startDrag({ value: "foo" });

        expect(document.body.style.cursor).toBe("grabbing");
    });

    test("endDrag when called, forgets the dragged item and gives the cursor back", () => {
        startDrag({ value: "foo" });

        endDrag();

        expect(getDraggedItem()).toBe(null);
        expect(document.body.style.cursor).toBe("");
    });

    test("endDrag when called, forgets a refusal standing at the time", () => {
        const givenField = {};
        startDrag({ value: "foo" });
        setRefusal(givenField, "duplicate");

        endDrag();

        expect(isRefusing()).toBe(false);
        expect(refusalFor(givenField)).toBe("");
    });

    test("setRefusal when called, speaks for the field having its say only", () => {
        const givenRefusingField = {};
        const givenOtherField = {};

        setRefusal(givenRefusingField, "duplicate");

        expect(refusalFor(givenRefusingField)).toBe("duplicate");
        expect(refusalFor(givenOtherField)).toBe("");
        expect(isRefusing()).toBe(true);
    });

    test("setRefusal when called without a reason, takes the refusal back", () => {
        const givenField = {};
        setRefusal(givenField, "duplicate");

        setRefusal(givenField, "");

        expect(refusalFor(givenField)).toBe("");
        expect(isRefusing()).toBe(false);
    });

    test("setRefusal when called by another field, has the last word", () => {
        const givenRefusingField = {};
        const givenAcceptingField = {};
        setRefusal(givenRefusingField, "duplicate");

        setRefusal(givenAcceptingField, "");

        expect(refusalFor(givenRefusingField)).toBe("");
        expect(isRefusing()).toBe(false);
    });

    test("onDragStateChange when a drag begins, is turned down or ends, tells the listener", () => {
        const givenListener = vi.fn();
        onDragStateChange(givenListener);

        startDrag({ value: "foo" });
        setRefusal({}, "duplicate");
        endDrag();

        expect(givenListener).toHaveBeenCalledTimes(3);
    });

    test("onDragStateChange when the function it returns is called, stops telling the listener", () => {
        const givenListener = vi.fn();
        const stopListening = onDragStateChange(givenListener);

        stopListening();
        startDrag({ value: "foo" });

        expect(givenListener).not.toHaveBeenCalled();
    });

    test("isCopyRequested when the modifier the system copies with is held, is true", () => {
        expect(isCopyRequested({ ctrlKey: true })).toBe(true);
        expect(isCopyRequested({ altKey: true })).toBe(true);
    });

    test("isCopyRequested without that modifier, or without an event at all, is false", () => {
        expect(isCopyRequested({ ctrlKey: false, altKey: false })).toBe(false);
        expect(isCopyRequested({ metaKey: true })).toBe(false);
        expect(isCopyRequested(undefined)).toBe(false);
    });
});
