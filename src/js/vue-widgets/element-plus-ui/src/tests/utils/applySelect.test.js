import { afterEach, beforeEach, expect, test, vi } from "vitest";
import $ from "jquery";
import applySelect, { transformContainerSelects } from "../../utils/applySelect";
import * as applySelectHelpers from "../../helpers/select/applySelect";

describe("applySelect", () => {
    beforeEach(() => {
        window.$ = $;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.innerHTML = "";
    });

    test("does not transform select elements without an explicit opt-in marker", async () => {
        window.elementPlus = {
            select: {},
        };

        const givenSelect = document.createElement("select");
        document.body.appendChild(givenSelect);

        transformContainerSelects(document.body);

        expect(givenSelect.getAttribute("element-plus-ref")).toBeNull();
        expect(document.querySelector("el-select")).toBeNull();
        expect(givenSelect.style.display).toBe("");
    });

    test("transforms opted-in select elements into el-select components", async () => {
        window.elementPlus = {
            select: {},
        };

        const observeSelectElementMutationsSpy = vi.spyOn(applySelectHelpers, "observeSelectElementMutations");
        const syncSelectOptionsSpy = vi.spyOn(applySelectHelpers, "syncSelectOptions");
        const attachChangeEventHandlerSpy = vi.spyOn(applySelectHelpers, "attachChangeEventHandler");

        const givenSelects = [];
        for (let i = 0; i < 5; i++) {
            const select = document.createElement("select");
            select.classList.add("tiki-elementplus-select");
            select.setAttribute("placeholder", `Select ${i}`);
            if (i % 2 === 0) {
                select.multiple = true;
                select.setAttribute("data-max", i);
            }
            const option = document.createElement("option");
            const value = Math.random().toString(36).substring(7);
            option.value = `Option ${value}`;
            option.text = `Option ${value}`;
            option.disabled = i % 2 === 0;
            select.appendChild(option);
            givenSelects.push(select);
        }

        document.body.append(...givenSelects);

        transformContainerSelects(document.body);

        givenSelects.forEach((select, index) => {
            const elementPlusUi = document.querySelector(`#${select.getAttribute("element-plus-ref")}`);
            expect(elementPlusUi).toBeTruthy();
            expect(elementPlusUi.tagName).toBe("EL-SELECT");
            expect(elementPlusUi.getAttribute("placeholder")).toBe(select.getAttribute("placeholder"));
            expect(elementPlusUi.getAttribute("multiple")).toBe(select.multiple ? "multiple" : null);
            expect(elementPlusUi.getAttribute("max")).toBe(select.getAttribute("data-max"));

            expect(select.style.display).toBe("none");

            expect(observeSelectElementMutationsSpy).toHaveBeenNthCalledWith(index + 1, select, elementPlusUi);
            expect(syncSelectOptionsSpy).toHaveBeenNthCalledWith(index + 1, elementPlusUi, select);
            expect(attachChangeEventHandlerSpy).toHaveBeenNthCalledWith(index + 1, elementPlusUi, select);
        });
    });

    test.each([
        [{ clearable: true, collapseTags: true, maxCollapseTags: 2, filterable: true, allowCreate: true }, "true"],
        [{ clearable: false, collapseTags: false, maxCollapseTags: 0, filterable: false, allowCreate: false }, "false"],
    ])("applies the right attributes to the el-select component based on the preferences", async (preferences, expectedBoolean) => {
        window.elementPlus = {
            select: preferences,
        };

        const givenSelect = document.createElement("select");
        givenSelect.setAttribute("data-elementplus-select", "true");
        document.body.appendChild(givenSelect);

        transformContainerSelects(document.body);

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("clearable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("collapse-tags")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("max-collapse-tags")).toBe(preferences.maxCollapseTags.toString());
        expect(elementPlusUi.getAttribute("filterable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("allow-create")).toBe(expectedBoolean);
    });

    test("does not transform opted-in selects more than once", async () => {
        window.elementPlus = {
            select: {},
        };

        const givenSelect = document.createElement("select");
        givenSelect.classList.add("tiki-elementplus-select");
        document.body.appendChild(givenSelect);

        transformContainerSelects(document.body);
        transformContainerSelects(document.body);

        expect(document.querySelectorAll("el-select")).toHaveLength(1);
    });

    test("observes and transforms opted-in selects added after applySelect runs", async () => {
        window.elementPlus = {
            select: {},
        };

        applySelect();

        const givenSelect = document.createElement("select");
        givenSelect.classList.add("tiki-elementplus-select");
        document.body.appendChild(givenSelect);

        await new Promise((resolve) => setTimeout(resolve, 0));

        const elementPlusUi = document.querySelector(`#${givenSelect.getAttribute("element-plus-ref")}`);
        expect(elementPlusUi).toBeTruthy();
        expect(elementPlusUi.tagName).toBe("EL-SELECT");
        expect(givenSelect.style.display).toBe("none");
    });

    test("applies the small size to the el-select component if the select element has the form-control-sm class", async () => {
        window.elementPlus = {
            select: {},
        };

        const givenSelect = document.createElement("select");
        givenSelect.classList.add("tiki-elementplus-select");
        givenSelect.classList.add("form-control-sm");
        document.body.appendChild(givenSelect);

        transformContainerSelects(document.body);

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("size")).toBe("small");
    });

    test("applies the remote-source-url attribute to the el-select component if the select element has the data-remote-source-url attribute", async () => {
        window.elementPlus = {
            select: {},
        };

        const givenSelect = document.createElement("select");
        givenSelect.classList.add("tiki-elementplus-select");
        const givenRemoteSourceUrl = "http://foo/bar";
        givenSelect.setAttribute("data-remote-source-url", givenRemoteSourceUrl);
        document.body.appendChild(givenSelect);

        transformContainerSelects(document.body);

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("remote-source-url")).toBe(givenRemoteSourceUrl);
    });
});
