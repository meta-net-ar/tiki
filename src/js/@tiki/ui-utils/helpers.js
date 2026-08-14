/**
 * @param {Date|Date[]|null} updatedData
 * @param {string} goToURLOnChange
 * @param {number} unixTimestamp
 * @param {number} toUnixTimestamp
 * @param {string} selectedTz
 * @param {string|undefined} globalCallback
 * @returns {void}
 * @description
 * Called when there is the need to navigate to a given URL when the datetime is changed instead of updating the model value.
 */
export function goToURLWithData(updatedData, goToURLOnChange, unixTimestamp, toUnixTimestamp, selectedTz, globalCallback) {
    if (goToURLOnChange) {
        const url = new URL(goToURLOnChange, window.location.origin);
        if (updatedData) {
            url.searchParams.set("todate", unixTimestamp);
            url.searchParams.set("enddate", toUnixTimestamp);
        } else {
            url.searchParams.delete("todate");
            url.searchParams.delete("enddate");
        }
        window.location.href = url;
    } else if (globalCallback && window[globalCallback]) {
        window[globalCallback]({
            date: unixTimestamp,
            enddate: toUnixTimestamp,
            tzname: selectedTz,
            tzoffset: moment.tz(selectedTz.offset).utcOffset(),
        });
    }
}
