import updateMirrorPosition from "./updateMirrorPosition";

/**
 * Mirror out all tracker inline edit elements inside the container, to allow external handlers execution
 * @param {HTMLElement} sourceContainer
 * @param {HTMLElement} targetContainer
 * @param {String} infiniteScrollContainerId
 */
export default function (sourceContainer, targetContainer, infiniteScrollContainerId) {
    const trackerInlineEditElements = sourceContainer.querySelectorAll(".editable-inline");

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
        new ResizeObserver(updateMirrorPosition.bind(null, mirror, el)).observe(sourceContainer);
        new MutationObserver(updateMirrorPosition.bind(null, mirror, el)).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: false,
        });
    });
}
