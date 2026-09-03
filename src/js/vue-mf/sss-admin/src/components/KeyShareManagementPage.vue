<script setup lang="ts">
import { ref, onMounted } from "vue";
import { getCsrfTicket } from "../csrf";

const tr = window.tr || ((s: string) => s);

interface KeyShareUser {
    name: string;
    login: string;
    hasAccess: boolean;
}

const props = defineProps<{
    keyId: number | null;
    keyName?: string;
}>();

defineEmits<{ back: [] }>();

const users = ref<KeyShareUser[]>([]);
const loading = ref(true);
const error = ref("");
const saving = ref<string | null>(null);
const savedMsg = ref<string | null>(null);
const loadedKeyName = ref("");
// The authoritative set of holders as stored on the key. get_users is capped
// (50 without a search term), so the visible table is only a window onto the
// user base — this list, not the table, is the source of truth for who holds a
// share, so a grant/revoke never drops holders that fall outside that window.
const authorizedLogins = ref<string[]>([]);
// Recovery state: when an action fails because the admin's own key share cannot
// be read (e.g. after a password change that bypassed the encryption rehash),
// we let them paste their share and retry, sending it as old_share.
const recoveryFor = ref<KeyShareUser | null>(null);
const recoveryShare = ref("");

async function loadShares() {
    if (!props.keyId) return;
    loading.value = true;
    error.value = "";
    try {
        const [keyRes, usersRes] = await Promise.all([
            fetch(`tiki-ajax_services.php?controller=encryption&action=get_key&keyId=${props.keyId}`),
            fetch("tiki-ajax_services.php?controller=encryption&action=get_users"),
        ]);
        const keyData = await keyRes.json();
        const usersData = await usersRes.json();

        loadedKeyName.value = keyData?.key?.name ?? "";
        const authorizedUsers = (keyData?.key?.users ?? "").split(",").filter(Boolean);
        authorizedLogins.value = authorizedUsers;
        const allUsers: string[] = Array.isArray(usersData) ? usersData : [];

        // Always show every current holder, even one who falls outside the
        // (capped) get_users window, so an admin can always see and revoke them.
        const shown = new Set(allUsers);
        const displayLogins = [...allUsers, ...authorizedUsers.filter((u: string) => !shown.has(u))];

        users.value = displayLogins.map((login: string) => ({
            name: login,
            login,
            hasAccess: authorizedUsers.includes(login),
        }));
    } catch {
        error.value = tr("Failed to load share information.");
    } finally {
        loading.value = false;
    }
}

async function toggleAccess(user: KeyShareUser, oldShare?: string) {
    saving.value = user.login;
    error.value = "";
    // Derive the new holder list from the authoritative set (not the visible,
    // capped table) and apply just this one grant/revoke — otherwise holders
    // outside the get_users window would be silently dropped and lose their share.
    const next = new Set(authorizedLogins.value);
    if (user.hasAccess) {
        next.delete(user.login);
    } else {
        next.add(user.login);
    }
    const newUsers = [...next];

    try {
        const body = new URLSearchParams({
            keyId: String(props.keyId),
            name: loadedKeyName.value || (props.keyName ?? ""),
            // Changing the authorized user list redistributes shares, which
            // requires regenerating them from the reconstructed key.
            regenerate: "1",
        });
        newUsers.forEach((u) => body.append("users[]", u));
        // Recovery: when the server cannot read our stored share, the admin can
        // paste it here. The backend reconstructs the key from this share, then
        // regeneration re-stores a fresh share under the current login phrase.
        if (oldShare) {
            body.append("old_share", oldShare);
        }
        body.set("ticket", await getCsrfTicket());

        const res = await fetch("tiki-ajax_services.php?controller=encryption&action=save_key", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body,
        });
        if (res.ok) {
            savedMsg.value = user.login;
            recoveryFor.value = null;
            recoveryShare.value = "";
            setTimeout(() => {
                savedMsg.value = null;
            }, 1800);
            // Reload from the server instead of toggling optimistically, so
            // the table always reflects the persisted access list.
            await loadShares();
        } else {
            let message = "";
            try {
                const data = await res.json();
                // Tiki surfaces service errors either as { message } or as
                // { errors: [{ message }] } depending on the exception type.
                message = data?.errors?.[0]?.message || data?.message || "";
            } catch {
                // Non-JSON body (e.g. an HTML 403 page returned when the server
                // cannot reconstruct the key from your stored share). Fall through
                // to the actionable default below.
            }
            if (!message) {
                message = tr("Could not update access. Your encryption key share could not be read.");
            }
            error.value = message;
            // Offer in-place recovery: paste the share and retry.
            recoveryFor.value = user;
        }
    } catch {
        error.value = tr("A network error occurred.");
    } finally {
        saving.value = null;
    }
}

function retryWithShare() {
    if (recoveryFor.value && recoveryShare.value.trim()) {
        toggleAccess(recoveryFor.value, recoveryShare.value.trim());
    }
}

