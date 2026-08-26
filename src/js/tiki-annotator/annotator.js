import { createTextAnnotator } from "@recogito/text-annotator";
import { openAnnotationPopup } from "./annotationPopup.js";
import {
    convertToLegacyPayload,
    getSelectionRects,
    isAllowed,
    notify,
    rectFromPoint,
    setCommentBody,
    tr,
    transformBackendResponse,
} from "./helpers.js";
import { hideAnnotationTrigger, showAnnotationTrigger } from "./annotationTrigger.js";
import { deleteAnnotationBackend, loadAnnotations, saveAnnotationBackend, setTicket, updateAnnotationBackend } from "./api.js";
import "@recogito/text-annotator/text-annotator.css";
import "./tikiOverrides.css";

const HIGHLIGHT_FILL = "#fd7e14";

const highlightStyle = (annotation, state) => ({
    fill: HIGHLIGHT_FILL,
    fillOpacity: state?.selected ? 0.4 : 0.21,
    underlineColor: HIGHLIGHT_FILL,
    underlineThickness: state?.selected || state?.hovered ? 2 : 1,
});

export async function initRecogito(config) {
    const objectUri = String(config.objectUri || "").trim();
    const showUserInfo = config.showUserInfo || false;

    if (!objectUri.includes(":")) {
        return null;
    }

    const container = document.getElementById(config.containerId);

    if (!container) {
        return null;
    }

    setTicket(config.ticket);

    const anno = createTextAnnotator(container, {
        annotatingEnabled: config.canPost,
        user: {
            id: config.userId,
            name: config.userName,
        },
        selectionMode: "all",
        style: highlightStyle,
    });

    if (!anno) {
        return null;
    }

    const threadIds = new Map();

    const rememberThreadId = (annotation, threadId) => {
        if (annotation?.id && threadId) {
            threadIds.set(annotation.id, threadId);
        }
    };

    const threadIdOf = (annotation) => (annotation?.id ? (threadIds.get(annotation.id) ?? null) : null);

    let suppressStoreEvents = false;

    const withSuppressedEvents = (fn) => {
        suppressStoreEvents = true;
        try {
            fn();
        } finally {
            suppressStoreEvents = false;
        }
    };

    const persistUpdate = async (annotation) => {
        const threadId = threadIdOf(annotation);

        if (!threadId) {
            notify(tr("This comment has not been saved yet."));
            return;
        }

        try {
            await updateAnnotationBackend(convertToLegacyPayload(annotation, objectUri, threadId));
        } catch (error) {
            notify(tr("Could not save the comment.") + " " + error.message);
        }
    };

    const persistDelete = async (annotation) => {
        const threadId = threadIdOf(annotation);

        if (!threadId) {
            return false;
        }

        try {
            await deleteAnnotationBackend(threadId);
            threadIds.delete(annotation.id);
            return true;
        } catch (error) {
            notify(tr("Could not delete the comment.") + " " + error.message);
            return false;
        }
    };

    /**
     * Load existing annotations
     */
    try {
        const annotationsList = await loadAnnotations(objectUri, transformBackendResponse);

        annotationsList.forEach((annotation) => rememberThreadId(annotation, annotation.threadId));
        anno.setAnnotations(annotationsList);
    } catch (error) {
        notify(tr("Could not load the inline comments.") + " " + error.message);
    }

    let lastPointer = null;

    container.addEventListener(
        "pointerdown",
        (event) => {
            lastPointer = { x: event.clientX, y: event.clientY };
        },
        true
    );

    let pendingAnnotation = null;

    const discardPending = () => {
        if (!pendingAnnotation) return;

        const annotation = pendingAnnotation;
        pendingAnnotation = null;

        withSuppressedEvents(() => anno.removeAnnotation(annotation));
    };

    const createWithPopup = async (annotation, anchorRect) => {
        try {
            const result = await openAnnotationPopup({
                annotation,
                mode: "create",
                showAuthor: false,
                anchorRect,
            });

            if (result.action !== "create") {
                withSuppressedEvents(() => anno.removeAnnotation(annotation));
                return;
            }

            setCommentBody(annotation, result.comment || "");

            const saved = await saveAnnotationBackend(convertToLegacyPayload(annotation, objectUri));

            rememberThreadId(annotation, saved?.id ?? saved?.threadId);
            withSuppressedEvents(() => anno.updateAnnotation(annotation));
        } catch (error) {
            withSuppressedEvents(() => anno.removeAnnotation(annotation));
            notify(tr("Could not save the comment.") + " " + error.message);
        } finally {
            anno.cancelSelected();
        }
    };

    /**
     * Create annotation
     */
    anno.on("createAnnotation", (annotation) => {
        if (suppressStoreEvents) return;

        hideAnnotationTrigger();
        discardPending();

        pendingAnnotation = annotation;

        showAnnotationTrigger({
            getAnchorRect: () => getSelectionRects()?.bounds ?? null,
            label: tr("Add comment"),
            onActivate: (anchorRect) => {
                pendingAnnotation = null;
                createWithPopup(annotation, anchorRect);
            },
            onDismiss: () => {
                discardPending();
                anno.cancelSelected();
            },
        });
    });

    /**
     * Click an annotation
     */
    anno.on("clickAnnotation", async (clicked) => {
        hideAnnotationTrigger();

        const annotation = Array.isArray(clicked) ? clicked[0] : clicked;

        if (!annotation) return;

        const permissions = annotation.permissions;

        const res = await openAnnotationPopup({
            annotation,
            mode: "edit",
            showAuthor: showUserInfo,
            canUpdate: isAllowed(permissions, "update"),
            canDelete: isAllowed(permissions, "delete"),
            anchorRect: lastPointer ? rectFromPoint(lastPointer.x, lastPointer.y) : null,
        });

        if (res.action === "delete") {
            if (await persistDelete(annotation)) {
                withSuppressedEvents(() => anno.removeAnnotation(annotation));
            }
            anno.cancelSelected();
            return;
        }

        if (res.action === "update") {
            setCommentBody(annotation, res.comment);
            withSuppressedEvents(() => anno.updateAnnotation(annotation));
            await persistUpdate(annotation);
        }

        anno.cancelSelected();
    });

    /**
     * Update annotation
     */
    anno.on("updateAnnotation", async (annotation) => {
        if (suppressStoreEvents) return;

        await persistUpdate(annotation);
    });

    /**
     * Delete annotation
     */
    anno.on("deleteAnnotation", async (annotation) => {
        if (suppressStoreEvents) return;

        await persistDelete(annotation);
    });

    return anno;
}
