import "./annotationTrigger.css";

const TRIGGER_ID = "tiki-annotator-trigger";

const ARROW_SIZE = 6;

const ARROW_GAP = 2;
const VIEWPORT_MARGIN = 4;

let activeTrigger = null;

export function hideAnnotationTrigger() {
    if (activeTrigger) {
        activeTrigger.cleanup();
        activeTrigger = null;
    }

    document.getElementById(TRIGGER_ID)?.remove();
}

export function showAnnotationTrigger({ getAnchorRect, label, onActivate, onDismiss }) {
    hideAnnotationTrigger();

    let anchorRect = getAnchorRect();

    if (!anchorRect) {
        onDismiss?.();
        return;
    }

    const button = document.createElement("button");

    button.id = TRIGGER_ID;
    button.type = "button";
    button.className = "tiki-annotator-trigger btn btn-primary btn-sm shadow-sm";
    button.title = label;
    button.setAttribute("aria-label", label);

    const icon = document.createElement("i");

    icon.className = "fas fa-pencil";
    icon.setAttribute("aria-hidden", "true");
    button.appendChild(icon);

    function place() {
        const next = getAnchorRect();

        if (!next) {
            dismiss();
            return;
        }

        anchorRect = next;

        const { width, height } = button.getBoundingClientRect();
        const offset = ARROW_SIZE + ARROW_GAP;

        let top = anchorRect.top - height - offset;
        const below = top < VIEWPORT_MARGIN;

        if (below) {
            top = anchorRect.bottom + offset;
        }

        top = Math.min(top, Math.max(VIEWPORT_MARGIN, window.innerHeight - height - offset - VIEWPORT_MARGIN));

        const centreX = anchorRect.left + anchorRect.width / 2;
        const left = Math.min(Math.max(centreX - width / 2, VIEWPORT_MARGIN), Math.max(VIEWPORT_MARGIN, window.innerWidth - width - VIEWPORT_MARGIN));

        button.classList.toggle("tiki-annotator-trigger--below", below);
        button.classList.toggle("tiki-annotator-trigger--above", !below);

        const caretMin = ARROW_SIZE + 2;
        const caretLeft = Math.min(Math.max(centreX - left, caretMin), Math.max(caretMin, width - caretMin));

        button.style.setProperty("--tiki-annotator-arrow-left", `${caretLeft}px`);
        button.style.left = `${left}px`;
        button.style.top = `${top}px`;
    }

    function cleanup() {
        document.removeEventListener("pointerdown", onPointerDown, true);
        document.removeEventListener("keydown", onKeyDown, true);
        window.removeEventListener("scroll", place, true);
        window.removeEventListener("resize", place);
        button.remove();
    }

    function dismiss() {
        if (activeTrigger?.button === button) {
            activeTrigger = null;
        }

        cleanup();
        onDismiss?.();
    }

    function activate() {
        if (activeTrigger?.button === button) {
            activeTrigger = null;
        }

        cleanup();
        onActivate?.(anchorRect);
    }

    function onPointerDown(event) {
        if (!button.contains(event.target)) {
            dismiss();
        }
    }

    function onKeyDown(event) {
        if (event.key === "Escape") {
            dismiss();
        }
    }

    button.addEventListener("mousedown", (event) => event.preventDefault());
    button.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        activate();
    });

    document.body.appendChild(button);
    place();

    activeTrigger = { button, cleanup };

    setTimeout(() => {
        document.addEventListener("pointerdown", onPointerDown, true);
        document.addEventListener("keydown", onKeyDown, true);
        window.addEventListener("scroll", place, true);
        window.addEventListener("resize", place);
    }, 0);
}
