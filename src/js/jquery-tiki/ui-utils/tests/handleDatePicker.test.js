import { afterEach, describe, expect, test, vi } from "vitest";
import moment from "moment";
import handleDatePicker from "../handleDatePicker";
import $ from "jquery";
import * as Helpers from "../helpers";

vi.mock("../helpers", () => {
    return {
        goToURLWithData: vi.fn(),
    };
});

describe("handleDatePicker", () => {
    window.$ = $;

    afterEach(() => {
        document.body.innerHTML = "";
    });

    test("create input data holders and assign correct attributes to the given date picker when all options are provided", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" timezone="UTC" custom-timezone="true" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            endDate: moment("2021-01-02").unix(),
            fieldName: "date",
            endFieldName: "endDate",
            timezoneFieldName: "timezone",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe(options.date.toString());
        const inputEndDateHolder = $(`input[name="${options.endFieldName}"]`);
        expect(inputEndDateHolder[0]).to.exist;
        expect(inputEndDateHolder.attr("value")).toBe(options.endDate.toString());
        const inputTimezoneHolder = $(`input[name="${options.timezoneFieldName}"]`);
        expect(inputTimezoneHolder[0]).to.exist;
        expect(inputTimezoneHolder.attr("value")).toBe("UTC");

        expect(givenDatePickerElement.attr("value")).toBe(`${moment.unix(options.date).toISOString()},${moment.unix(options.endDate).toISOString()}`);
    });

    test("create input data holders and assign correct attributes to the given date picker when in non-range mode", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" timezone="UTC" custom-timezone="true" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            fieldName: "date",
            timezoneFieldName: "timezone",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe(options.date.toString());
        const inputTimezoneHolder = $(`input[name="${options.timezoneFieldName}"]`);
        expect(inputTimezoneHolder[0]).to.exist;
        expect(inputTimezoneHolder.attr("value")).toBe("UTC");

        expect(givenDatePickerElement.attr("value")).toBe(moment.unix(options.date).toISOString());
    });

    test("create input data holders and assign correct attributes to the given date picker when no cutom timezone is requested", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            fieldName: "date",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe(options.date.toString());

        expect(givenDatePickerElement.attr("value")).toBe(moment.unix(options.date).toISOString());
    });

    test("create input data holders when no default values are provided", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            fieldName: "date",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe("");

        expect(givenDatePickerElement.attr("value")).toBeUndefined();
    });

    test("update the input date holder when the date picker value changes", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            fieldName: "date",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe(options.date.toString());

        givenDatePickerElement[0].dispatchEvent(new CustomEvent("change", { detail: [moment("2021-01-02").toDate()] }));

        expect(inputDateHolder.attr("value")).toBe(moment("2021-01-02").unix().toString());
    });

    test("update the input date holders when the date picker value changes in range mode", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            endDate: moment("2021-01-02").unix(),
            fieldName: "date",
            endFieldName: "endDate",
        };

        handleDatePicker("#date-picker", options);

        const inputDateHolder = $(`input[name="${options.fieldName}"]`);
        expect(inputDateHolder[0]).to.exist;
        expect(inputDateHolder.attr("value")).toBe(options.date.toString());
        const inputEndDateHolder = $(`input[name="${options.endFieldName}"]`);
        expect(inputEndDateHolder[0]).to.exist;
        expect(inputEndDateHolder.attr("value")).toBe(options.endDate.toString());

        givenDatePickerElement[0].dispatchEvent(
            new CustomEvent("change", { detail: [[moment("2021-01-03").toDate(), moment("2021-01-04").toDate()]] })
        );

        expect(inputDateHolder.attr("value")).toBe(moment("2021-01-03").unix().toString());
        expect(inputEndDateHolder.attr("value")).toBe(moment("2021-01-04").unix().toString());
    });

    test("calls the goToURLWithData function when the date picker value changes and the goto option is provided", async () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" timezone="Afrika/Abidjan" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            fieldName: "date",
            goto: "/test",
        };

        handleDatePicker("#date-picker", options);

        const newValue = [moment("2021-01-02").toDate(), moment("2021-01-03").toDate()];
        givenDatePickerElement[0].dispatchEvent(new CustomEvent("change", { detail: [newValue] }));

        expect(Helpers.goToURLWithData).toHaveBeenCalledWith(
            newValue,
            options.goto,
            moment("2021-01-02").unix().toString(),
            moment("2021-01-03").unix().toString(),
            "Afrika/Abidjan",
            undefined
        );
    });

    test("update the input timezone holder when the date picker timezone changes", () => {
        const givenDatePickerElement = $(`<el-date-picker id="date-picker" timezone="UTC" custom-timezone="true" />`);
        $(document.body).append(givenDatePickerElement);
        const options = {
            date: moment("2021-01-01").unix(),
            fieldName: "date",
            timezoneFieldName: "timezone",
        };

        handleDatePicker("#date-picker", options);

        const inputTimezoneHolder = $(`input[name="${options.timezoneFieldName}"]`);
        expect(inputTimezoneHolder[0]).to.exist;
        expect(inputTimezoneHolder.attr("value")).toBe("UTC");

        givenDatePickerElement[0].dispatchEvent(new CustomEvent("timezoneChange", { detail: ["Africa/Abidjan"] }));

        expect(inputTimezoneHolder.attr("value")).toBe("Africa/Abidjan");
    });
});
