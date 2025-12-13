import { beforeAll, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import handleSlider from "../handlerSlider";

describe("handleSlider", () => {
    beforeAll(() => {
        window.$ = $;
    });

    test("should correctly handle the slider element", () => {
        const givenSliderElement = $("<el-slider id='slider' value='50'></el-slider>");
        const fieldName = "testField";

        $("body").append(givenSliderElement);

        handleSlider("#slider", fieldName);

        const inputHolder = givenSliderElement.next(`input[name="${fieldName}"]`);
        expect(inputHolder).to.exist;
        expect(inputHolder.attr("type")).toBe("hidden");
        expect(inputHolder.val()).toBe("50");

        const inputChangeHandlerMock = vi.fn();
        inputHolder.on("change", inputChangeHandlerMock);

        // Correctly handle value change
        givenSliderElement[0].dispatchEvent(
            new CustomEvent("change", {
                detail: [75],
            })
        );
        expect(inputHolder.val()).toBe("75");
        expect(inputChangeHandlerMock).toHaveBeenCalled();
        expect(inputChangeHandlerMock).toHaveBeenCalledTimes(1);

        // Correctly handle range value change
        givenSliderElement[0].dispatchEvent(
            new CustomEvent("change", {
                detail: [[25, 75]],
            })
        );
        expect(inputHolder.val()).toBe("25,75");
        expect(inputChangeHandlerMock).toHaveBeenCalledTimes(2);
    });
});
