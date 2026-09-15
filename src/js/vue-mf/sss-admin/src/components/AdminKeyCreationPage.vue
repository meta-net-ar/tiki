<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useClipboard } from "../composables/useClipboard";
import { getCsrfTicket } from "../csrf";
const tr = window.tr || ((s: string) => s);

const props = defineProps<{
    mode: "create" | "edit";
    keyId?: number | null;
    keyName?: string;
}>();

const emit = defineEmits<{
    back: [];
    saved: [];
}>();

const name = ref(props.keyName ?? "");
const description = ref("");
const algo = ref("aes-256-ctr");
const availableAlgos = ref<string[]>([]);
const regenerate = ref(false);
const selectedUsers = ref<string[]>([]);
const saving = ref(false);
const saved = ref(false);
const error = ref("");
const savedShares = ref<{ user: string; share: string }[]>([]);
const availableUsers = ref<{ value: string; label: string }[]>([]);
const userSearch = ref("");

const { copySuccess, copyError, copyToClipboard } = useClipboard();

// In edit mode, the user list (like the algorithm) only applies when shares
// are regenerated — the backend ignores it otherwise.
const usersLocked = computed(() => props.mode === "edit" && !regenerate.value);

async function loadUsers(find = "") {
    try {
        const url = find
            ? `tiki-ajax_services.php?controller=encryption&action=get_users&find=${encodeURIComponent(find)}`
            : "tiki-ajax_services.php?controller=encryption&action=get_users";
        const res = await fetch(url);
        const data = await res.json();
        if (Array.isArray(data)) {
            availableUsers.value = data.map((u: string) => ({ value: u, label: u }));
        }
    } catch {
        // fallback: no users loaded
    }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null;
function debouncedLoadUsers() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadUsers(userSearch.value), 300);
}

async function loadAlgos() {
    try {
        const res = await fetch("tiki-ajax_services.php?controller=encryption&action=get_algos");
        const data = await res.json();
        if (Array.isArray(data)) {
            availableAlgos.value = data;
            if (data.length > 0 && !data.includes(algo.value)) {
                algo.value = data[0];
            }
        }
    } catch {
        // fallback: no algo list (sodium path — field stays hidden)
    }
}

async function loadKey() {
    if (!props.keyId) return;
    try {
        const res = await fetch(`tiki-ajax_services.php?controller=encryption&action=get_key&keyId=${props.keyId}`);
        const data = await res.json();
        if (data?.key) {
            name.value = data.key.name ?? props.keyName ?? "";
            description.value = data.key.description ?? "";
            if (data.key.algo) algo.value = data.key.algo;
            if (data.key.users) {
                selectedUsers.value = data.key.users.split(",").filter(Boolean);
            }
        }
    } catch {
        // use props fallback
    }
}

async function save() {
    if (!name.value) return;
    saving.value = true;
    error.value = "";
    try {
        const body = new URLSearchParams({
            name: name.value,
            description: description.value,
            shares: String(selectedUsers.value.length || 1),
            algo: algo.value,
        });
        if (props.mode === "edit" && props.keyId) {
            body.set("keyId", String(props.keyId));
            if (regenerate.value) body.set("regenerate", "1");
        }
        selectedUsers.value.forEach((u) => body.append("users[]", u));
        body.set("ticket", await getCsrfTicket());

        const res = await fetch("tiki-ajax_services.php?controller=encryption&action=save_key", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body,
        });
        if (!res.ok) {
            error.value = tr("Failed to save key. Please check the form and try again.");
            return;
        }
        const data = await res.json();
        const shares: string[] = Array.isArray(data?.shares) ? data.shares : [];
        // A metadata-only update (edit without regeneration) returns no
        // shares — there is nothing new for holders to save in that case.
        savedShares.value = shares.length > 0 ? selectedUsers.value.map((u, i) => ({ user: u, share: shares[i] ?? "" })) : [];
        saved.value = true;
    } catch {
        error.value = tr("A network error occurred.");
    } finally {
        saving.value = false;
    }
}

function goBack() {
    // Clear the one-time-displayed shares from memory before leaving the page
    savedShares.value = [];
    emit("back");
}

onMounted(() => {
    loadUsers();
    loadAlgos();
    if (props.mode === "edit") loadKey();
});
</script>

