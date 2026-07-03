<script setup lang="ts">
import { computed } from "vue";

const tr = window.tr || ((s: string) => s);

const props = defineProps<{
    locked?: boolean | string;
    forbidden?: boolean | string;
    keyName?: string;
}>();

// Light-DOM custom element attributes arrive as strings. Vue casts the
// presence-only form (locked="") to true, but explicit string values like
// locked="false" or locked="0" would otherwise be truthy — coerce them.
function coerceFlag(value: boolean | string | undefined): boolean {
    if (typeof value === "string") {
        return !["false", "0"].includes(value.toLowerCase());
    }
    return !!value;
}

const isForbidden = computed(() => coerceFlag(props.forbidden));
const isLocked = computed(() => coerceFlag(props.locked));

function handleUnlockClick(e: MouseEvent) {
    e.preventDefault();
    const link = e.currentTarget as HTMLElement;
    const host = link.closest("tiki-encrypted-field") as HTMLElement | null;
    const fieldId = host?.dataset?.fieldId;
    if (!fieldId) return;
    const itemId = host?.dataset?.itemId ?? "0";

    // Directly remove `hidden` from this row's modal — simplest possible trigger.
    // The same field renders once per item, so match on both ids to reach the
    // modal for this row rather than the first one on the page. EnterKeyModal
    // watches for this via MutationObserver and shows itself.
    const modal = document.querySelector(
        `tiki-enter-key-modal[data-field-id="${fieldId}"][data-item-id="${itemId}"]`
    ) as HTMLElement | null;
    if (modal) modal.removeAttribute("hidden");
}
</script>

<template>
    <template v-if="isForbidden">
        <span class="d-inline-flex align-items-center gap-1 mb-1">
            <i class="fas fa-lock text-secondary" :aria-label="tr('Encrypted – no access')" />
            <span class="badge text-bg-danger">{{ tr("No Access") }}</span>
        </span>
        <div class="description form-text text-danger">{{ tr("Field is encrypted with a key that no longer exists. Contact your administrator.") }}</div>
    </template>

    <template v-else-if="isLocked">
        <span class="d-inline-flex align-items-center gap-1 mb-1">
            <i class="fas fa-lock text-warning-emphasis" :aria-label="tr('Encrypted – locked')" />
            <span class="badge text-bg-warning">{{ tr("Encrypted") }}</span>
        </span>
        <div class="description form-text">
            {{ tr('Field “%0” is encrypted. Leave empty or enter the key first to fill it.').replace('%0', keyName ?? tr('encrypted')) }}
            <a href="#" class="encryption-key-entry" @click="handleUnlockClick">{{ tr("Try with a manually entered key.") }}</a>
        </div>
    </template>

    <template v-else>
        <span class="d-inline-flex align-items-center gap-1 mb-1">
            <i class="fas fa-unlock text-success-emphasis" :aria-label="tr('Encrypted – unlocked')" />
            <span class="badge text-bg-success">{{ tr("Unlocked") }}</span>
        </span>
        <div class="description form-text">{{ tr('Field data is encrypted using key “%0”.').replace('%0', keyName ?? tr('encrypted')) }}</div>
    </template>
</template>
