import moment from "moment-timezone";

export default function (isRange = false) {
    return [
        {
            text: "Today",
            value: () => {
                if (isRange) {
                    return [moment().startOf("day").toDate(), moment().endOf("day").toDate()];
                } else {
                    return moment().toDate();
                }
            },
        },
        {
            text: "Last week",
            value: () => {
                if (isRange) {
                    return [moment().subtract(1, "week").startOf("week").toDate(), moment().subtract(1, "week").endOf("week").toDate()];
                } else {
                    return moment().subtract(1, "week").toDate();
                }
            },
        },
        {
            text: "Last month",
            value: () => {
                if (isRange) {
                    return [moment().subtract(1, "month").startOf("month").toDate(), moment().subtract(1, "month").endOf("month").toDate()];
                } else {
                    return moment().subtract(1, "month").toDate();
                }
            },
        },
    ];
}
