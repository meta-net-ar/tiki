import { afterEach, describe, expect, test } from "vitest";
import $ from "jquery";
import { attachChangeEventHandler, observeSelectElementMutations } from "../../../helpers/select/applySelect";

const tick = () => new Promise((resolve) => setTimeout(resolve, 0));
const flushMicrotasks = async () => {
    await Promise.resolve();
    await Promise.resolve();
};

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
            await tick();

            expect(givenElementPlusUi.getAttribute(elementPlusAttribute)).toBeNull();

            givenSelect.setAttribute(selectAttribute, attributeValue);
            await tick();

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

        await flushMicrotasks();

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([
            { value: "", label: "", disabled: false },
            { value: "foo", label: selectOption.textContent, disabled: selectOption.disabled },
        ]);
    });

    test("updates the element-plus-ui options when select options are removed", async () => {
        const givenSelect = document.createElement("select");
        const givenElementPlusUi = document.createElement("element-plus-ui");
        const keep = document.createElement("option");
        keep.value = "1";
        const drop = document.createElement("option");
        drop.value = "31";

        givenSelect.append(keep, drop);
        givenSelect.value = "1";
        observeSelectElementMutations(givenSelect, givenElementPlusUi);

        drop.remove();
        await flushMicrotasks();

        expect(JSON.parse(givenElementPlusUi.getAttribute("options"))).toEqual([{ value: "1", label: keep.textContent, disabled: keep.disabled }]);
        expect(givenElementPlusUi.getAttribute("value")).toBe("1");
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

        await flushMicrotasks();

        expect(givenElementPlusUi.getAttribute("group")).toBe("true");
    });

    test("syncs el-select when the native select changes programmatically", async () => {
        const givenSelect = document.createElement("select");
        ["28", "31"].forEach((v) => {
            const option = document.createElement("option");
            option.value = v;
            givenSelect.appendChild(option);
        });
        givenSelect.value = "31";

        const givenElementPlusUi = document.createElement("element-plus-ui");
        attachChangeEventHandler(givenElementPlusUi, givenSelect);

        givenSelect.value = "28";
        $(givenSelect).trigger("change");
        await tick();

        expect(givenElementPlusUi.getAttribute("value")).toBe("28");
    });

    test.each([
        [true, ["foo", "bar"]],
        [false, "foo"],
    ])(
        "attachChangeEventHandler is able to correctly update the select value when the element-plus-ui value changes and the multiple attribute is %s",
        async (isMultiple, value) => {
            const givenSelect = document.createElement("select");
            givenSelect.multiple = isMultiple;
            givenSelect.append(
                ...["foo", "bar"].map((v) => {
                    const option = document.createElement("option");
                    option.value = v;
                    return option;
                })
            );

            const givenElementPlusUi = document.createElement("element-plus-ui");
            attachChangeEventHandler(givenElementPlusUi, givenSelect);

            $(givenElementPlusUi).trigger($.Event("select-change", { detail: [{ value }] }));
            await tick();

            if (isMultiple) {
                expect([...givenSelect.selectedOptions].map((o) => o.value)).toEqual(value);
            } else {
                expect(givenSelect.value).toEqual(value);
            }
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
            $(givenElementPlusUi).trigger($.Event("select-change", { detail: [{ value: updatedValue }] }));
            await tick();

            if (multiple) {
                expect([...givenSelect.selectedOptions].map((o) => o.value)).toEqual(updatedValue);
            } else {
                expect(givenSelect.value).toEqual(updatedValue);
            }
        }
    );
});
