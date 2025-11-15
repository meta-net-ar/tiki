/**
 *  Mirror out all inputs inside the container, so form state is preserved
 * @param {HTMLElement} sourceContainer
 * @param {import("vue").Ref} scrollbarRef
 */
export default function (sourceContainer, scrollbarRef) {
    const inputs = sourceContainer.querySelectorAll("input, select, textarea");
    const trackerInlineEditElements = sourceContainer.querySelectorAll(".editable-inline");
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

    trackerInlineEditElements.forEach((el) => {
        const mirror = el.cloneNode(true);
        mirror.innerHTML = "";
        // mirror is positioned at the same place as the original element
        $(mirror).css({
            position: "absolute",
            top: $(el).offset().top,
            left: $(el).offset().left,
            "min-width": $(el).outerWidth(),
            "min-height": $(el).outerHeight(),
            display: "block",
            "z-index": 1,
        });
        targetContainer.appendChild(mirror);

        $(el).on("click", () => {
            mirror.dispatchEvent(new Event("click", { bubbles: true }));
        });
    });
}
