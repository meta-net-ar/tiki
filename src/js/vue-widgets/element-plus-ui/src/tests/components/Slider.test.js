import { render, screen } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import { h } from "vue";
import Slider, { DATA_TEST_ID } from "../../components/Slider/Slider.vue";
import { ElSlider } from "element-plus";

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElSlider: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
    };
});

describe("Slider", () => {
    afterEach(() => {
        vi.clearAllMocks();
    });

    describe("render tests", () => {
        test("renders correctly with some basic props", () => {
            const exposeMock = vi.fn();

            render(Slider, {
                props: { _expose: exposeMock },
            });

            expect(exposeMock).toHaveBeenCalledWith({ value: expect.objectContaining({ _value: 0, __v_isRef: true }) });

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;

            expect(screen.getByTestId(DATA_TEST_ID.SLIDER_ELEMENT)).to.exist;
            expect(ElSlider).toHaveBeenCalledWith(
                expect.objectContaining({
                    min: 0,
                    max: 100,
                    step: 1,
                    range: false,
                    vertical: false,
                    "show-stops": false,
                    "show-input": false,
                    modelValue: 0,
                }),
                expect.anything()
            );
        });

        test("renders correctly with advanced props", () => {
            const exposeMock = vi.fn();

            render(Slider, {
                props: {
                    _expose: exposeMock,
                    min: 10,
                    max: 90,
                    step: 5,
                    value: "15,30",
                    range: "true",
                    vertical: "true",
                    "show-stops": "true",
                    "show-input": "true",
                },
            });

            expect(exposeMock).toHaveBeenCalledWith({ value: expect.objectContaining({ _value: [15, 30], __v_isRef: true }) });

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;

            expect(screen.getByTestId(DATA_TEST_ID.SLIDER_ELEMENT)).to.exist;
            expect(ElSlider).toHaveBeenCalledWith(
                expect.objectContaining({
                    min: 10,
                    max: 90,
                    step: 5,
                    modelValue: [15, 30],
                    range: true,
                    vertical: true,
                    "show-stops": true,
                    "show-input": true,
                }),
                expect.anything()
            );
        });
    });

    describe("action tests", () => {
        test("emits the change event when the slider value changes", async () => {
            const emitMock = vi.fn();

            render(Slider, {
                props: { _expose: vi.fn(), _emit: emitMock },
            });

            ElSlider.mock.calls[0][0].onChange(50);

            expect(emitMock).toHaveBeenCalledWith("change", 50);
            expect(emitMock).toHaveBeenCalledTimes(1);
        });
    });
});
