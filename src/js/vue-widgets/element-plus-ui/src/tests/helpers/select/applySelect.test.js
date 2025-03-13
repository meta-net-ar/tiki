import { describe, expect, test } from "vitest";
import $ from "jquery";
import { attachChangeEventHandler, observeSelectElementMutations } from "../../../helpers/select/applySelect";

describe("applySelect helper functions", () => {
    beforeEach(() => {
        window.$ = $;
    });

    test.each([
        ["is-invalid", ["class", "is-invalid", "true"]],
        ["is-invalid", ["class", "", null]],
        ["max", ["data-max", "2", "2"]],
        ["max", ["data-max", "", ""]],
    ])(
        "observeSelectElementMutations is able to update the element-plus-ui %s attribute when relative changes occurs in the select element",
        async (attribute, [selectAttribute, attributeValue, expectedValue]) => {
            const givenSelect = document.createElement("select");
            const givenElementPlusUi = document.createElement("element-plus-ui");
            document.body.append(givenSelect, givenElementPlusUi);

            observeSelectElementMutations(givenSelect, givenElementPlusUi);

            await window.happyDOM.waitUntilComplete();

            expect(givenElementPlusUi.getAttribute(attribute)).toBeNull();

            givenSelect.setAttribute(selectAttribute, attributeValue);
            await window.happyDOM.waitUntilComplete();

            expect(givenElementPlusUi.getAttribute(attribute)).toBe(expectedValue);
        }
    );

    test("updates the element-plus-ui options when the select options change", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");

        observeSelectElementMutations(givenSelect, givenElementPlusUi);

        const selectOption = document.createElement("option");
        selectOption.value = "foo";
        givenSelect.appendChild(selectOption);

        await window.happyDOM.waitUntilComplete();

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([
            { value: "foo", label: selectOption.textContent, disabled: selectOption.disabled },
        ]);
    });

    test("updates the element-plus-ui groups when the select grouped options change", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");

        observeSelectElementMutations(givenSelect, givenElementPlusUi);

        const selectOptGroup = document.createElement("optgroup");
        selectOptGroup.label = "group";
        const selectOption = document.createElement("option");
        selectOption.value = "foo";
        selectOptGroup.appendChild(selectOption);
        givenSelect.appendChild(selectOptGroup);

        await window.happyDOM.waitUntilComplete();

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

            await window.happyDOM.waitUntilComplete();

            const actualValue = [];
            for (let i = 0; i < givenSelect.selectedOptions.length; i++) {
                actualValue.push(givenSelect.selectedOptions[i].value);
            }

            expect(isMultiple ? actualValue : givenSelect.value).toEqual(value);
        }
    );

    test("attachChangeEventHandler is able to correctly update the multi select value when the element-plus-ui value changes with a new option added to the select", async () => {
        const givenSelect = document.createElement("select");
        givenSelect.multiple = true;
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

        const selectChangeEvent = $.Event("select-change", { detail: [{ value: ["foo", "bar", "baz"] }] });
        $(givenElementPlusUi).trigger(selectChangeEvent);

        await window.happyDOM.waitUntilComplete();

        const actualValue = [];
        for (let i = 0; i < givenSelect.selectedOptions.length; i++) {
            actualValue.push(givenSelect.selectedOptions[i].value);
        }
        expect(actualValue).toEqual(["foo", "bar", "baz"]);
        expect($(givenSelect).find("option[value='baz']")).toHaveLength(1);
    });
});
