import enterKeypressHandler from "../helpers/shared/enterKeypressHandler";

export const TEXT = {
    ERROR_NO_ELEMENT: "The element must be provided to apply the autocompletion",
    ERROR_NO_SOURCE: "Either remoteSourceUrl or sourceList must be provided to apply the autocompletion",
};

/**
 * Apply autocompletion to an input element
 * @param {HTMLInputElement} element input element for which to apply the autocompletion
 * @param {Object} options
 * @param {String} options.remoteSourceUrl remote URL to fetch suggestions from
 * @param {String} options.remoteQueryKey query parameter key to use when fetching suggestions from the remote URL, defaults to 'q'
 * @param {Array} options.sourceList suggestions to filter from instead of fetching from the remote URL
 * @param {String} options.valueKey key to use as the value from the suggestions objects (only if suggestions are objects, and necessary only if 'transformResult' is not provided)
 * @param {Function} options.selectCb callback function to execute when an autocomplete suggestion is selected, receives the select event as a parameter
 * @param {Function} options.transformResultCb callback function to execute on the results returned from the remote URL before they are passed down to the component, receives the results as a parameter and must return the transformed results.
 *
 * @returns {HTMLElement} elementPlusUi element that was created to handle the autocompletion
 */

export default function applyAutocomplete(element, options) {
    if (!element) {
        console.error(TEXT.ERROR_NO_ELEMENT); // eslint-disable-line no-console
        return;
    }

    const { remoteSourceUrl, sourceList = [], valueKey, selectCb, transformResultCb, remoteQueryKey } = options;

    if (!remoteSourceUrl && !sourceList.length) {
        console.error(TEXT.ERROR_NO_SOURCE); // eslint-disable-line no-console
        return;
    }

    const elementPlusInput = document.querySelector(`el-input#${element.getAttribute("element-plus-ref")}`);
    if (elementPlusInput) {
        elementPlusInput.remove();
    }

    const uiRef = element.getAttribute("element-plus-ref");
    if (uiRef && document.querySelector(`el-autocomplete#${uiRef}`)) {
        return document.querySelector(`el-autocomplete#${uiRef}`);
    }

    const elementUniqueId = Math.random().toString(36).substring(7);
    const elementPlusUi = document.createElement("el-autocomplete");
    elementPlusUi.setAttribute("id", elementUniqueId);
    elementPlusUi.setAttribute("remote-source-url", remoteSourceUrl);
    elementPlusUi.setAttribute("source-list", JSON.stringify(sourceList));
    elementPlusUi.setAttribute("value", element.value);

    if (remoteQueryKey) {
        elementPlusUi.setAttribute("remote-query-key", remoteQueryKey);
    }

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

    elementPlusUi.addEventListener("select", (event) => {
        const key = valueKey || "value";
        element.value = event.detail[0][key];
        element.dispatchEvent(new Event("change"));
        if (selectCb) {
            selectCb(event);
        }
    });

    if (transformResultCb) {
        const transformResultFnName = `transformResult_${elementUniqueId}`;
        window[transformResultFnName] = transformResultCb;
        elementPlusUi.setAttribute("transform-result-fn", transformResultFnName);
    }

    elementPlusUi.addEventListener("pressEnter", () => enterKeypressHandler($(element), $(elementPlusUi)));

    element.setAttribute("element-plus-ref", elementUniqueId);
    element.style.display = "none";
    element.parentNode.insertBefore(elementPlusUi, element.nextSibling);
    const syncUiValue = () => {
        const newValue = element.value;
        if (typeof elementPlusUi.setValue === "function") {
            elementPlusUi.setValue(newValue);
            return;
        }
        elementPlusUi.value = newValue;
    };

    element.addEventListener("change", syncUiValue);

    return elementPlusUi;
}
