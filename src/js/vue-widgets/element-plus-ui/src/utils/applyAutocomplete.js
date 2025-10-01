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
        element.value = event.detail[0].value;
        element.dispatchEvent(new Event("change"));
        if (selectCb) {
            selectCb(event);
        }
    });

    elementPlusUi.addEventListener("pressEnter", () => {
        const form = element.closest("form");
        if (!form) return;

        let defaultSubmitButton = form.querySelector('button[type="submit"]:not([disabled]), input[type="submit"]:not([disabled])');

        if (defaultSubmitButton) {
            defaultSubmitButton.click();
            return;
        } else {
            // If no button exists, count how many inputs types that can block implicit submission
            const blockingInputCount = form.querySelectorAll(
                'input[type="text"], input[type="search"], input[type="url"], input[type="tel"], input[type="email"], input[type="password"], input[type="date"], input[type="month"], input[type="week"], input[type="time"], input[type="datetime-local"], input[type="number"]'
            ).length;

            if (blockingInputCount <= 1) {
                // If there's only one or fewer, we can safely submit the form directly.
                form.submit();
            }
        }
    });

    element.setAttribute("element-plus-ref", elementUniqueId);
    element.style.display = "none";
    element.parentNode.insertBefore(elementPlusUi, element.nextSibling);

    return elementPlusUi;
}