<template>
    <div class="container-fluid py-3" style="max-width: 720px">
        <div class="d-flex align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="goBack">
                <i class="fas fa-arrow-left me-1" />{{ tr("Back") }}
            </button>
            <h1 class="h4 mb-0">{{ mode === "edit" ? tr("Edit Key") : tr("Create Encryption Key") }}</h1>
        </div>

        <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

        <div v-if="saved" class="alert alert-success d-flex gap-2 align-items-start mb-4">
            <i class="fas fa-circle-check mt-1 flex-shrink-0" />
            <div>
                <strong>{{ tr("Key “%0” saved.").replace("%0", name) }}</strong>
                <template v-if="savedShares.length > 0">{{ tr("Shares distributed to authorized users automatically.") }}</template>
            </div>
        </div>

        <template v-if="saved && savedShares.length === 0">
            <div class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="goBack">
                    <i class="fas fa-table-cells-large me-1" />{{ tr("Back to dashboard") }}
                </button>
            </div>
        </template>

        <template v-if="saved && savedShares.length > 0">
            <div class="alert alert-warning d-flex gap-2 align-items-start mb-3 py-2">
                <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0" />
                <div class="small">
                    <strong>{{ tr("Save your share now.") }}</strong>
                    {{
                        tr(
                            "Each holder's share is shown once. Store it in a password manager or secure location. The server can retrieve stored shares for logged-in users, but having an offline copy is recommended for disaster recovery."
                        )
                    }}
                </div>
            </div>
            <table class="table table-sm table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>{{ tr("Holder") }}</th>
                        <th>{{ tr("Share") }}</th>
                        <th>{{ tr("Actions") }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ tr("Server (Tiki DB)") }}</strong>
                        </td>
                        <td>
                            <code class="text-muted small">{{ tr("Server share — not disclosed") }}</code>
                        </td>
                        <td class="text-muted small">{{ tr("Stored in %0").replace("%0", "tiki_encryption_keys.secret") }}</td>
                    </tr>
                    <tr v-for="s in savedShares" :key="s.user">
                        <td>
                            <strong>{{ s.user }}</strong>
                        </td>
                        <td>
                            <code class="small" style="word-break: break-all">{{ s.share || tr("(not available)") }}</code>
                        </td>
                        <td>
                            <div v-if="s.share" class="d-flex gap-1 align-items-center flex-wrap">
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-secondary"
                                    :title="copySuccess === s.user ? tr('Copied!') : tr('Copy share')"
                                    @click="copyToClipboard(s.share, s.user)"
                                >
                                    <i :class="copySuccess === s.user ? 'fas fa-check text-success' : 'fas fa-clipboard'" />
                                </button>
                                <a
                                    class="btn btn-sm btn-outline-secondary"
                                    :href="'data:text/plain;charset=utf-8,' + encodeURIComponent(s.share)"
                                    :download="`share-${name}-${s.user}.txt`"
                                    :title="tr('Download share')"
                                >
                                    <i class="fas fa-download" />
                                </a>
                                <span v-if="copyError === s.user" class="text-danger small w-100">
                                    <i class="fas fa-circle-exclamation me-1" />{{ tr("Copy failed — copy manually.") }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="text-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="goBack">
                    <i class="fas fa-table-cells-large me-1" />{{ tr("Back to dashboard") }}
                </button>
            </div>
        </template>

        <template v-if="!saved">
            <h2 style="font-size: 1.3rem; font-weight: 400" class="mb-4">{{ tr("General information") }}</h2>

            <div class="row mb-3">
                <label class="col-sm-4 col-form-label col-form-label-sm">{{ tr("Key name or domain") }}</label>
                <div class="col-sm-8">
                    <input v-model="name" type="text" class="form-control form-control-sm" />
                </div>
            </div>

            <div class="row mb-3">
                <label class="col-sm-4 col-form-label col-form-label-sm">{{ tr("Description") }}</label>
                <div class="col-sm-8">
                    <textarea v-model="description" class="form-control form-control-sm" rows="5" />
                </div>
            </div>

            <div v-if="availableAlgos.length > 0" class="row mb-3">
                <label class="col-sm-4 col-form-label col-form-label-sm">{{ tr("Encryption algorithm") }}</label>
                <div class="col-sm-8">
                    <select v-model="algo" class="form-select form-select-sm" :disabled="mode === 'edit' && !regenerate">
                        <option v-for="a in availableAlgos" :key="a" :value="a">{{ a }}</option>
                    </select>
                    <div v-if="mode === 'edit' && !regenerate" class="form-text text-muted">
                        {{ tr("Algorithm cannot be changed without regenerating shares.") }}
                    </div>
                </div>
            </div>

            <div v-if="mode === 'edit'" class="row mb-3">
                <label class="col-sm-4 col-form-label col-form-label-sm">{{ tr("Regenerate shares") }}</label>
                <div class="col-sm-8 d-flex align-items-center gap-2">
                    <input v-model="regenerate" type="checkbox" class="form-check-input" />
                    <span class="small text-muted">{{ tr("Generate new shares for all authorized users") }}</span>
                </div>
            </div>

            <div class="row mb-4">
                <label class="col-sm-4 col-form-label col-form-label-sm">{{ tr("Users to share with") }}</label>
                <div class="col-sm-8">
                    <input
                        v-model="userSearch"
                        type="search"
                        class="form-control form-control-sm mb-2"
                        :placeholder="tr('Search users…')"
                        :disabled="usersLocked"
                        @input="debouncedLoadUsers"
                    />
                    <div class="border rounded p-2" style="max-height: 150px; overflow-y: auto">
                        <div v-for="u in availableUsers" :key="u.value" class="form-check">
                            <input
                                :id="`sss-user-${u.value}`"
                                v-model="selectedUsers"
                                type="checkbox"
                                class="form-check-input"
                                :value="u.value"
                                :disabled="usersLocked"
                            />
                            <label :for="`sss-user-${u.value}`" class="form-check-label">{{ u.label }}</label>
                        </div>
                        <div v-if="availableUsers.length === 0" class="text-muted small">
                            {{ userSearch ? tr("No users match your search.") : tr("Loading users…") }}
                        </div>
                    </div>
                    <div v-if="usersLocked" class="form-text text-muted">{{ tr("User access cannot be changed without regenerating shares.") }}</div>
                </div>
            </div>

            <div class="text-center">
                <button type="button" class="btn btn-primary me-2" :disabled="saving || !name" @click="save">
                    <span v-if="saving" class="spinner-border spinner-border-sm me-1" />
                    {{ tr("Apply") }}
                </button>
                <button type="button" class="btn btn-outline-secondary" @click="goBack">{{ tr("Cancel") }}</button>
            </div>
        </template>
    </div>
</template>
