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
});
