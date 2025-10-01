import { describe, test, expect, beforeAll, vi, beforeEach } from "vitest";
import applyInput from "../../utils/applyInput";
import $ from "jquery";
import { handleAffixes, handleFileInput } from "../../helpers/input/applyInput";

vi.mock("../../helpers/input/applyInput", () => ({
    handleAffixes: vi.fn(),
    handleFileInput: vi.fn(),
}));

describe("applyInput", () => {
    beforeAll(() => {
        window.$ = $;
    });

    beforeEach(() => {
        $("body").empty();
    });

    test.each([["text"], [undefined], ["number"], ["email"], ["password"], ["search"], ["url"], ["tel"]])(
        "transforms input elements of type %s into el-input components",
        async (givenType) => {
            applyInput();

            const givenInputs = [];
            for (let i = 0; i < 3; i++) {
                const input = $("<input>");
                input.attr("placeholder", `Input ${i}`);
                input.attr("type", givenType);
                input.attr("value", `Value ${i}`);
                givenInputs.push(input);
            }

            $("body").append(givenInputs);

            await window.happyDOM.waitUntilComplete();

            givenInputs.forEach((input) => {
                const elInput = input.next("el-input");
                expect(elInput.length).toBeTruthy();

                expect(input.attr("element-plus-ref")).toBe(elInput.attr("id"));
                expect(input.css("display")).toBe("none");

                expect(elInput.attr("placeholder")).toBe(input.attr("placeholder"));
                expect(elInput.attr("value")).toBe(input.attr("value"));
                if (givenType === "password") {
                    expect(elInput.attr("show-password")).toBe("true");
                } else {
                    expect(elInput.attr("type")).toBe(givenType);
                }

                expect(handleAffixes).toBeCalledWith(input.get(0), expect.any(Object));
                const handleAffixesCalls = handleAffixes.mock.calls.find((call) => call[0] === input.get(0));
                expect(handleAffixesCalls[1].prop("outerHTML")).toBe(elInput.prop("outerHTML"));
            });
        }
    );

    test("applies all given attributes to the el-input component", async () => {
        applyInput();

        const input = $("<input>");
        input.attr("placeholder", "Input");
        input.attr("type", "text");
        input.attr("value", "Value");
        input.attr("disabled", true);
        input.attr("autocomplete", "on");
        input.attr("name", "input-name");
        input.attr("autofocus", true);

        $("body").append(input);

        await window.happyDOM.waitUntilComplete();

        const elInput = $(`el-input#${input.attr("element-plus-ref")}`);
        expect(elInput.length).toBeTruthy();
        expect(elInput.attr("placeholder")).toBe(input.attr("placeholder"));
        expect(elInput.attr("value")).toBe(input.attr("value"));
        expect(elInput.attr("disabled")).toBe("disabled");
        expect(elInput.attr("autocomplete")).toBe(input.attr("autocomplete"));
        expect(elInput.attr("name")).toBe(input.attr("name"));
    });

    test("handles correctly inputs of file type", async () => {
        applyInput();

        const input = $("<input>");
        input.attr("type", "file");

        $("body").append(input);

        await window.happyDOM.waitUntilComplete();

        expect(handleFileInput).toBeCalledWith(input.get(0));

        // should not change the current input element
        expect($("body").html()).toBe(input.prop("outerHTML"));
    });

    test("should not transform non-visible inputs", async () => {
        applyInput();
        const input = $("<input>");
        input.css("display", "none");

        $("body").append(input);

        await window.happyDOM.waitUntilComplete();

        expect($("body").html()).toBe(input.prop("outerHTML"));
    });

    test.each([["cypht-layout"], ["tiki-webmail"]])("should not transform inputs inside %s", async (givenClass) => {
        applyInput();
        const input = $("<input>");
        const layout = $(`<div class='${givenClass}'></div>`);
        layout.append(input);
        $("body").append(layout);

        await window.happyDOM.waitUntilComplete();
        expect($("body").html()).toBe(`<div class="${givenClass}"><input></div>`);
    });

    test.each([["change", ["new value"]], ["input", ["new value"]], ["input", ["input value"]], ["blur"], ["focus"], ["keyup"], ["keydown"]])(
        "handles correctly the %s event",
        async (eventName, eventDetail) => {
            applyInput();

            const input = $("<input>");
            input.attr("placeholder", "Input");
            input.attr("type", "text");
            input.attr("value", "Value");

            const eventHandler = vi.fn();
            input.on(eventName, eventHandler);

            $("body").append(input);

            await window.happyDOM.waitUntilComplete();

            const elInput = $(`el-input#${input.attr("element-plus-ref")}`);
            elInput.val("updated value");

            const event = $.Event(eventName, { detail: eventDetail });
            elInput.trigger(event);

            await window.happyDOM.waitUntilComplete();

            expect(eventHandler).toHaveBeenCalled();
            if (eventDetail) {
                expect(input.val()).toBe(eventDetail[0]);
            } else {
                expect(input.val()).toBe(elInput.val());
            }
        }
    );

    test.each([
        ["submit", "type", "search", true],
        ["submit", "role", "search", true],
        ["submit", "type", "text", true],
    ])("the enter event should %s when the %s is %s", async (_, attr, attrValue, shouldSubmit) => {
        applyInput();

        const input = $("<input>");
        input.attr("placeholder", "Input");
        input.attr(attr, attrValue);
        input.attr("value", "Value");

        const form = $("<form></form>");
        form.append(input);
        $("body").append(form);

        const submitHandler = vi.fn();
        form.on("submit", submitHandler);

        await window.happyDOM.waitUntilComplete();

        const elInput = $(`el-input#${input.attr("element-plus-ref")}`);

        const event = $.Event("enter");
        elInput.trigger(event);

        await window.happyDOM.waitUntilComplete();

        if (shouldSubmit) {
            expect(submitHandler).toHaveBeenCalled();
        } else {
            expect(submitHandler).not.toHaveBeenCalled();
        }
    });
});
