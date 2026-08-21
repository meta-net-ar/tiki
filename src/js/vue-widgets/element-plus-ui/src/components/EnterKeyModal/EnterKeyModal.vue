<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";

const tr = window.tr || ((s: string) => s);

const props = defineProps<{
    keyName: string;
    encryptionKeyId: number | string;
    fieldId: number | string;
    itemId?: number | string;
}>();

const emit = defineEmits<{
    unlocked: [fieldId: number | string, value: string];
    close: [];
}>();

const sharedKey = ref("");
const state = ref<"idle" | "loading" | "error">("idle");
const errorMsg = ref("");
const autoFilled = ref(false);

// shown drives v-if — controls whether backdrop+modal exist in DOM
const shown = ref(false);

// A field is only unique when paired with its item: the same field renders once
// per item in list/plugin views, so every DOM lookup, id and event is keyed by
// both fieldId and itemId to avoid resolving to the first matching row.
const fieldIdAttr = String(props.fieldId);
const itemIdAttr = String(props.itemId ?? 0);
const modalDialogId = `tiki-cem-dialog-${fieldIdAttr}-${itemIdAttr}`;
const sharedKeyInputId = `tiki-cem-shared-key-${fieldIdAttr}-${itemIdAttr}`;

// Find our host element by data-field-id + data-item-id (set directly by PHP)
function getHost(): HTMLElement | null {
    return document.querySelector(`tiki-enter-key-modal[data-field-id="${fieldIdAttr}"][data-item-id="${itemIdAttr}"]`);
}

// When shown changes, sync the host's `hidden` attribute so position:fixed
// children are visible (display:none on host hides all children regardless of position)
watch(shown, (isShown) => {
    const host = getHost();
    if (!host) return;
    if (isShown) {
        host.removeAttribute("hidden");
    } else {
        host.setAttribute("hidden", "");
    }
});

// MutationObserver watches for external removal of `hidden` (e.g. from EncryptedField.vue
// click handler or handleEncryptedField.js). No custom events needed — just watch the DOM.
let observer: MutationObserver | null = null;

onMounted(() => {
    const host = getHost();
    if (!host) return;

    observer = new MutationObserver(() => {
        const isHidden = host.hasAttribute("hidden");
        if (!isHidden && !shown.value) {
            // hidden was removed externally — show the modal
            shown.value = true;
            sharedKey.value = "";
            state.value = "idle";
            errorMsg.value = "";
            autoFilled.value = false;
            // Focus the password input after the modal renders
            nextTick(() => {
                const input = document.getElementById(sharedKeyInputId) as HTMLInputElement | null;
                input?.focus();
            });
        }
    });
    observer.observe(host, { attributes: true, attributeFilter: ["hidden"] });
});

onUnmounted(() => {
    observer?.disconnect();
    observer = null;
});

// Fetches the user's stored account share and fills the input.
// Gated behind an explicit button click so the secret share never
// enters the DOM without the user asking for it.
async function loadStoredShare() {
    state.value = "loading";
    errorMsg.value = "";
    try {
        const res = await fetch(`tiki-ajax_services.php?controller=encryption&action=get_share_for_key&keyId=${props.encryptionKeyId}`);
        const data = res.ok ? await res.json() : null;
        if (data && typeof data === "string") {
            sharedKey.value = data;
            autoFilled.value = true;
            state.value = "idle";
        } else {
            state.value = "error";
            errorMsg.value = tr("No stored share found for your account.");
        }
    } catch {
        state.value = "error";
        errorMsg.value = tr("A network error occurred. Please try again.");
    }
}

function applyUnlock(value: string) {
    // Enable the form input and populate if we have a decrypted value. Edit mode
    // renders one form per item, so the fieldId-only wrapper id is unambiguous there.
    const inputWrapper = document.getElementById(`trackerinput_${props.fieldId}`);
    if (inputWrapper) {
        const input = inputWrapper.querySelector("input, textarea, select") as HTMLInputElement | null;
        if (input) {
            input.disabled = false;
            if (value) input.value = value;
        }
    }

    // Switch this row's encrypted-field indicator to unlocked state
    const fieldEl = document.querySelector(`tiki-encrypted-field[data-field-id="${fieldIdAttr}"][data-item-id="${itemIdAttr}"]`);
    if (fieldEl) fieldEl.removeAttribute("locked");

    // Hide via Vue reactive state — watch() will add `hidden` back to host
    shown.value = false;

    emit("unlocked", props.fieldId, value);
    // Dispatch on the field's host element (not document, no bubbling) so the
    // decrypted cleartext is only observable by listeners scoped to that field.
    fieldEl?.dispatchEvent(new CustomEvent("tiki:unlocked", { detail: { fieldId: props.fieldId, itemId: props.itemId ?? 0, value } }));
    // Field-type JS that only needs to know the field was unlocked (the Secret
    // field re-enables its show/hide toggle) listens on document. Notify without
    // the cleartext, so page-level listeners never see a decrypted value.
    document.dispatchEvent(new CustomEvent("tiki:unlocked", { detail: { fieldId: props.fieldId, itemId: props.itemId ?? 0 } }));
}

