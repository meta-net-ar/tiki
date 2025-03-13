import { describe, expect, test, vi } from "vitest";
import applyAutocomplete, { TEXT } from "../../utils/applyAutocomplete";

describe("applyAutocomplete", () => {
    test("given an input element, it create an el-autocomplete element with the Autcomplete component", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        const givenRemoteSourceUrl = "https://foo.bar";

        applyAutocomplete(givenInput, givenRemoteSourceUrl);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        expect(expectedAutoCompleteElement.tagName).toBe("EL-AUTOCOMPLETE");
        expect(expectedAutoCompleteElement.getAttribute("remote-source-url")).toBe(givenRemoteSourceUrl);
        expect(expectedAutoCompleteElement.getAttribute("source-list")).toBe("[]");
        expect(givenInput.getAttribute("element-plus-ref")).toBe(expectedAutoCompleteElement.id);
        expect(givenInput.style.display).toBe("none");
    });

    test("should set the placeholder and value-key attributes on the autocomplete element when provided", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        const givenRemoteSourceUrl = "https://foo.bar";
        const givenValueKey = "foo";
        givenInput.setAttribute("placeholder", "bar");

        applyAutocomplete(givenInput, givenRemoteSourceUrl, [], givenValueKey);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        expect(expectedAutoCompleteElement.getAttribute("placeholder")).toBe("bar");
        expect(expectedAutoCompleteElement.getAttribute("value-key")).toBe(givenValueKey);
    });

    test("should generate the autocomplete element given the source list provided instead of the remote source url", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        const givenSourceList = ["foo", "bar"];

        applyAutocomplete(givenInput, null, givenSourceList);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        expect(expectedAutoCompleteElement.getAttribute("source-list")).toBe(JSON.stringify(givenSourceList));
    });

    test("given a select callback is provided, it should get fired when the autocomplete element emits a select event", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        const givenSelectCb = vi.fn();

        applyAutocomplete(givenInput, null, [{ value: "foo" }], null, givenSelectCb);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        const expectedEventDetails = new CustomEvent("select");
        expectedAutoCompleteElement.dispatchEvent(expectedEventDetails);

        expect(givenSelectCb).toHaveBeenCalledWith(expectedEventDetails);
    });

    test("should update the input value and emit a change event when the autocomplete element emits an input event", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        givenInput.value = "foo";

        const dispatchEventSpy = vi.spyOn(givenInput, "dispatchEvent");

        applyAutocomplete(givenInput, null, [{ value: "foo" }]);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        const expectedEventDetails = new CustomEvent("input", { detail: ["bar"] });
        expectedAutoCompleteElement.dispatchEvent(expectedEventDetails);

        expect(givenInput.value).toBe("bar");
        expect(dispatchEventSpy).toHaveBeenCalledWith(expect.objectContaining({ type: "change" }));
    });

    test("should not update the input value when the autocomplete element emits an input event without details", () => {
        const givenInput = document.createElement("input");
        document.body.appendChild(givenInput);
        givenInput.value = "foo";

        applyAutocomplete(givenInput, null, [{ value: "foo" }]);

        const expectedAutoCompleteElement = givenInput.nextElementSibling;
        const expectedEventDetails = new CustomEvent("input");
        expectedAutoCompleteElement.dispatchEvent(expectedEventDetails);

        expect(givenInput.value).toBe("foo");
    });

    test.each([
        ["element", [null], TEXT.ERROR_NO_ELEMENT],
        ["remoteSourceUrl neither the sourceList", [document.createElement("input"), null, []], TEXT.ERROR_NO_SOURCE],
    ])(`should log an error when the %s is not provided`, (_, args, errorMessage) => {
        const consoleErrorSpy = vi.spyOn(console, "error");

        applyAutocomplete(...args);

        expect(consoleErrorSpy).toHaveBeenCalledWith(errorMessage);
    });
});
