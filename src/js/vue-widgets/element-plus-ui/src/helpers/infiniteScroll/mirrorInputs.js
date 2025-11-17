/**
 *  Mirror out all inputs inside the container, so form state is preserved
 * @param {HTMLElement} sourceContainer
 * @param {import("vue").Ref} scrollbarRef
 * @param {string} infiniteScrollContainerId
 */
export default function (sourceContainer, scrollbarRef, infiniteScrollContainerId) {
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

        const uniqueId = `mirror-editable-inline-${Math.random().toString(36).substring(2, 15)}`;
        $(el).attr("infinite-scroll-child-id", uniqueId);

        $(mirror).attr("container-id", infiniteScrollContainerId);
        $(mirror).attr("infinite-scroll-child-id", uniqueId);

        mirror.innerHTML = "";

        $(mirror).css({
            position: "absolute",
            top: $(el).offset().top + $(el).outerHeight() / 4 - 5,
            left: $(el).offset().left + $(el).outerWidth() / 2,
            width: 10,
            height: $(el).outerHeight(),
            background: "transparent",
        });

        targetContainer.appendChild(mirror);

        $(el).on("click", () => {
            mirror.dispatchEvent(new Event("click", { bubbles: true }));
        });

        // When left and top position changes (e.g. window resize, elements addition/removal to DOM), update mirror position
        const updateMirrorPosition = () => {
            $(mirror).css({
                top: $(el).offset().top + $(el).outerHeight() / 4 - 5,
                left: $(el).offset().left + $(el).outerWidth() / 2,
                height: $(el).outerHeight(),
            });
        };
        new ResizeObserver(updateMirrorPosition).observe(sourceContainer);
        new MutationObserver(updateMirrorPosition).observe(document.body, { childList: true, subtree: true, attributes: false });
    });
}
