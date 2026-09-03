<template>
    <div class="table-responsive">
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-primary btn-sm" @click="addRedirect">
                {{ $t('Add Redirect') }}
            </button>
        </div>

        <table class="table table-striped table-hover table-bordered align-middle mb-0 redirects-table">
            <thead class="table-light">
                <tr>
                    <th scope="col">{{ $t('From URL') }}</th>
                    <th scope="col">{{ $t('To URL') }}</th>
                    <th scope="col">{{ $t('Code') }}</th>
                    <th scope="col" class="text-end">{{ $t('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="!redirectsData.length">
                    <td colspan="4" class="text-muted text-center py-4">{{ $t('No redirects found.') }}</td>
                </tr>

                <tr v-for="(redirect, index) in redirectsData" :key="redirect.relationId ?? index">
                    <td>
                        <div v-if="isEditingRow(index)" class="edit-cell">
                            <div class="form-check form-check-inline">
                                <input
                                    id="source-existing"
                                    v-model="editingDraft.source_mode"
                                    class="form-check-input"
                                    type="radio"
                                    value="existing"
                                >
                                <label class="form-check-label" for="source-existing">{{ $t('Existing page') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input
                                    id="source-custom"
                                    v-model="editingDraft.source_mode"
                                    class="form-check-input"
                                    type="radio"
                                    value="custom"
                                >
                                <label class="form-check-label" for="source-custom">{{ $t('Custom URL') }}</label>
                            </div>

                            <select
                                v-if="editingDraft.source_mode === 'existing'"
                                v-model="editingDraft.source_itemId"
                                class="form-select form-select-sm mt-2"
                                @change="selectExistingPage('source', editingDraft.source_itemId)"
                            >
                                <option value="">Select page...</option>
                                <option v-for="page in pageOptions" :key="page.itemId" :value="page.itemId">
                                    {{ page.href }} · {{ page.type }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="editingDraft.custom_source"
                                type="text"
                                class="form-control form-control-sm mt-2"
                                placeholder="/old-page"
                            >

                            <div class="small text-muted mt-2">
                                <span v-if="editingDraft.source_mode === 'existing' && editingDraft.source_itemId">
                                    Selected: {{ getRedirectPreview('source') }}
                                </span>
                                <span v-else-if="editingDraft.source_mode === 'custom' && editingDraft.custom_source">
                                    Selected: {{ editingDraft.custom_source }}
                                </span>
                                <span v-else>Select a page or enter a custom URL.</span>
                            </div>
                        </div>
                        <div v-else class="text-break">
                            {{ getDisplayUrl(redirect, 'source') }}
                        </div>
                    </td>

                    <td>
                        <div v-if="isEditingRow(index)" class="edit-cell">
                            <div class="form-check form-check-inline">
                                <input
                                    id="target-existing"
                                    v-model="editingDraft.target_mode"
                                    class="form-check-input"
                                    type="radio"
                                    value="existing"
                                >
                                <label class="form-check-label" for="target-existing">{{ $t('Existing page') }}</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input
                                    id="target-custom"
                                    v-model="editingDraft.target_mode"
                                    class="form-check-input"
                                    type="radio"
                                    value="custom"
                                >
                                <label class="form-check-label" for="target-custom">{{ $t('Custom URL') }}</label>
                            </div>
                            <select
                                v-if="editingDraft.target_mode === 'existing'"
                                v-model="editingDraft.target_itemId"
                                class="form-select form-select-sm mt-2"
                                @change="selectExistingPage('target', editingDraft.target_itemId)"
                            >
                                <option value="">Select page...</option>
                                <option v-for="page in pageOptions" :key="page.itemId" :value="page.itemId">
                                    {{ page.href }} · {{ page.type }}
                                </option>
                            </select>
                            <input
                                v-else
                                v-model="editingDraft.custom_target"
                                type="text"
                                class="form-control form-control-sm mt-2"
                                placeholder="/new-page"
                            >

                            <div class="small text-muted mt-2">
                                <span v-if="editingDraft.target_mode === 'existing' && editingDraft.target_itemId">
                                    Selected: {{ getRedirectPreview('target') }}
                                </span>
                                <span v-else-if="editingDraft.target_mode === 'custom' && editingDraft.custom_target">
                                    Selected: {{ editingDraft.custom_target }}
                                </span>
                                <span v-else>Select a page or enter a custom URL.</span>
                            </div>
                        </div>
                        <div v-else class="text-break">
                            {{ getDisplayUrl(redirect, 'target') }}
                        </div>
                    </td>

                    <td>
                        <select
                            v-if="isEditingRow(index)"
                            v-model.number="editingDraft.redirect_code"
                            class="form-select form-select-sm"
                        >
                            <option v-for="option in redirectCodeOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <span v-else>{{ getCodeLabel(redirect.redirect_code) }}</span>
                    </td>

                    <td class="text-end">
                        <div v-if="isEditingRow(index)" class="d-flex flex-column align-items-end gap-2">
                            <div v-if="saveError" class="text-danger small">{{ saveError }}</div>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-success" :disabled="!canSaveRedirect" @click="saveEdit(index)">{{ $t('Save') }}</button>
                                <button type="button" class="btn btn-outline-secondary" @click="cancelEdit(index)">{{ $t('Cancel') }}</button>
                            </div>
                        </div>
                        <div v-else class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary" @click="startEdit(index)">{{ $t('Edit') }}</button>
                            <button type="button" class="btn btn-outline-danger" @click="deleteRedirect(index)">{{ $t('Delete') }}</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const parseJsonArray = (value) => {
    if (value === null || value === undefined) {
        return [];
    }

    if (Array.isArray(value)) {
        return value;
    }

    if (typeof value === 'string') {
        if (value.trim() === '') {
            return [];
        }

        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : Object.values(parsed || {});
        } catch (error) {
            return [];
        }
    }

    if (typeof value === 'object') {
        return Object.values(value);
    }

    return [];
};

const props = defineProps({
    redirects: {
        type: String,
        default: '[]'
    },
    wikiPages: {
        type: String,
        default: '[]'
    },
    blogPosts: {
        type: String,
        default: '[]'
    },
    articles: {
        type: String,
        default: '[]'
    },
    baseUrl: {
        type: String,
        default: ''
    }
});

const parsedRedirects = computed(() => parseJsonArray(props.redirects));
const parsedWikiPages = computed(() => parseJsonArray(props.wikiPages));
const parsedBlogPosts = computed(() => parseJsonArray(props.blogPosts));
const parsedArticles = computed(() => parseJsonArray(props.articles));

const redirectCodeOptions = [
    { value: 301, label: '301 Permanent' },
    { value: 302, label: '302 Temporary' },
    { value: 307, label: '307 Temporary' },
    { value: 308, label: '308 Permanent' },
];

const createEmptyRedirect = () => ({
    relationId: null,
    relation: 'tiki.link.redirect',
    redirect_code: 301,
    source_mode: 'existing',
    target_mode: 'existing',
    custom_source: '',
    custom_target: '',
    source_type: 'wiki page',
    source_itemId: '',
    source_fieldId: null,
    target_type: 'wiki page',
    target_itemId: '',
    metadata_itemId: null,
});

const pageOptions = computed(() => {
    const map = new Map();

    const add = (items, fallbackType) => {
        items.forEach((item) => {
            const itemId = item?.itemId || item?.id || item?.objectId || '';
            if (!itemId) {
                return;
            }

            map.set(itemId, {
                itemId,
                href: item?.href || '',
                type: item?.type || fallbackType,
            });
        });
    };

    add(parsedWikiPages.value, 'Wiki Page');
    add(parsedBlogPosts.value, 'Blog Post');
    add(parsedArticles.value, 'Article');

    return [...map.values()].sort((a, b) => a.href.localeCompare(b.href));
});

const pageLookup = computed(() => {
    const lookup = {};

    pageOptions.value.forEach((page) => {
        lookup[page.itemId] = page;
    });

    return lookup;
});

const normalizeRedirect = (redirect = {}) => ({
    relationId: redirect.relationId ?? null,
    relation: redirect.relation ?? 'tiki.link.redirect',
    redirect_code: Number.parseInt(redirect.redirect_code ?? redirect.redirectCode ?? 301, 10) || 301,
    source_mode: redirect.source_mode ?? 'existing',
    target_mode: redirect.target_mode ?? 'existing',
    custom_source: redirect.custom_source ?? '',
    custom_target: redirect.custom_target ?? '',
    source_type: redirect.source_type ?? 'wiki page',
    source_itemId: redirect.source_itemId ?? '',
    source_fieldId: redirect.source_fieldId ?? null,
    target_type: redirect.target_type ?? 'wiki page',
    target_itemId: redirect.target_itemId ?? '',
    metadata_itemId: redirect.metadata_itemId ?? null,
});

const redirectsData = ref(parsedRedirects.value.map((redirect) => normalizeRedirect(redirect)));
const editingRowIndex = ref(null);
const editingDraft = ref(createEmptyRedirect());
const pendingNewRowIndex = ref(null);
const saveError = ref('');

const getDisplayUrl = (redirect, side) => {
    if (side === 'source') {
        if (redirect.source_mode === 'custom') {
            return redirect.custom_source || redirect.source_itemId || '';
        }

        return pageLookup.value[redirect.source_itemId]?.href || redirect.source_itemId || '';
    }

    if (redirect.target_mode === 'custom') {
        return redirect.custom_target || redirect.target_itemId || '';
    }

    return pageLookup.value[redirect.target_itemId]?.href || redirect.target_itemId || '';
};

const getRedirectPreview = (side) => {
    if (side === 'source') {
        if (editingDraft.value.source_mode === 'custom') {
            return editingDraft.value.custom_source || '';
        }

        return pageLookup.value[editingDraft.value.source_itemId]?.href || '';
    }

    if (editingDraft.value.target_mode === 'custom') {
        return editingDraft.value.custom_target || '';
    }

    return pageLookup.value[editingDraft.value.target_itemId]?.href || '';
};

const getCodeLabel = (code) => {
    const match = redirectCodeOptions.find((option) => option.value === Number(code));
    return match ? match.label : `${code}`;
};

const selectExistingPage = (side, itemId) => {
    const page = pageLookup.value[itemId];

    if (side === 'source') {
        editingDraft.value.source_itemId = itemId;
        editingDraft.value.source_type = page?.type || 'wiki page';
        editingDraft.value.custom_source = '';
    } else {
        editingDraft.value.target_itemId = itemId;
        editingDraft.value.target_type = page?.type || 'wiki page';
        editingDraft.value.custom_target = '';
    }
};

const canSaveRedirect = computed(() => {
    const sourceUrl = getRedirectPreview('source').trim();
    const targetUrl = getRedirectPreview('target').trim();

    return Boolean(sourceUrl && targetUrl && sourceUrl !== targetUrl);
});

const emitUpdatedRedirects = () => {
    const normalizedRedirects = redirectsData.value.map((redirect) => normalizeRedirect(redirect));

    const event = new CustomEvent('redirect-builder:update', {
        detail: normalizedRedirects,
        bubbles: true,
    });
    document.dispatchEvent(event);
};

const isEditingRow = (index) => editingRowIndex.value === index;

const startEdit = (index) => {
    saveError.value = '';
    editingRowIndex.value = index;
    editingDraft.value = { ...normalizeRedirect(redirectsData.value[index]) };
};

const saveEdit = (index) => {
    const sourceUrl = getRedirectPreview('source').trim();
    const targetUrl = getRedirectPreview('target').trim();

    if (!sourceUrl || !targetUrl) {
        saveError.value = 'Please provide both source and target URLs.';
        return;
    }

    if (sourceUrl === targetUrl) {
        saveError.value = 'Source and target cannot be the same URL.';
        return;
    }

    const exists = redirectsData.value.some((redirect, rowIndex) => {
        if (rowIndex === index) {
            return false;
        }

        return getDisplayUrl(redirect, 'source') === sourceUrl && getDisplayUrl(redirect, 'target') === targetUrl;
    });

    if (exists) {
        saveError.value = 'Redirect already exists.';
        return;
    }

    redirectsData.value[index] = normalizeRedirect(editingDraft.value);
    editingRowIndex.value = null;
    pendingNewRowIndex.value = null;
    saveError.value = '';
    emitUpdatedRedirects();
};

const cancelEdit = (index) => {
    if (pendingNewRowIndex.value === index) {
        redirectsData.value.splice(index, 1);
        pendingNewRowIndex.value = null;
    }

    editingRowIndex.value = null;
    editingDraft.value = createEmptyRedirect();
    saveError.value = '';
};

const deleteRedirect = (index) => {
    redirectsData.value.splice(index, 1);

    if (editingRowIndex.value === index) {
        editingRowIndex.value = null;
    }

    if (pendingNewRowIndex.value === index) {
        pendingNewRowIndex.value = null;
    }

    emitUpdatedRedirects();
};

const addRedirect = () => {
    const newIndex = redirectsData.value.length;
    redirectsData.value.push(createEmptyRedirect());
    pendingNewRowIndex.value = newIndex;
    startEdit(newIndex);
    emitUpdatedRedirects();
};

watch(
    () => props.redirects,
    (newValue) => {
        redirectsData.value = parseJsonArray(newValue).map((redirect) => normalizeRedirect(redirect));

        if (editingRowIndex.value !== null) {
            editingRowIndex.value = null;
            pendingNewRowIndex.value = null;
            editingDraft.value = createEmptyRedirect();
            saveError.value = '';
        }
    }
);
</script>

<style scoped>
.redirects-table th,
.redirects-table td {
    padding: 0.9rem 0.85rem;
    font-size: 0.98rem;
}

.edit-cell {
    min-width: 18rem;
}
</style>