import { describe, expect, test } from "vitest";
import $ from "jquery";
import { handleAffixes, handleFileInput } from "../../../helpers/input/applyInput";

describe("applyInput helper functions", () => {
    beforeEach(() => {
        window.$ = $;
    });

    describe("handleFileInput", () => {
        test("transforms the input element of type file into an el-file-input component", () => {
            const givenInput = $("<input>");
            givenInput.attr("type", "file");

            $("body").append(givenInput);

            handleFileInput(givenInput.get(0));

            const elFileInput = givenInput.next("el-file-input");
            expect(elFileInput.length).toBeTruthy();

            expect(givenInput.attr("element-plus-ref")).toBe(elFileInput.attr("id"));
            expect(givenInput.css("display")).toBe("none");

            expect(elFileInput.attr("accept")).toBeUndefined();
            expect(elFileInput.attr("multiple")).toBeUndefined();
        });

        test("applies the accept and multiple attributes to the el-file-input component", () => {
            const givenInput = $("<input>");
            givenInput.attr("type", "file");
            givenInput.attr("accept", "image/*");
            givenInput.prop("multiple", true);

            $("body").append(givenInput);

            handleFileInput(givenInput.get(0));

            const elFileInput = givenInput.next("el-file-input");
            expect(elFileInput.attr("accept")).toBe(givenInput.attr("accept"));
            expect(elFileInput.attr("multiple")).toBe("multiple");
        });

        test.each([
            [false, { raw: new File(["content"], "test.txt", { type: "text/plain" }) }],
            [
                true,
                [
                    { raw: new File(["content"], "test.txt", { type: "text/plain" }) },
                    { raw: new File(["content"], "test2.txt", { type: "text/plain" }) },
                ],
            ],
        ])("handles correctly the change event when the multiple attribute is set to %s", (multiple, eventData) => {
            const givenInput = $("<input>");
            givenInput.attr("type", "file");
            $("body").append(givenInput);

            handleFileInput(givenInput.get(0));

            const elFileInput = givenInput.next("el-file-input");
            const event = $.Event("change", { detail: [eventData] });
            elFileInput.trigger(event);

            if (multiple) {
                expect(givenInput[0].files).toEqual(eventData.map((file) => file.raw));
            } else {
                expect(givenInput[0].files).toEqual([eventData.raw]);
            }
        });
    });

    describe("handleAffixes", () => {
        test("adds the affixes to the el-input component for bootsrap grouped inputs", () => {
            const givenInput = $("<input>");
            const givenElementPlusUi = $("<el-input></el-input>");

            const prependAffix = $("<span class='input-group-text'>Prepend</span>");
            const appendAffix = $("<span class='input-group-text'>Append</span>");

            $("body").append(prependAffix);
            $("body").append(givenInput);
            $("body").append(appendAffix);

            handleAffixes(givenInput.get(0), givenElementPlusUi);

            expect(givenElementPlusUi.attr("prepend-text")).toBe(prependAffix.text());
            expect(givenElementPlusUi.attr("append-text")).toBe(appendAffix.text());
        });

        test("should not add affix siblings that are not input-group-text", () => {
            const givenInput = $("<input>");
            const givenElementPlusUi = $("<el-input></el-input>");

            const nonAffixSibling = $("<span class='non-affix'>Non affix</span>");

            $("body").append(nonAffixSibling);
            $("body").append(givenInput);
            $("body").append(nonAffixSibling);

            handleAffixes(givenInput.get(0), givenElementPlusUi);

            expect(givenElementPlusUi.attr("prepend-text")).toBeUndefined();
            expect(givenElementPlusUi.attr("append-text")).toBeUndefined();
        });
    });
});
