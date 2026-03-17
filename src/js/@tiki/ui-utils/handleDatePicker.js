import moment from "moment";
import { goToURLWithData } from "./helpers";

export default function handleDatePicker(selector, options) {
    const element = $(selector);
    const pickerType = String(element.attr("type") || "");
    const isDateOnlyMode = ! pickerType.includes("time");

    const toDateOnlyUnix = (value) => {
        const selected = moment(value);
        return moment.utc([selected.year(), selected.month(), selected.date()]).unix();
    };

    const toPickerValue = (unixSeconds) => {
        if (isDateOnlyMode) {
            return moment.unix(unixSeconds).utc().format("YYYY-MM-DD");
        }

        return moment.unix(unixSeconds).toISOString();
    };

    if (options.date) {
        element.attr("value", toPickerValue(options.date));
    }
    if (options.endDate) {
        element.attr("value", element.attr("value") + "," + toPickerValue(options.endDate));
    }

    const inputDateHolder = $(`<input type="hidden" name="${options.fieldName}" value="${options.date || ""}">`);
    const inputEndDateHolder = $(`<input type="hidden" name="${options.endFieldName}" value="${options.endDate || ""}">`);
    const inputTimezoneHolder = $(`<input type="hidden" name="${options.timezoneFieldName}" value="${element.attr("timezone") || ""}">`);

    element.after(inputDateHolder);
    if (options.endFieldName) {
        element.after(inputEndDateHolder);
    }

    element.on("change", (e) => {
        const value = e.detail[0];
        if (Array.isArray(value)) {
            inputDateHolder.attr("value", isDateOnlyMode ? toDateOnlyUnix(value[0]) : moment(value[0]).unix());
            inputEndDateHolder.attr("value", isDateOnlyMode ? toDateOnlyUnix(value[1]) : moment(value[1]).unix());
            inputEndDateHolder.trigger("change");
        } else {
            inputDateHolder.attr("value", isDateOnlyMode ? toDateOnlyUnix(value) : moment(value).unix());
        }

        inputDateHolder.trigger("change");

        if (options.goto) {
            goToURLWithData(value, options.goto, inputDateHolder.val(), inputEndDateHolder.val(), element.attr("timezone"), options.globalCallback);
        }
    });

    if (element.attr("custom-timezone") === "true") {
        element.after(inputTimezoneHolder);
        element.on("timezoneChange", (e) => {
            inputTimezoneHolder.attr("value", e.detail[0]);
        });
    }
}
