/**
 * Re-order the select options elements when the sort operation is triggered
 * @param {HTMLDivElement} wrapperElement div element that wraps the select component
 * @param {Array} options array of options set in the select component
 * @param {String} options[].label option label
 * @param {String} options[].value option value
 * @returns {void}
 */
export function sortOptions(wrapperElement, options) {
    const elementPlusId = wrapperElement.getRootNode().host.id;
    const select = document.querySelector(`select[element-plus-ref="${elementPlusId}"]`);
    if (!select) {
        return;
    }
    const tags = wrapperElement.querySelectorAll(".el-select__tags-text");

    tags.forEach((tag, index) => {
        const label = tag.textContent;
        const item = options.find((item) => item.label === label);
        const option = item ? select.querySelector(`option[value="${item.value}"]`) : null;
        if (!option) {
            return;
        }
        /*
            Move the existing option instead of inserting a copy: cloneNode() only copies HTML
            attributes, and el-select applies its value through the `selected` property, which a
            copy does not carry over. The copy would come back unselected and lose its value.
        */
        select.insertBefore(option, select.options[index] ?? null);
    });
}
