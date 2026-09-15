/**
 * Fallback click delegation for tiki-encrypted-field → tiki-enter-key-modal.
 *
 * EncryptedField.vue handles the click directly when the Vue component is mounted.
 * This delegation covers any edge case where the Vue click handler is not available.
 *
 * EnterKeyModal.vue uses a MutationObserver to watch for `hidden` removal and
 * shows itself reactively — no custom events required.
 */

// View-mode: populate the value placeholder on tiki:unlocked.
// Edit-mode input + badge state are handled directly by EnterKeyModal.vue::applyUnlock
// before dispatching this event, so we only handle the view-mode span here.
function handleUnlocked(e) {
    const { fieldId, itemId = 0, value } = e.detail;

    // The span is keyed by field + item because the same field renders once per
    // item in list/plugin views. An empty string is a valid decrypted value, so
    // reveal the span whenever a value (including "") was returned — only skip a
    // null/undefined (failure) payload.
    const viewSpan = document.getElementById(`encrypted-view-${fieldId}-${itemId}`);
    if (viewSpan && value != null) {
        viewSpan.textContent = value;
        viewSpan.style.display = "";
    }
}

export default function handleEncryptedField() {
    document.addEventListener("click", (e) => {
        const link = e.target.closest("a.encryption-key-entry");
        if (!link) return;
        e.preventDefault();
        const fieldEl = link.closest("tiki-encrypted-field");
        if (!fieldEl) return;
        const fieldId = fieldEl.dataset.fieldId;
        if (!fieldId) return;
        const itemId = fieldEl.dataset.itemId ?? "0";
        // Remove `hidden` from this row's modal (matched by field + item so we
        // don't open the first row's modal) — MutationObserver in EnterKeyModal picks this up
        const modal = document.querySelector(`tiki-enter-key-modal[data-field-id="${fieldId}"][data-item-id="${itemId}"]`);
        if (modal) modal.removeAttribute("hidden");
    });

    // Only the host-scoped tiki:unlocked event carries decrypted cleartext:
    // EnterKeyModal dispatches it on each field's host element (non-bubbling), so no
    // page-level listener ever sees a value. It also sends a value-free notification
    // on document for field-type JS that just needs to know the field was unlocked.
    // Attach the view-span handler per host. Fields are server-rendered, so they all
    // exist by the time this module runs.
    document.querySelectorAll("tiki-encrypted-field").forEach((fieldEl) => {
        fieldEl.addEventListener("tiki:unlocked", handleUnlocked);
    });
}
