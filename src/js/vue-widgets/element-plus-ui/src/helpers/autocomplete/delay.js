/**
 * Delay before a live search, longer for short queries
 * @param {Number} length query length
 * @param {Number} minChars minimum characters before searching
 * @returns {Number|null} delay in ms, or null to skip the search
 */
export function getSearchDelay(length, minChars = 1) {
    if (length <= 0 || length < Math.max(1, minChars)) {
        return null;
    }
    if (length <= 2) {
        return 1500;
    }
    if (length <= 4) {
        return 1000;
    }
    if (length === 5) {
        return 750;
    }
    return 500;
}