async function submit() {
    if (!sharedKey.value) return;
    state.value = "loading";
    errorMsg.value = "";

    try {
        const enterRes = await fetch(`tiki-ajax_services.php?controller=encryption&action=enter_key`, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                keyId: String(props.encryptionKeyId),
                shared_key: sharedKey.value,
            }),
        });

        if (!enterRes.ok) {
            let msg = tr("The entered key is incorrect for this encryption key.");
            try {
                const errData = await enterRes.json();
                if (errData?.errors?.[0]?.message) msg = errData.errors[0].message;
            } catch {
                // use default message
            }
            state.value = "error";
            errorMsg.value = msg;
            return;
        }

        const itemIdNum = Number(props.itemId ?? 0);
        if (itemIdNum > 0) {
            const valRes = await fetch(`tiki-ajax_services.php?controller=encryption&action=get_decrypted_value`, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    keyId: String(props.encryptionKeyId),
                    fieldId: String(props.fieldId),
                    itemId: String(props.itemId),
                }),
            });
            const valData = await valRes.json();
            // An empty string is a valid decrypted value — the server only signals
            // failure with a null value (and an accompanying error message). Testing
            // for falsy here would reject a legitimately-empty field.
            if (valData?.value == null) {
                state.value = "error";
                errorMsg.value = valData?.error ?? tr("Decryption failed.");
                return;
            }
            state.value = "idle";
            sharedKey.value = "";
            applyUnlock(valData.value);
        } else {
            state.value = "idle";
            sharedKey.value = "";
            applyUnlock("");
        }
    } catch {
        state.value = "error";
        errorMsg.value = tr("A network error occurred. Please try again.");
    }
}

// Trap focus within the modal: Tab/Shift+Tab cycle through focusable elements;
// Escape closes the dialog.
function handleKeydown(e: KeyboardEvent) {
    if (e.key === "Escape") {
        close();
        return;
    }
    if (e.key !== "Tab") return;

    const dialog = document.getElementById(modalDialogId);
    if (!dialog) return;
    const focusable = Array.from(
        dialog.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')
    ).filter((el) => el.offsetParent !== null);
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey) {
        if (document.activeElement === first) {
            e.preventDefault();
            last.focus();
        }
    } else {
        if (document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    }
}

function close() {
    state.value = "idle";
    errorMsg.value = "";
    sharedKey.value = "";
    autoFilled.value = false;
    shown.value = false;
    emit("close");
}
</script>

<template>
    <template v-if="shown">
        <div class="enter-key-backdrop" @click.self="state !== 'loading' && close()" />
        <div
            :id="modalDialogId"
            class="modal d-block enter-key-modal"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="`${modalDialogId}-title`"
            tabindex="-1"
            @keydown="handleKeydown"
        >
            <div class="modal-dialog modal-dialog-centered" style="max-width: 500px">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 :id="`${modalDialogId}-title`" class="modal-title">{{ tr("Enter key") }}</h5>
                        <button type="button" class="btn-close" :disabled="state === 'loading'" @click="close" :aria-label="tr('Close')" />
                    </div>

                    <div class="modal-body">
                        <div v-if="state === 'error'" class="alert alert-danger alert-dismissible mb-3">
                            <ul class="mb-0 ps-3">
                                <li>{{ errorMsg }}</li>
                            </ul>
                            <button
                                type="button"
                                class="btn-close"
                                @click="
                                    errorMsg = '';
                                    state = 'idle';
                                "
                                :aria-label="tr('Close')"
                            />
                        </div>

                        <div class="mb-3">
                            <label :for="sharedKeyInputId" class="form-label"> {{ tr('Enter shared secret for key “%0”').replace('%0', keyName) }} </label>
                            <input
                                :id="sharedKeyInputId"
                                v-model="sharedKey"
                                type="password"
                                name="shared_key"
                                class="form-control"
                                :disabled="state === 'loading'"
                                @keyup.enter="submit"
                            />
                            <div v-if="autoFilled" class="form-text text-success mt-1">
                                <i class="fas fa-circle-check me-1" />{{ tr("Share retrieved from your account. Click Submit to unlock.") }}
                            </div>
                            <template v-else>
                                <div class="form-text text-muted mt-1">
                                    {{ tr("If you have a shared secret key not saved into your account, you can paste it here to encrypt or decrypt data with it.") }}
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary mt-2"
                                    :disabled="state === 'loading'"
                                    @click="loadStoredShare"
                                >
                                    <i class="fas fa-user-lock me-1" />{{ tr("Use my stored share") }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" :disabled="state === 'loading'" @click="close">{{ tr("Close") }}</button>
                        <button type="button" class="btn btn-primary" :disabled="state === 'loading' || !sharedKey" @click="submit">
                            <span v-if="state === 'loading'" class="spinner-border spinner-border-sm me-1" />
                            {{ tr("Submit") }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</template>

<style scoped>
.enter-key-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.enter-key-modal {
    position: fixed;
    inset: 0;
    z-index: 1050;
}
</style>
