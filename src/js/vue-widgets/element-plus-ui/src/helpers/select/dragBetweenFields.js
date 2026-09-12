/*
    The tags rendered by el-select carry their label, never the value behind it, and two selects
    can label the same value differently. What is being dragged is kept here instead.
*/
let draggedItem = null;

let refusal = null;

const dragListeners = new Set();

function tellEveryone() {
    dragListeners.forEach((listener) => listener());
}

/**
 * Remember what is being dragged, so the field it is dropped on can identify it
 * @param {Object} item item being dragged
 * @param {String} item.value value of the dragged item
 * @returns {void}
 */
export function startDrag(item) {
    draggedItem = item && item.value !== undefined ? { value: item.value } : null;
    refusal = null;
    // Between two fields the pointer crosses the page, which would go back to an arrow on the way
    document.body.style.cursor = "grabbing";
    tellEveryone();
}

/**
 * Forget the dragged item, whether it was dropped or not, and tell every field about it
 * @returns {void}
 */
export function endDrag() {
    draggedItem = null;
    refusal = null;
    document.body.style.cursor = "";
    tellEveryone();
}

export function getDraggedItem() {
    return draggedItem;
}

export function isDragging() {
    return draggedItem !== null;
}

/**
 * Ask to be told whenever a drag begins, ends, or is turned down by a field
 * @param {Function} listener called with no argument, and reads back what it needs
 * @returns {Function} call it to stop being told
 */
export function onDragStateChange(listener) {
    dragListeners.add(listener);
    return () => dragListeners.delete(listener);
}

/**
 * Say why a field turns a drop down, or that it no longer does. Only the last word counts,
 * whoever had it: a field taking the value in speaks for every other.
 * @param {Object} field the field having its say
 * @param {String} reason empty when the value is welcome
 * @returns {void}
 */
export function setRefusal(field, reason) {
    refusal = reason ? { field, reason } : null;
    tellEveryone();
}

export function refusalFor(field) {
    return refusal && refusal.field === field ? refusal.reason : "";
}

export function isRefusing() {
    return refusal !== null;
}

/**
 * Whether the user asked for a copy rather than a move, holding the modifier their system copies
 * with while dragging: Ctrl on Windows and Linux, Option on macOS
 * @param {Event} originalEvent the DOM event SortableJS is reacting to
 * @returns {Boolean}
 */
export function isCopyRequested(originalEvent) {
    return Boolean(originalEvent && (originalEvent.ctrlKey || originalEvent.altKey));
}
