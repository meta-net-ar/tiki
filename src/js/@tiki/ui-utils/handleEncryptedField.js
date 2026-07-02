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
    const { fieldId, value } = e.detail;

    const viewSpan = document.getElementById(`encrypted-view-${fieldId}`);
    if (viewSpan && value) {
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
        // Remove `hidden` from the modal — MutationObserver in EnterKeyModal picks this up
        const modal = document.querySelector(`tiki-enter-key-modal[data-field-id="${fieldId}"]`);
        if (modal) modal.removeAttribute("hidden");
    });

    // The tiki:unlocked event carries decrypted cleartext, so EnterKeyModal dispatches
    // it on each field's host element (non-bubbling) instead of document — page-level
    // listeners never see it. Attach the view-span handler per host. Fields are
    // server-rendered, so they all exist by the time this module runs.
    document.querySelectorAll("tiki-encrypted-field").forEach((fieldEl) => {
        fieldEl.addEventListener("tiki:unlocked", handleUnlocked);
    });
}
