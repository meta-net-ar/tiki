import { afterEach, beforeEach, expect, test, vi } from "vitest";
import { Window } from "happy-dom";
import $ from "jquery";
import applySelect from "../../utils/applySelect";
import * as applySelectHelpers from "../../helpers/select/applySelect";

describe("applySelect", () => {
    beforeEach(() => {
        window.$ = $;
    });

    afterEach(() => {
        global.window = new Window();
        global.document = window.document;
    });

    test("transforms select element into el-select components", async () => {
        window.elementPlus = {
            select: {},
        };

        const observeSelectElementMutationsSpy = vi.spyOn(applySelectHelpers, "observeSelectElementMutations");
        const syncSelectOptionsSpy = vi.spyOn(applySelectHelpers, "syncSelectOptions");
        const attachChangeEventHandlerSpy = vi.spyOn(applySelectHelpers, "attachChangeEventHandler");

        applySelect();

        const givenSelects = [];
        for (let i = 0; i < 5; i++) {
            const select = document.createElement("select");
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

        await window.happyDOM.waitUntilComplete();

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

        applySelect();

        const givenSelect = document.createElement("select");
        document.body.appendChild(givenSelect);

        await window.happyDOM.waitUntilComplete();

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("clearable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("collapse-tags")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("max-collapse-tags")).toBe(preferences.maxCollapseTags.toString());
        expect(elementPlusUi.getAttribute("filterable")).toBe(expectedBoolean);
        expect(elementPlusUi.getAttribute("allow-create")).toBe(expectedBoolean);
    });

    test("applies the small size to the el-select component if the select element has the form-control-sm class", async () => {
        window.elementPlus = {
            select: {},
        };

        applySelect();

        const givenSelect = document.createElement("select");
        givenSelect.classList.add("form-control-sm");
        document.body.appendChild(givenSelect);

        await window.happyDOM.waitUntilComplete();

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("size")).toBe("small");
    });

    test("applies the remote-source-url attribute to the el-select component if the select element has the data-remote-source-url attribute", async () => {
        window.elementPlus = {
            select: {},
        };

        applySelect();

        const givenSelect = document.createElement("select");
        const givenRemoteSourceUrl = "http://foo/bar";
        givenSelect.setAttribute("data-remote-source-url", givenRemoteSourceUrl);
        document.body.appendChild(givenSelect);

        await window.happyDOM.waitUntilComplete();

        const elementPlusUi = document.querySelector("el-select");
        expect(elementPlusUi.getAttribute("remote-source-url")).toBe(givenRemoteSourceUrl);
    });
});
