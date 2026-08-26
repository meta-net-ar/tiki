import { getCommentBody, tr } from "./helpers.js";

const POPUP_ID = "tiki-annotator-popup";

const POPUP_Z_INDEX = "1070";

function makeButton(label, className) {
    const button = document.createElement("button");

    button.type = "button";
    button.className = className;
    button.textContent = label;

    return button;
}

function positionPopup(popup, anchorRect) {
    popup.style.left = "0px";
    popup.style.top = "0px";

    const { width, height } = popup.getBoundingClientRect();
    const margin = 8;
    const gap = 8;

    let left;
    let top;

    // Position near selection if create, otherwise center-ish
    if (anchorRect) {
        left = anchorRect.left + anchorRect.width / 2 - width / 2;
        top = anchorRect.bottom + gap;

        if (top + height + margin > window.innerHeight && anchorRect.top - gap - height > margin) {
            top = anchorRect.top - gap - height;
        }
    } else {
        left = window.innerWidth / 2 - width / 2;
        top = window.innerHeight * 0.2;
    }

    left = Math.min(Math.max(left, margin), Math.max(margin, window.innerWidth - width - margin));
    top = Math.min(Math.max(top, margin), Math.max(margin, window.innerHeight - height - margin));

    popup.style.left = `${left}px`;
    popup.style.top = `${top}px`;
}

export function openAnnotationPopup({ annotation, showAuthor = false, mode = "create", canUpdate = true, canDelete = true, anchorRect = null }) {
    return new Promise((resolve) => {
        document.getElementById(POPUP_ID)?.remove();

        const readOnly = mode === "edit" && !canUpdate;

        const popup = document.createElement("div");

        popup.id = POPUP_ID;
        popup.className = "card shadow";
        popup.style.position = "fixed";
        popup.style.zIndex = POPUP_Z_INDEX;
        popup.style.minWidth = "18rem";
        popup.style.maxWidth = "min(24rem, 90vw)";

        const body = document.createElement("div");
        body.className = "card-body p-3";
        popup.appendChild(body);

        const heading = document.createElement("h6");
        heading.className = "card-title mb-2";

        if (mode === "create") {
            heading.textContent = tr("Add comment");
        } else {
            heading.textContent = readOnly ? tr("Comment") : tr("Edit comment");
        }

        body.appendChild(heading);

        if (showAuthor && (annotation?.realName || annotation?.commentDate)) {
            const meta = document.createElement("p");
            const parts = [];

            if (annotation.realName) parts.push(annotation.realName);
            if (annotation.commentDate) parts.push(annotation.commentDate);

            meta.className = "small text-body-secondary mb-2";
            meta.textContent = parts.join(" | ");
            body.appendChild(meta);
        }

        const existingComment = getCommentBody(annotation);

        let textarea = null;

        if (readOnly) {
            const text = document.createElement("p");

            text.className = "mb-0 text-break";
            text.style.whiteSpace = "pre-wrap";
            text.textContent = existingComment;
            body.appendChild(text);
        } else {
            textarea = document.createElement("textarea");

            textarea.className = "form-control";
            textarea.rows = 4;
            textarea.value = existingComment;
            body.appendChild(textarea);
        }

        const footer = document.createElement("div");
        footer.className = "d-flex justify-content-between align-items-center gap-2 mt-3";
        body.appendChild(footer);

        const leftActions = document.createElement("div");
        const rightActions = document.createElement("div");

        rightActions.className = "d-flex gap-2 ms-auto";
        footer.appendChild(leftActions);
        footer.appendChild(rightActions);

        function cleanup() {
            document.removeEventListener("mousedown", outsideClick);
            document.removeEventListener("keydown", onKeyDown);
            popup.remove();
        }

        function finish(action, comment) {
            cleanup();
            resolve(comment === undefined ? { action } : { action, comment });
        }

        function outsideClick(event) {
            if (!popup.contains(event.target)) {
                finish("cancel");
            }
        }

        function onKeyDown(event) {
            if (event.key === "Escape") {
                finish("cancel");
                return;
            }

            if (event.key === "Enter" && (event.ctrlKey || event.metaKey) && popup.contains(event.target)) {
                save();
            }
        }

        function save() {
            if (!textarea) {
                finish("cancel");
                return;
            }

            finish(mode === "create" ? "create" : "update", textarea.value.trim());
        }

        if (mode === "edit" && canDelete) {
            const deleteBtn = makeButton(tr("Delete"), "btn btn-sm btn-outline-danger");

            deleteBtn.onclick = () => finish("delete");
            leftActions.appendChild(deleteBtn);
        }

        const cancelBtn = makeButton(readOnly ? tr("Close") : tr("Cancel"), "btn btn-sm btn-secondary");

        cancelBtn.onclick = () => finish("cancel");
        rightActions.appendChild(cancelBtn);

        if (!readOnly) {
            const saveBtn = makeButton(mode === "create" ? tr("Create") : tr("Update"), "btn btn-sm btn-primary");

            saveBtn.onclick = save;
            rightActions.appendChild(saveBtn);
        }

        document.body.appendChild(popup);
        positionPopup(popup, anchorRect);

        if (textarea) {
            textarea.focus();
        }

        setTimeout(() => {
            document.addEventListener("mousedown", outsideClick);
            document.addEventListener("keydown", onKeyDown);
        }, 0);
    });
}
