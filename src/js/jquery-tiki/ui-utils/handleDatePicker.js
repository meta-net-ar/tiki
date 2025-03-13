import moment from "moment";
import { goToURLWithData } from "./helpers";

export default function handleDatePicker(selector, options) {
    const element = $(selector);
    if (options.date) {
        element.attr("value", moment.unix(options.date).toISOString());
    }
    if (options.endDate) {
        element.attr("value", element.attr("value") + "," + moment.unix(options.endDate).toISOString());
    }

    const inputDateHolder = $(`<input type="hidden" name="${options.fieldName}" value="${options.date || ""}">`);
    const inputEndDateHolder = $(`<input type="hidden" name="${options.endFieldName}" value="${options.endDate || ""}">`);
    const inputTimezoneHolder = $(`<input type="hidden" name="${options.timezoneFieldName}" value="${element.attr("timezone") || ""}">`);

    element.after(inputDateHolder);
    if (options.endFieldName) {
        element.after(inputEndDateHolder);
    }

    element[0].addEventListener("change", (e) => {
        const value = e.detail[0];
        if (Array.isArray(value)) {
            inputDateHolder.attr("value", moment(value[0]).unix());
            inputEndDateHolder.attr("value", moment(value[1]).unix());
        } else {
            inputDateHolder.attr("value", moment(value).unix());
        }
        if (options.goto) {
            goToURLWithData(value, options.goto, inputDateHolder.val(), inputEndDateHolder.val(), element.attr("timezone"), options.globalCallback);
        }
    });

    if (element.attr("custom-timezone") === "true") {
        element.after(inputTimezoneHolder);
        element[0].addEventListener("timezoneChange", (e) => {
            inputTimezoneHolder.attr("value", e.detail[0]);
        });
    }
}
