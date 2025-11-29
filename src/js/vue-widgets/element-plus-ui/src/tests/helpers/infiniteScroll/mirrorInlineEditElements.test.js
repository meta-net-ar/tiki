import { beforeEach, describe, expect, test, vi } from "vitest";
import $ from "jquery";
import mirrorInlineEditElements from "../../../helpers/infiniteScroll/mirrorInlineEditElements";
import updateMirrorPosition from "../../../helpers/infiniteScroll/updateMirrorPosition";

vi.mock("../../../helpers/infiniteScroll/updateMirrorPosition", () => ({
    default: vi.fn(),
}));

class MockMutationObserver {
    constructor(callback) {
        this.callback = callback;
        MockMutationObserver.instances.push(this);
    }

    observe() {}

    disconnect() {}

    static triggerAll() {
        MockMutationObserver.instances.forEach((instance) => {
            instance.callback();
        });
    }
}

class MockResizeObserver extends MockMutationObserver {
    constructor(callback) {
        super(callback);
        MockResizeObserver.instances.push(this);
    }

    static triggerAll() {
        MockResizeObserver.instances.forEach((instance) => {
            instance.callback();
        });
    }
}

describe("mirrorInlineEditElements", () => {
    beforeAll(() => {
        window.$ = $;

        MockMutationObserver.instances = [];
        global.MutationObserver = MockMutationObserver;

        MockResizeObserver.instances = [];
        global.ResizeObserver = MockResizeObserver;
    });

    beforeEach(() => {
        MockMutationObserver.instances = [];
        MockResizeObserver.instances = [];
        vi.clearAllMocks();
    });

    test("copies all inline edit elements from the source container to the target container", () => {
        const givenSourceContainer = document.createElement("div");
        const givenTargetContainer = document.createElement("div");
        const givenInfiniteScrollContainerId = "infinite-scroll-container-1";
        givenSourceContainer.innerHTML = `
            <div class="editable-inline" id="inline-edit-1">Edit Me 1</div>
            <div class="editable-inline" id="inline-edit-2">Edit Me 2</div>
        `;

        mirrorInlineEditElements(givenSourceContainer, givenTargetContainer, givenInfiniteScrollContainerId);

        const mirroredElements = givenTargetContainer.querySelectorAll(".editable-inline");
        expect(mirroredElements.length).toBe(2);
        givenSourceContainer.querySelectorAll(".editable-inline").forEach((el, index) => {
            const mirroredEl = mirroredElements[index];
            expect(mirroredEl.getAttribute("container-id")).toBe(givenInfiniteScrollContainerId);
            expect(mirroredEl.getAttribute("infinite-scroll-child-id")).toBe(el.getAttribute("infinite-scroll-child-id"));
            expect(mirroredEl.style.position).toBe("absolute");
            expect(mirroredEl.style.background).toBe("transparent");
            expect(mirroredEl.style.width).toBe("10px");
            expect($(mirroredEl).height()).toBe($(el).outerHeight());
            expect($(mirroredEl).css("top")).toBe(`${$(el).offset().top + $(el).outerHeight() / 4 - 5}px`);
            expect($(mirroredEl).css("left")).toBe(`${$(el).offset().left + $(el).outerWidth() / 2}px`);
            expect(mirroredEl.innerHTML).toBe("");
        });
    });

    test("synchronizes click events from original inline edit elements to mirrored elements", () => {
        const givenSourceContainer = document.createElement("div");
        const givenTargetContainer = document.createElement("div");
        const givenInfiniteScrollContainerId = "infinite-scroll-container-1";
        givenSourceContainer.innerHTML = `
            <div class="editable-inline" id="inline-edit-1">Edit Me 1</div>
        `;

        mirrorInlineEditElements(givenSourceContainer, givenTargetContainer, givenInfiniteScrollContainerId);

        const originalElement = givenSourceContainer.querySelector(".editable-inline");
        const mirroredElement = givenTargetContainer.querySelector(".editable-inline");

        const mirroredClickHandler = vi.fn();
        mirroredElement.addEventListener("click", mirroredClickHandler);

        // Simulate click on original element again to see if it triggers mirrored click
        $(originalElement).trigger("click");

        expect(mirroredClickHandler).toHaveBeenCalled();
    });

    test("updates mirrored element position when DOM is mutated", () => {
        const givenSourceContainer = document.createElement("div");
        const givenTargetContainer = document.createElement("div");
        const givenInfiniteScrollContainerId = "infinite-scroll-container-1";
        givenSourceContainer.innerHTML = `
            <div class="editable-inline" id="inline-edit-1" style="width: 100px; height: 20px; top: 20px; position: absolute;">Edit Me 1</div>
        `;

        const mutationObserveSpy = vi.spyOn(MockMutationObserver.prototype, "observe");

        mirrorInlineEditElements(givenSourceContainer, givenTargetContainer, givenInfiniteScrollContainerId);

        expect(mutationObserveSpy).toHaveBeenCalledWith(document.body, {
            childList: true,
            subtree: true,
            attributes: false,
        });

        MockMutationObserver.triggerAll();

        expect(updateMirrorPosition).toHaveBeenCalledWith(
            givenTargetContainer.querySelector(".editable-inline"),
            givenSourceContainer.querySelector(".editable-inline")
        );
    });

    test("updates mirrored element position when container is resized", () => {
        const givenSourceContainer = document.createElement("div");
        const givenTargetContainer = document.createElement("div");
        const givenInfiniteScrollContainerId = "infinite-scroll-container-1";
        givenSourceContainer.innerHTML = `
            <div class="editable-inline" id="inline-edit-1" style="width: 100px; height: 20px; top: 20px; position: absolute;">Edit Me 1</div>
        `;

        const resizeObserveSpy = vi.spyOn(MockResizeObserver.prototype, "observe");

        mirrorInlineEditElements(givenSourceContainer, givenTargetContainer, givenInfiniteScrollContainerId);

        expect(resizeObserveSpy).toHaveBeenCalledWith(givenSourceContainer);

        MockResizeObserver.triggerAll();

        expect(updateMirrorPosition).toHaveBeenCalledWith(
            givenTargetContainer.querySelector(".editable-inline"),
            givenSourceContainer.querySelector(".editable-inline")
        );
    });
});