function cancelRecovery() {
    recoveryFor.value = null;
    recoveryShare.value = "";
    error.value = "";
}

onMounted(loadShares);
</script>

<template>
    <div class="container-fluid py-3">
        <div class="d-flex align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="$emit('back')">
                <i class="fas fa-arrow-left me-1" />{{ tr("Back") }}
            </button>
            <h1 class="h4 mb-0">{{ tr("Key Share Management") }}</h1>
        </div>

        <div class="alert alert-info d-flex gap-2 align-items-start mb-3">
            <i class="fas fa-shield-halved mt-1" />
            <div>
                {{ tr("Managing access shares for encryption key “%0”.").replace("%0", keyName ?? tr("unknown")) }}
                {{ tr("Each authorized user receives a key share. Decryption requires their share plus the server share.") }}
            </div>
        </div>
        <div class="alert alert-warning d-flex gap-2 align-items-start mb-4 py-2">
            <i class="fas fa-triangle-exclamation mt-1 flex-shrink-0" />
            <div class="small">
                <strong>{{ tr("Granting or revoking access regenerates all shares for the updated user list.") }}</strong>
                {{
                    tr(
                        "Every authorized holder will receive a new share stored in their account. Existing offline copies of old shares will continue to work until the key itself is regenerated."
                    )
                }}
            </div>
        </div>

        <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

        <div v-if="recoveryFor" class="card border-primary mb-3">
            <div class="card-body py-3">
                <h2 class="h6 mb-2"><i class="fas fa-key me-1" />{{ tr("Enter your key share to continue") }}</h2>
                <p class="small text-muted mb-2">
                    {{ tr("The server could not read your stored share for this key (this can happen after a password change).") }}
                    {{ tr("Paste your share for “%0” to recover.").replace("%0", keyName ?? tr("this key")) }}
                    {{ tr("Your stored share will be repaired automatically.") }}
                </p>
                <div class="input-group input-group-sm">
                    <input
                        v-model="recoveryShare"
                        type="password"
                        class="form-control"
                        :placeholder="tr('Paste your key share')"
                        autocomplete="off"
                        @keyup.enter="retryWithShare"
                    />
                    <button type="button" class="btn btn-primary" :disabled="!recoveryShare.trim() || saving !== null" @click="retryWithShare">
                        <span v-if="saving !== null" class="spinner-border spinner-border-sm me-1" />{{ tr("Retry with my share") }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="cancelRecovery">{{ tr("Cancel") }}</button>
                </div>
            </div>
        </div>

        <div v-if="loading" class="d-flex align-items-center gap-2 text-muted mb-3">
            <span class="spinner-border spinner-border-sm" />{{ tr("Loading users…") }}
        </div>

        <table v-else class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ tr("User") }}</th>
                    <th>{{ tr("Access") }}</th>
                    <th style="width: 160px">{{ tr("Action") }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.login">
                    <td>
                        <i class="fas fa-circle-user me-2 text-secondary" />
                        {{ user.name }}
                    </td>
                    <td>
                        <span v-if="user.hasAccess" class="badge bg-success"> <i class="fas fa-unlock me-1" />{{ tr("Has share") }} </span>
                        <span v-else class="badge bg-secondary"> <i class="fas fa-lock me-1" />{{ tr("No access") }} </span>
                    </td>
                    <td>
                        <span v-if="saving === user.login" class="spinner-border spinner-border-sm text-secondary" />
                        <span v-else-if="savedMsg === user.login" class="text-success small">
                            <i class="fas fa-circle-check me-1" />{{ tr("Saved") }}
                        </span>
                        <button
                            type="button"
                            v-else
                            class="btn btn-sm"
                            :class="user.hasAccess ? 'btn-outline-danger' : 'btn-outline-success'"
                            @click="toggleAccess(user)"
                        >
                            <i :class="user.hasAccess ? 'fas fa-circle-xmark me-1' : 'fas fa-circle-plus me-1'" />
                            {{ user.hasAccess ? tr("Revoke access") : tr("Grant access") }}
                        </button>
                    </td>
                </tr>
                <tr v-if="users.length === 0">
                    <td colspan="3" class="text-muted text-center">{{ tr("No users found.") }}</td>
                </tr>
            </tbody>
        </table>

        <div class="mt-3 text-muted small">
            <i class="fas fa-circle-info me-1" />
            {{
                tr(
                    "Revoking access removes the user's stored share from their account. If they copied their share elsewhere before revocation, they may still reconstruct the key until it is regenerated."
                )
            }}
        </div>
        <div class="alert alert-warning d-flex gap-2 align-items-start mt-3 mb-0 py-2">
            <i class="fas fa-arrows-rotate mt-1 flex-shrink-0" />
            <div class="small">
                {{
                    tr(
                        "True cryptographic revocation requires regenerating the key and re-encrypting all protected fields — no existing share will work afterward."
                    )
                }}
                {{ tr("This is a roadmap feature.") }}
            </div>
        </div>
    </div>
</template>
