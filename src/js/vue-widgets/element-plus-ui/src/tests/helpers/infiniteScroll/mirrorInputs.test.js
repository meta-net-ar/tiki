import { beforeAll, describe, expect, test, vi } from "vitest";
import mirrorInputs from "../../../helpers/infiniteScroll/mirrorInputs";
import $ from "jquery";

describe("mirrorInputs", () => {
    beforeAll(() => {
        window.$ = $;
    });

    test("copies all input elements from the source container to the host parent element of infinite scroll component", () => {
        const givenSourceContainer = document.createElement("div");
        givenSourceContainer.innerHTML = `
            <input type="text" id="input-text" value="Sample Text" />
            <input type="checkbox" id="input-checkbox" checked />
            <input type="radio" id="input-radio" />
            <select id="select-element">
                <option value="option1" selected>Option 1</option>
                <option value="option2">Option 2</option>
            </select>
            <textarea id="textarea-element">Sample Textarea</textarea>
            <input type="submit" id="input-submit" value="Submit" />
            <input type="button" id="input-button" value="Click Me" />
        `;

        const mockHostParentElement = document.createElement("div");
        const mockScrollbarRef = {
            value: {
                wrapRef: {
                    getRootNode: () => ({
                        host: {
                            parentElement: mockHostParentElement,
                        },
                    }),
                },
            },
        };

        mirrorInputs(givenSourceContainer, mockScrollbarRef);

        const mirroredInputs = mockHostParentElement.querySelectorAll("input, select, textarea");
        expect(mirroredInputs.length).toBe(7);
        givenSourceContainer.querySelectorAll("input, select, textarea").forEach((input, index) => {
            const mirroredInput = mirroredInputs[index];
            expect(mirroredInput.type).toBe(input.type);
            expect(mirroredInput.value).toBe(input.value);
            if (input.type === "checkbox" || input.type === "radio") {
                expect(mirroredInput.checked).toBe(input.checked);
            }
            expect(mirroredInput.style.display).toBe("none");
        });
    });

    test("synchronizes changes from original inputs to mirrored inputs and vice versa", async () => {
        const givenSourceContainer = document.createElement("div");
        givenSourceContainer.innerHTML = `
            <input type="text" id="input-text" value="Sample Text" />
            <input type="checkbox" id="input-checkbox" checked />
            <input type="radio" id="input-radio" />
            <select id="select-element">
                <option value="option1" selected>Option 1</option>
                <option value="option2">Option 2</option>
            </select>
            <textarea id="textarea-element">Sample Textarea</textarea>
        `;

        const mockHostParentElement = document.createElement("div");
        const mockScrollbarRef = {
            value: {
                wrapRef: {
                    getRootNode: () => ({
                        host: {
                            parentElement: mockHostParentElement,
                        },
                    }),
                },
            },
        };

        mirrorInputs(givenSourceContainer, mockScrollbarRef);

        givenSourceContainer.querySelectorAll("input, select, textarea").forEach((input) => {
            const mirroredInput = mockHostParentElement.querySelector(`#${input.id}`);

            const mockMirrorChangeCallback = vi.fn();
            $(mirroredInput).on("change", mockMirrorChangeCallback);

            // Change original input and check mirrored input
            if (input.type === "checkbox" || input.type === "radio") {
                input.checked = !input.checked;
                $(input).trigger("change");
                expect(mirroredInput.checked).toBe(input.checked);
            } else {
                input.value = "Updated Value";
                $(input).trigger("change");
                expect(mirroredInput.value).toBe(input.value);
            }

            expect(mockMirrorChangeCallback).toHaveBeenCalled();

            // Change mirrored input and check original input
            if (input.type === "checkbox" || input.type === "radio") {
                mirroredInput.checked = !mirroredInput.checked;
                $(mirroredInput).trigger("change");
                expect(input.checked).toBe(mirroredInput.checked);
            } else {
                mirroredInput.value = "Mirrored Updated Value";
                $(mirroredInput).trigger("change");
                expect(input.value).toBe(mirroredInput.value);
            }
        });
    });

    test("clicking submit and button inputs triggers click on mirrored inputs", () => {
        const givenSourceContainer = document.createElement("div");
        givenSourceContainer.innerHTML = `
            <input type="submit" id="input-submit" value="Submit" />
            <input type="button" id="input-button" value="Click Me" />
        `;

        const mockHostParentElement = document.createElement("div");
        const mockScrollbarRef = {
            value: {
                wrapRef: {
                    getRootNode: () => ({
                        host: {
                            parentElement: mockHostParentElement,
                        },
                    }),
                },
            },
        };

        mirrorInputs(givenSourceContainer, mockScrollbarRef);

        ["input-submit", "input-button"].forEach((id) => {
            const originalInput = givenSourceContainer.querySelector(`#${id}`);
            const mirroredInput = mockHostParentElement.querySelector(`#${id}`);

            const mockMirrorClickCallback = vi.fn();
            mirroredInput.addEventListener("click", mockMirrorClickCallback);

            // Click original input and check mirrored input
            originalInput.click();
            expect(mockMirrorClickCallback).toHaveBeenCalled();
        });
    });
});
