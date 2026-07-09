<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from "vue";
import { useClipboard } from "../composables/useClipboard";
import { getCsrfTicket } from "../csrf";

const tr = window.tr || ((s: string) => s);

interface KeyEntry {
    keyId: number;
    name: string;
    description?: string;
    shares?: number;
    users?: string;
    atRisk?: boolean;
}

const emit = defineEmits<{
    create: [];
    edit: [keyId: number, keyName: string];
    shares: [keyId: number, keyName: string];
}>();

const keys = ref<KeyEntry[]>([]);
const loading = ref(true);
const error = ref("");
const deletingId = ref<number | null>(null);

const myShareModal = ref<{
    shown: boolean;
    keyId: number | null;
    keyName: string;
    share: string;
    loading: boolean;
    error: string;
}>({ shown: false, keyId: null, keyName: "", share: "", loading: false, error: "" });

const myShareModalDialogId = "tiki-ksm-my-share-dialog";

// Trap focus within the share modal: Tab/Shift+Tab cycle through focusable
// elements; Escape closes the dialog. Mirrors EnterKeyModal.vue.
function handleMyShareModalKeydown(e: KeyboardEvent) {
    if (e.key === "Escape") {
        closeMyShareModal();
        return;
    }
    if (e.key !== "Tab") return;

    const dialog = document.getElementById(myShareModalDialogId);
    if (!dialog) return;
    const focusable = Array.from(
        dialog.querySelectorAll<HTMLElement>('button:not([disabled]), input:not([disabled]), a[href], [tabindex]:not([tabindex="-1"])')
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

async function getMyShare(keyId: number, keyName: string) {
    myShareModal.value = { shown: true, keyId, keyName, share: "", loading: true, error: "" };
    nextTick(() => document.getElementById(myShareModalDialogId)?.focus());
    try {
        const res = await fetch(`tiki-ajax_services.php?controller=encryption&action=get_share_for_key&keyId=${keyId}`);
        if (!res.ok) throw new Error();
        const data = await res.json();
        if (data && typeof data === "string") {
            myShareModal.value.share = data;
        } else {
            myShareModal.value.error = tr(
                "No share found for your account. You may not be authorized for this key, or you need to log out and back in."
            );
        }
    } catch {
        myShareModal.value.error = tr("Failed to retrieve share.");
    } finally {
        myShareModal.value.loading = false;
    }
}

function closeMyShareModal() {
    // Clear the secret share from memory as soon as the modal closes
    myShareModal.value.share = "";
    myShareModal.value.error = "";
    myShareModal.value.shown = false;
}

async function loadKeys() {
    loading.value = true;
    error.value = "";
    try {
        const res = await fetch("tiki-ajax_services.php?controller=encryption&action=get_keys");
        const data = await res.json();
        const raw: KeyEntry[] = Array.isArray(data) ? data : (data.keys ?? []);
        // shares = count of user holders (server share[0] is separate). 0 means no user can decrypt.
        keys.value = raw.map((k) => ({ ...k, atRisk: (k.shares ?? 0) < 1 }));
    } catch {
        error.value = tr("Failed to load encryption keys.");
    } finally {
        loading.value = false;
    }
}

async function deleteKey(keyId: number) {
    if (!confirm(tr("Delete this encryption key? Fields using it will become inaccessible."))) return;
    deletingId.value = keyId;
    try {
        const res = await fetch("tiki-ajax_services.php?controller=encryption&action=delete_key", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({ keyId: String(keyId), ticket: await getCsrfTicket() }),
        });
        if (!res.ok) {
            error.value = tr("Failed to delete key.");
            return;
        }
        await loadKeys();
    } catch {
        error.value = tr("Failed to delete key.");
    } finally {
        deletingId.value = null;
    }
}

const atRiskCount = computed(() => keys.value.filter((k) => k.atRisk).length);

const { copySuccess, copyError, copyToClipboard } = useClipboard();

onMounted(loadKeys);
</script>

<template>
    <div class="container-fluid py-3">
        <h1 class="h3 mb-1">
            {{ tr("Encryption Key Management") }}
            <i class="fas fa-circle-question text-muted" style="font-size: 1rem" />
        </h1>
        <p class="text-muted small mb-3">{{ tr("Manage shared encryption keys and user access for protected tracker fields.") }}</p>

        <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

        <div class="d-flex gap-2 mb-3">
            <button type="button" class="btn btn-primary btn-sm" @click="$emit('create')">
                <i class="fas fa-circle-plus me-1" />{{ tr("Create new key") }}
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm" @click="loadKeys">
                <i class="fas fa-arrows-rotate me-1" />{{ tr("Refresh") }}
            </button>
        </div>

        <div v-if="loading" class="d-flex align-items-center gap-2 text-muted">
            <span class="spinner-border spinner-border-sm" />
            {{ tr("Loading keys…") }}
        </div>

        <template v-else-if="keys.length === 0">
            <div class="alert alert-info">{{ tr("No encryption keys defined yet. Create one to start protecting tracker fields.") }}</div>
        </template>

        <template v-else>
            <div v-if="atRiskCount > 0" class="alert alert-danger d-flex gap-2 align-items-start mb-4">
                <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0" />
                <div>
                    <strong>{{
                        atRiskCount > 1
                            ? tr("%0 keys are at risk.").replace("%0", String(atRiskCount))
                            : tr("%0 key is at risk.").replace("%0", String(atRiskCount))
                    }}</strong>
                    {{ tr("Fields protected by these keys may become permanently inaccessible. Review the entries highlighted below.") }}
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>{{ tr("Key name") }}</th>
                            <th class="text-center">{{ tr("Shares") }}</th>
                            <th>{{ tr("Users") }}</th>
                            <th style="width: 160px">{{ tr("Actions") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="k in keys" :key="k.keyId" :class="k.atRisk ? 'table-danger' : ''">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i :class="k.atRisk ? 'fas fa-triangle-exclamation text-danger' : 'fas fa-key text-secondary'" />
                                    <div>
                                        <div class="fw-semibold">{{ k.name }}</div>
                                        <div v-if="k.description" class="text-muted small">{{ k.description }}</div>
                                        <div v-if="k.atRisk" class="text-danger small mt-1">
                                            <i class="fas fa-circle-exclamation me-1" />{{
                                                tr("No authorized users — fields using this key cannot be decrypted.")
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span
                                    class="badge"
                                    :class="(k.shares ?? 0) === 0 ? 'bg-danger' : (k.shares ?? 0) === 1 ? 'bg-warning text-dark' : 'bg-success'"
                                    >{{ k.shares ?? 0 }}</span
                                >
                            </td>
                            <td class="small text-muted">{{ k.users || "—" }}</td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        :title="tr('Manage shares')"
                                        @click="$emit('shares', k.keyId, k.name)"
                                    >
                                        <i class="fas fa-users" />
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-info"
                                        :title="tr('Get my share')"
                                        @click="getMyShare(k.keyId, k.name)"
                                    >
                                        <i class="fas fa-key" />
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-warning"
                                        :title="tr('Edit key')"
                                        @click="$emit('edit', k.keyId, k.name)"
                                    >
                                        <i class="fas fa-pencil" />
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        :title="tr('Delete key')"
                                        :disabled="deletingId === k.keyId"
                                        @click="deleteKey(k.keyId)"
                                    >
                                        <span v-if="deletingId === k.keyId" class="spinner-border spinner-border-sm" />
                                        <i v-else class="fas fa-trash" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-muted small mt-2">
                <i class="fas fa-circle-info me-1" />
                <span class="badge bg-danger me-1">0</span> {{ tr("With no shares, no one can decrypt.") }}
                <span class="badge bg-warning text-dark mx-1">1</span>
                {{ tr("A single share is a single point of failure.") }}
                {{ tr("Regenerating a key re-issues shares to all current authorized users.") }}
            </div>
        </template>

        <!-- Get my share modal -->
        <template v-if="myShareModal.shown">
            <div
                class="modal-backdrop fade show"
                style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 1040"
                @click="closeMyShareModal"
            />
            <div
                :id="myShareModalDialogId"
                class="modal d-block"
                style="position: fixed; inset: 0; z-index: 1050"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="`${myShareModalDialogId}-title`"
                tabindex="-1"
                @keydown="handleMyShareModalKeydown"
            >
                <div class="modal-dialog modal-dialog-centered" style="max-width: 520px">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 :id="`${myShareModalDialogId}-title`" class="modal-title">
                                <i class="fas fa-key me-2" />{{ tr("My share for “%0”").replace("%0", myShareModal.keyName) }}
                            </h5>
                            <button type="button" class="btn-close" @click="closeMyShareModal" :aria-label="tr('Close')" />
                        </div>
                        <div class="modal-body">
                            <div v-if="myShareModal.loading" class="d-flex align-items-center gap-2 text-muted">
                                <span class="spinner-border spinner-border-sm" />{{ tr("Retrieving share…") }}
                            </div>
                            <div v-else-if="myShareModal.error" class="alert alert-warning mb-0">
                                {{ myShareModal.error }}
                            </div>
                            <template v-else-if="myShareModal.share">
                                <div class="alert alert-warning d-flex gap-2 align-items-start mb-3 py-2">
                                    <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0" />
                                    <div class="small">
                                        {{
                                            tr(
                                                "Store this share securely (password manager). Anyone with this share plus the server share can decrypt protected fields."
                                            )
                                        }}
                                    </div>
                                </div>
                                <label class="form-label small fw-semibold">{{ tr("Your share") }}</label>
                                <div class="input-group input-group-sm mb-3">
                                    <input type="text" class="form-control font-monospace" readonly :value="myShareModal.share" />
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary"
                                        :title="copySuccess === 'myshare' ? tr('Copied!') : tr('Copy')"
                                        @click="copyToClipboard(myShareModal.share, 'myshare')"
                                    >
                                        <i :class="copySuccess === 'myshare' ? 'fas fa-check text-success' : 'fas fa-clipboard'" />
                                    </button>
                                    <a
                                        class="btn btn-outline-secondary"
                                        :title="tr('Download')"
                                        :href="'data:text/plain;charset=utf-8,' + encodeURIComponent(myShareModal.share)"
                                        :download="`share-${myShareModal.keyName}.txt`"
                                    >
                                        <i class="fas fa-download" />
                                    </a>
                                </div>
                                <div v-if="copyError === 'myshare'" class="text-danger small">
                                    <i class="fas fa-circle-exclamation me-1" />{{ tr("Copy failed — select the text above and copy manually.") }}
                                </div>
                            </template>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" @click="closeMyShareModal">{{ tr("Close") }}</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
