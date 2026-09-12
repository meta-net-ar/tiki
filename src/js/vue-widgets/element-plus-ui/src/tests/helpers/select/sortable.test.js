import { describe, expect, test } from "vitest";
import { sortOptions } from "../../../helpers/select/sortable";

describe("Select sortable helper functions", () => {
    test("sortOptions when called, re-orders the options in the select element", () => {
        const givenWrapperElement = document.createElement("div");
        givenWrapperElement.getRootNode = () => ({ host: { id: "element-plus-ui-id" } });
        document.body.append(givenWrapperElement);

        const givenSelect = document.createElement("select");
        givenSelect.setAttribute("element-plus-ref", "element-plus-ui-id");
        const givenOptions = Array.from({ length: 3 }, (_, i) => {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = `Option ${i}`;
            return option;
        });
        givenSelect.append(...givenOptions);
        givenWrapperElement.append(givenSelect);

        const expectedReorderedOptions = givenOptions.slice().reverse();

        const givenOrderedTags = expectedReorderedOptions.map((option) => {
            const tag = document.createElement("div");
            tag.classList.add("el-select__tags-text");
            tag.textContent = option.textContent;
            return tag;
        });

        givenWrapperElement.append(...givenOrderedTags);

        sortOptions(
            givenWrapperElement,
            givenOptions.map((option) => ({ label: option.textContent, value: option.value }))
        );

        expect(Array.from(givenSelect.options)).toEqual(expectedReorderedOptions);
    });

    test("sortOptions when called, keeps the options selected through the `selected` property", () => {
        const givenWrapperElement = document.createElement("div");
        givenWrapperElement.getRootNode = () => ({ host: { id: "selected-property-id" } });
        document.body.append(givenWrapperElement);

        const givenSelect = document.createElement("select");
        givenSelect.multiple = true;
        givenSelect.setAttribute("element-plus-ref", "selected-property-id");
        const givenOptions = Array.from({ length: 3 }, (_, i) => {
            const option = document.createElement("option");
            option.value = i;
            option.textContent = `Option ${i}`;
            return option;
        });
        givenSelect.append(...givenOptions);
        givenWrapperElement.append(givenSelect);

        // Select through the property only, without the `selected` attribute, the way el-select
        // applies its value to the select element it mirrors.
        givenOptions[0].selected = true;
        givenOptions[2].selected = true;

        const givenOrderedTags = [givenOptions[2], givenOptions[0]].map((option) => {
            const tag = document.createElement("div");
            tag.classList.add("el-select__tags-text");
            tag.textContent = option.textContent;
            return tag;
        });

        givenWrapperElement.append(...givenOrderedTags);

        sortOptions(
            givenWrapperElement,
            givenOptions.map((option) => ({ label: option.textContent, value: option.value }))
        );

        expect(Array.from(givenSelect.selectedOptions).map((option) => option.value)).toEqual(["2", "0"]);
    });
});
