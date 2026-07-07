import { afterEach, describe, expect, test } from "vitest";
import $ from "jquery";
import { attachChangeEventHandler, observeSelectElementMutations } from "../../../helpers/select/applySelect";

describe("applySelect helper functions", () => {
    beforeEach(() => {
        window.$ = $;
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test.each([
        ["is-invalid", ["is-invalid", "class", "is-invalid", "true"]],
        ["is-invalid", ["is-invalid", "class", "", null]],
        ["max", ["max", "data-max", "2", "2"]],
        ["max", ["max", "data-max", "", ""]],
        ["style", ["style", "style", "color: red;", "color: red;"]],
        ["style ('display: none' ignored)", ["style", "style", "display: none;", ""]],
        ["style ('display: none' ignored but other styles considered)", ["style", "style", "display: none; color: red;", "color: red;"]],
    ])(
        "observeSelectElementMutations is able to update the element-plus-ui %s attribute when relative changes occurs in the select element",
        async (_, [elementPlusAttribute, selectAttribute, attributeValue, expectedValue]) => {
            const givenSelect = document.createElement("select");
            const givenElementPlusUi = document.createElement("element-plus-ui");
            document.body.append(givenSelect, givenElementPlusUi);

            observeSelectElementMutations(givenSelect, givenElementPlusUi);

            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(givenElementPlusUi.getAttribute(elementPlusAttribute)).toBeNull();

            givenSelect.setAttribute(selectAttribute, attributeValue);
            await new Promise((resolve) => setTimeout(resolve, 0));

            expect(givenElementPlusUi.getAttribute(elementPlusAttribute)).toBe(expectedValue);
        }
    );

    test("updates the element-plus-ui options when the select options change", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");

        givenSelect.appendChild(document.createElement("option"));

        observeSelectElementMutations(givenSelect, givenElementPlusUi);

        const selectOption = document.createElement("option");
        selectOption.value = "foo";
        givenSelect.appendChild(selectOption);

        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([
            { value: "", label: "", disabled: false },
            { value: "foo", label: selectOption.textContent, disabled: selectOption.disabled },
        ]);
    });

    test("updates the element-plus-ui options when the native select change event is triggered", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");
        const selectOption = document.createElement("option");
        selectOption.value = "foo";
        givenSelect.appendChild(selectOption);

        attachChangeEventHandler(givenElementPlusUi, givenSelect);

        selectOption.disabled = true;
        $(givenSelect).trigger("change");
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([{ value: "foo", label: selectOption.textContent, disabled: true }]);

        selectOption.disabled = false;
        $(givenSelect).trigger("change");
        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([{ value: "foo", label: selectOption.textContent, disabled: false }]);
    });

    test("updates the element-plus-ui groups when the select grouped options change", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");

        givenSelect.appendChild(document.createElement("option"));

        observeSelectElementMutations(givenSelect, givenElementPlusUi);

        const selectOptGroup = document.createElement("optgroup");
        selectOptGroup.label = "group";
        const selectOption = document.createElement("option");
        selectOption.value = "foo";
        selectOptGroup.appendChild(selectOption);
        givenSelect.appendChild(selectOptGroup);

        await new Promise((resolve) => setTimeout(resolve, 0));

        expect(givenElementPlusUi.getAttribute("group")).toBe("true");
    });

    test.each([
        [true, ["foo", "bar"]],
        [false, "foo"],
    ])(
        "attachChangeEventHandler is able to correctly update the select value when the element-plus-ui value changes and the multiple attribute is %s",
        async (isMultiple, value) => {
            const givenSelect = document.createElement("select");
            givenSelect.multiple = isMultiple;
            const selectOptions = ["foo", "bar"].map((v) => {
                const option = document.createElement("option");
                option.value = v;
                return option;
            });

            givenSelect.append(...selectOptions);
            givenSelect.value = "";

            const givenElementPlusUi = document.createElement("element-plus-ui");

            attachChangeEventHandler(givenElementPlusUi, givenSelect);

            expect(givenSelect.value).toBe("");

            const selectChangeEvent = $.Event("select-change", { detail: [{ value }] });
            $(givenElementPlusUi).trigger(selectChangeEvent);

            await new Promise((resolve) => setTimeout(resolve, 0));

            const actualValue = [];
            for (let i = 0; i < givenSelect.selectedOptions.length; i++) {
                actualValue.push(givenSelect.selectedOptions[i].value);
            }

            expect(isMultiple ? actualValue : givenSelect.value).toEqual(value);
        }
    );

    test.each([
        [true, ["foo", "bar"]],
        [false, "baz"],
    ])(
        "attachChangeEventHandler is able to correctly update the select value when the element-plus-ui value changes with a new option added to the selection when multiple is %s",
        async (multiple, updatedValue) => {
            const givenSelect = document.createElement("select");
            givenSelect.multiple = multiple;

            const givenElementPlusUi = document.createElement("element-plus-ui");

            attachChangeEventHandler(givenElementPlusUi, givenSelect);

            expect(givenSelect.value).toBe("");

            const selectChangeEvent = $.Event("select-change", { detail: [{ value: updatedValue }] });
            $(givenElementPlusUi).trigger(selectChangeEvent);

            await new Promise((resolve) => setTimeout(resolve, 0));

            if (multiple) {
                const actualValue = [];
                for (let i = 0; i < givenSelect.selectedOptions.length; i++) {
                    actualValue.push(givenSelect.selectedOptions[i].value);
                }
                expect(actualValue).toEqual(updatedValue);
            } else {
                expect(givenSelect.value).toEqual(updatedValue);
            }
        }
    );
});
