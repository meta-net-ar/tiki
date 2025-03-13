/**
 * Fetch suggestions that match the given query from a given source of items
 * @param {String} query search term
 * @param {Function} callback function to call with the results
 * @param {String|null} sourceRemoteUrl remote URL to fetch suggestions from
 * @param {Array} sourceList suggestions to filter from instead of fetching from the remote URL
 */
export function fetchSuggestions(query, callback, sourceRemoteUrl = null, sourceList = []) {
    if (sourceList.length) {
        const filteredList = sourceList.filter((item) => item.value.toLowerCase().includes(query.toLowerCase()));
        return callback(filteredList);
    }

    if (!sourceRemoteUrl) {
        console.error("Either sourceRemoteUrl or sourceList must be provided to fetch suggestions");
        return;
    }

    const url = new URL(sourceRemoteUrl);
    url.searchParams.append("q", query);
    fetch(url.href, {
        headers: {
            Accept: "application/json",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data?.length) {
                if (typeof data[0] === "string") {
                    data = data.map((item) => ({ value: item }));
                }
            }
            callback(data);
        });
}
