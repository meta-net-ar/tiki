import { afterEach, beforeAll, describe, expect, test } from "vitest";
import handleTransferList from "../handleTransferList";
import $ from "jquery";

describe("handleTransferList", () => {
    beforeAll(() => {
        window.$ = $;
    });

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("create a select element with the correct attributes", () => {
        const givenId = "transfer-list";
        const givenFieldName = "transfer-list-field";
        const givenTransfer = document.createElement("div");
        givenTransfer.id = givenId;
        document.body.appendChild(givenTransfer);

        handleTransferList(givenId, givenFieldName);

        const select = document.querySelector(`select[name="${givenFieldName}"]`);
        expect(select).toBeTruthy();
        expect(select.multiple).toBe(true);
        expect(select.getAttribute("element-plus-ref")).toBe(givenId);
        expect(select.getAttribute("aria-hidden")).toBe("true");
        expect(select.parentNode.style.display).toBe("none");
    });

    test("create a select element with the default values", () => {
        const givenId = "transfer-list";
        const givenFieldName = "transfer-list-field";
        const givenTransfer = document.createElement("div");
        givenTransfer.id = givenId;
        givenTransfer.setAttribute("default-value", JSON.stringify(["1", "2"]));
        document.body.appendChild(givenTransfer);

        handleTransferList(givenId, givenFieldName);

        const select = document.querySelector(`select[name="${givenFieldName}"]`);
        expect(select).toBeTruthy();
        expect(select.multiple).toBe(true);
        expect(select.getAttribute("element-plus-ref")).toBe(givenId);
        expect(select.options.length).toBe(2);
        expect(select.options[0].value).toBe("1");
        expect(select.options[0].selected).toBe(true);
        expect(select.options[1].value).toBe("2");
        expect(select.options[1].selected).toBe(true);
    });

    test("update the select element when the transfer list changes", () => {
        const givenId = "transfer-list";
        const givenFieldName = "transfer-list-field";
        const givenTransfer = document.createElement("div");
        givenTransfer.id = givenId;
        document.body.appendChild(givenTransfer);

        handleTransferList(givenId, givenFieldName);

        const select = document.querySelector(`select[name="${givenFieldName}"]`);
        expect(select).toBeTruthy();
        expect(select.options.length).toBe(0);

        const givenTransferEvent = new CustomEvent("transfer-change", {
            detail: [{ value: ["3", "4"] }],
        });
        givenTransfer.dispatchEvent(givenTransferEvent);

        expect(select.options.length).toBe(2);
        expect(select.options[0].value).toBe("3");
        expect(select.options[0].selected).toBe(true);
        expect(select.options[1].value).toBe("4");
        expect(select.options[1].selected).toBe(true);
    });
});
