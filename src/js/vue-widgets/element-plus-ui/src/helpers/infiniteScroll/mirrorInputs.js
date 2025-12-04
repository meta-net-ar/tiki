import mirrorInlineEditElements from "./mirrorInlineEditElements";

/**
 *  Mirror out all inputs inside the container, so form state is preserved
 * @param {HTMLElement} sourceContainer
 * @param {import("vue").Ref} scrollbarRef
 * @param {string} infiniteScrollContainerId
 */
export default function (sourceContainer, scrollbarRef, infiniteScrollContainerId) {
    const inputs = sourceContainer.querySelectorAll("input, select, textarea");
    const targetContainer = scrollbarRef.value.wrapRef.getRootNode().host.parentElement;

    inputs.forEach((input) => {
        const mirror = input.cloneNode(true);
        mirror.style.display = "none";
        targetContainer.appendChild(mirror);

        $(input).on("change", () => {
            if (mirror.type === "checkbox" || mirror.type === "radio") {
                mirror.checked = input.checked;
            } else {
                mirror.value = input.value;
            }
            mirror.dispatchEvent(new Event("change"));
        });

        if (input.type === "submit" || input.type === "button") {
            input.addEventListener("click", () => {
                mirror.click();
            });
        }

        // sync back independent mirror changes (usually triggered programmatically) to the original input
        $(mirror).on("change", () => {
            if (input.type === "checkbox" || input.type === "radio") {
                input.checked = mirror.checked;
            } else {
                input.value = mirror.value;
            }
        });
    });

    mirrorInlineEditElements(sourceContainer, targetContainer, infiniteScrollContainerId);
}
