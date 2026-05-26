import { render, within, screen, waitFor } from "@testing-library/vue";
import { afterEach, describe, expect, test, vi } from "vitest";
import DatePicker, { DATA_TEST_ID } from "../../components/DatePicker/DatePicker.vue";
import { ElDatePicker, ElSelect } from "element-plus";
import { h } from "vue";
import * as getShortcuts from "../../helpers/datePicker/getShortcuts";
import moment from "moment-timezone";
import ConfigWrapper from "../../components/ConfigWrapper.vue";

vi.mock("element-plus", async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        ElDatePicker: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
        ElSelect: vi.fn((props, { slots }) => h("div", props, slots.default ? slots.default() : null)),
        ElOption: vi.fn((props) => h("div", props)),
    };
});

vi.mock("../../components/ConfigWrapper.vue", () => {
    return {
        default: vi.fn((props, { slots }) => h("div", { ...props, "data-testid": "config-wrapper" }, slots.default ? slots.default() : null)),
    };
});

describe("DatePicker", () => {
    const mockShortcuts = ["foo", "bar"];
    const getShortcutsSpy = vi.spyOn(getShortcuts, "default").mockImplementationOnce(() => mockShortcuts);

    const consoleErrorSpy = vi.spyOn(console, "error");
    const consoleWarnSpy = vi.spyOn(console, "warn");

    afterEach(() => {
        vi.clearAllMocks();
    });

    describe("render tests", () => {
        test("renders correctly with default props", () => {
            const givenProps = {
                _expose: vi.fn(),
                _emit: vi.fn(),
            };

            render(DatePicker, {
                props: givenProps,
            });

            expect(getShortcutsSpy).toHaveBeenCalledWith(false);

            expect(ElDatePicker).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: "date",
                    placeholder: "Pick a date",
                    teleported: false,
                    shortcuts: mockShortcuts,
                    "start-placeholder": "Start date",
                    "end-placeholder": "End date",
                }),
                expect.any(Object)
            );

            expect(ConfigWrapper).toHaveBeenCalledWith(
                {
                    language: "en",
                },
                expect.any(Object)
            );

            const exposedData = givenProps._expose.mock.calls[0][0];
            expect(exposedData.date.value).toBe("");
            expect(exposedData.timezone.value).toBe("");

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;
            const datePickerElement = within(configWrapper).getByTestId(DATA_TEST_ID.DATE_PICKER);
            expect(datePickerElement).to.exist;
            const timezonePicker = screen.queryByTestId(DATA_TEST_ID.TIMEZONE_PICKER);
            expect(timezonePicker).not.to.exist;
            expect(screen.queryByTestId(DATA_TEST_ID.TIMEZONE_PICKER_SELECT)).not.to.exist;
            expect(screen.queryByTestId(DATA_TEST_ID.TIMEZONE_PICKER_OPTION)).not.to.exist;

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });

        test("renders correctly with all props", () => {
            const givenProps = {
                _expose: vi.fn(),
                _emit: vi.fn(),
                type: "datetime",
                value: new Date().toISOString(),
                timezone: "UTC",
                placeholder: "Select a date",
                startPlaceholder: "Start at",
                endPlaceholder: "End at",
                customTimezone: "true",
                format: "YYYY-MM-DD HH:mm:ss",
                rangeSeparator: "to",
                language: "fr",
                timezonePlaceholder: "Select a timezone",
            };

            render(DatePicker, {
                props: givenProps,
            });

            expect(ElDatePicker).toHaveBeenCalledWith(
                expect.objectContaining({
                    type: givenProps.type,
                    modelValue: givenProps.value,
                    placeholder: givenProps.placeholder,
                    teleported: false,
                    format: givenProps.format,
                    "start-placeholder": givenProps.startPlaceholder,
                    "end-placeholder": givenProps.endPlaceholder,
                    "range-separator": givenProps.rangeSeparator,
                }),
                expect.any(Object)
            );

            expect(ConfigWrapper).toHaveBeenCalledWith(
                {
                    language: givenProps.language,
                },
                expect.any(Object)
            );

            const configWrapper = screen.getByTestId("config-wrapper");
            expect(configWrapper).to.exist;
            const datePickerElement = within(configWrapper).getByTestId(DATA_TEST_ID.DATE_PICKER);
            expect(datePickerElement).to.exist;
            const timezonePicker = screen.getByTestId(DATA_TEST_ID.TIMEZONE_PICKER);
            expect(timezonePicker).to.exist;
            const timezonePickerSelect = within(timezonePicker).getByTestId(DATA_TEST_ID.TIMEZONE_PICKER_SELECT);
            expect(timezonePickerSelect).to.exist;
            const timezonePickerOption = within(timezonePicker).getAllByTestId(DATA_TEST_ID.TIMEZONE_PICKER_OPTION);
            expect(timezonePickerOption).toHaveLength(moment.tz.names().length);

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();

            const exposedData = givenProps._expose.mock.calls[0][0];
            expect(exposedData.date.value).toBe(givenProps.value);
            expect(exposedData.timezone.value).toBe(givenProps.timezone);
        });

        test.each([
            ["datetime", new Date().toISOString(), false],
            ["date", new Date().toISOString(), false],
            ["datetimerange", [new Date().toISOString(), new Date().toISOString()].join(","), true],
            ["daterange", [new Date().toISOString(), new Date().toISOString()].join(","), true],
        ])("renders correctly with %s type", (type, value, isRange) => {
            const givenProps = {
                _expose: vi.fn(),
                _emit: vi.fn(),
                type,
                value,
            };

            render(DatePicker, {
                props: givenProps,
            });

            expect(getShortcutsSpy).toHaveBeenCalledWith(isRange);

            expect(ElDatePicker).toHaveBeenCalledWith(
                expect.objectContaining({
                    type,
                    modelValue: isRange ? value.split(",") : value,
                }),
                expect.any(Object)
            );

            const exposedData = givenProps._expose.mock.calls[0][0];
            expect(exposedData.date.value).toEqual(isRange ? value.split(",") : value);
            expect(exposedData.timezone.value).toBe("");

            expect(consoleErrorSpy).not.toHaveBeenCalled();
            expect(consoleWarnSpy).not.toHaveBeenCalled();
        });
    });

    describe("action tests", () => {
        test("emits the change event and updates the exposed data when the date changes", async () => {
            const givenProps = {
                _expose: vi.fn(),
                _emit: vi.fn(),
            };

            const willUpdateToDate = new Date("2025-01-25");

            vi.mocked(ElDatePicker).mockImplementationOnce((_, ctx) =>
                h(
                    "div",
                    {},
                    h(
                        "button",
                        {
                            onClick: () => {
                                ctx.emit("change", willUpdateToDate);
                                ctx.emit("update:modelValue", willUpdateToDate);
                            },
                        },
                        "Select"
                    )
                )
            );

            render(DatePicker, {
                props: givenProps,
            });

            const exposedData = givenProps._expose.mock.calls[0][0];

            expect(exposedData.date.value).toBe("");

            screen.getByText("Select").click();

            expect(exposedData.date.value).toBe(willUpdateToDate);
            expect(givenProps._emit).toHaveBeenCalledWith("change", willUpdateToDate);
        });

        test.each([
            ["datetime", new Date().toISOString(), false],
            ["datetimerange", [new Date().toISOString(), new Date().toISOString()].join(","), true],
        ])("when the type is %s, emits the change event and updates the exposed data when the timezone changes", async (type, value, isRange) => {
            const willUpdateToTimezone = "Africa/Abidjan";
            vi.mocked(ElSelect).mockImplementationOnce((props, ctx) =>
                h(
                    "div",
                    props,
                    h(
                        "button",
                        {
                            onClick: () => {
                                ctx.emit("change", willUpdateToTimezone);
                                ctx.emit("update:modelValue", willUpdateToTimezone);
                            },
                        },
                        "Select"
                    )
                )
            );
            const givenProps = {
                _expose: vi.fn(),
                _emit: vi.fn(),
                type,
                value,
                customTimezone: "true",
            };

            render(DatePicker, {
                props: givenProps,
            });

            const exposedData = givenProps._expose.mock.calls[0][0];
            expect(exposedData.timezone.value).toBe("");
            expect(exposedData.date.value).toEqual(isRange ? value.split(",") : value);

            const timezonePickerSelect = screen.getByTestId(DATA_TEST_ID.TIMEZONE_PICKER_SELECT);
            timezonePickerSelect.querySelector("button").click();

            const expectedDateValue = isRange
                ? value.split(",").map((date) => moment(date).tz(willUpdateToTimezone).toDate())
                : moment(value).tz(willUpdateToTimezone).toDate();
            expect(givenProps._emit).toHaveBeenCalledWith("timezoneChange", willUpdateToTimezone);
            expect(givenProps._emit).toHaveBeenCalledWith("change", expectedDateValue);
            expect(exposedData.timezone.value).toBe(willUpdateToTimezone);
            expect(exposedData.date.value).toEqual(expectedDateValue);
        });
    });

    test.todo("updates the value and timezone models when the props change");
});
