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
    const tags = wrapperElement.querySelectorAll(".el-select__tags-text");

    tags.forEach((tag, index) => {
        const label = tag.textContent;
        const item = options.find((item) => item.label === label);
        const option = select.querySelector(`option[value="${item.value}"]`);
        select.options.add(option.cloneNode(true), index);
        option.remove();
    });
}
