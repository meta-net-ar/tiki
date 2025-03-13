export const TEXT = {
    ERROR_NO_ELEMENT: "The element must be provided to apply the autocompletion",
    ERROR_NO_SOURCE: "Either remoteSourceUrl or sourceList must be provided to apply the autocompletion",
};

/**
 * Apply autocompletion to an input element
 * @param {HTMLInputElement} element input element for which to apply the autocompletion
 * @param {String} remoteSourceUrl URL to fetch suggestions from
 * @param {Array} sourceList List of suggestions
 * @param {String} valueKey Key to use as the value when a suggestion is an object
 * @param {Function} selectCb Callback function to execute when a suggestion is selected
 *
 * @returns {HTMLElement} elementPlusUi element that was created to handle the autocompletion
 */
export default function applyAutocomplete(element, remoteSourceUrl = null, sourceList = [], valueKey = null, selectCb = null) {
    if (!element) {
        console.error(TEXT.ERROR_NO_ELEMENT);
        return;
    }

    if (!remoteSourceUrl && !sourceList.length) {
        console.error(TEXT.ERROR_NO_SOURCE);
        return;
    }

    const elementUniqueId = Math.random().toString(36).substring(7);
    const elementPlusUi = document.createElement("el-autocomplete");
    elementPlusUi.setAttribute("id", elementUniqueId);
    elementPlusUi.setAttribute("remote-source-url", remoteSourceUrl);
    elementPlusUi.setAttribute("source-list", JSON.stringify(sourceList));
    elementPlusUi.setAttribute("value", element.value);

    if (valueKey) {
        elementPlusUi.setAttribute("value-key", valueKey);
    }

    if (element.getAttribute("placeholder")) {
        elementPlusUi.setAttribute("placeholder", element.getAttribute("placeholder"));
    }

    elementPlusUi.addEventListener("input", (event) => {
        if (event.detail) {
            element.value = event.detail[0];
            element.dispatchEvent(new Event("change"));
        }
    });

    if (selectCb) {
        elementPlusUi.addEventListener("select", selectCb);
    }

    element.setAttribute("element-plus-ref", elementUniqueId);
    element.style.display = "none";
    element.parentNode.insertBefore(elementPlusUi, element.nextSibling);

    return elementPlusUi;
}
