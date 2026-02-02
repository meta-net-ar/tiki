<script setup>
import { getAutocompleteResources } from "../../../../@tiki/ui-utils/autocomplete";
import { fetchSuggestions } from "../../../../vue-widgets/element-plus-ui/src/helpers/autocomplete/remote";
import DialogInput from "./DialogInput.vue";
import { ref, computed, onMounted } from "vue";
import { ElAutocomplete } from "element-plus";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const labelInput = ref("");
const pageInput = ref("");
const relationInput = ref("");

const toolbarObject = computed(() => props.toolbarObject);

const isElementPlusAutocompleteActive = ref(!!window.elementPlus?.autocomplete)
const autoCompleteResources = getAutocompleteResources('pagename');

const translate = window.tr;

onMounted(() => {
    _shown();
    $(toolbarObject.value.modalElement)
    .find('[data-bs-toggle="tooltip"]')
    .tooltip();
});

function _shown() {

    const textArea = document.getElementById(toolbarObject.value.domElementId);
    const selection = getTASelection(textArea);

    let parts = selection.match(/\((.*?)\((.*?)\|(.*?)\)\)/);
    if (! parts) {
        parts = selection.match(/\((.*?)\((.*?)\)\)/);
    }

    if (parts) {
        labelInput.value = parts[3] ?? "";
        pageInput.value = parts[2] ?? "";
        relationInput.value = parts[1];
    } else {
        labelInput.value = selection;
        pageInput.value = "";
        relationInput.value = "";
    }
}

function _insert() {
    let output = "";
    if (pageInput.value) {
        output += "(";
        if (relationInput.value) {
            output += relationInput.value;
        }
        output += `(${pageInput.value}`;
        if (labelInput.value) {
            output += `|${labelInput.value}`;
        }
        output += "))";
    }

    insertAt(toolbarObject.value.domElementId, output, false, false, true);

    return output;
}

defineExpose({ execute: _insert, shown: _shown });
</script>

<template>
    <DialogInput v-model="labelInput" label="Label" class="mb-2" />
    <el-autocomplete
        v-model="pageInput"
        :fetch-suggestions="(query, callback) => fetchSuggestions(query, callback, autoCompleteResources.url)"
        :placeholder="translate('Search for a wiki page...')"
        :value-key="autoCompleteResources.valueKey"
        clearable
        :debounce="500"
        :trigger-on-focus="false"
        :highlight-first-item="true"
        class="mb-2"
        v-if="isElementPlusAutocompleteActive"
    />
    <DialogInput v-model="pageInput" label="Page" class="mb-2" v-else />
    <div class="input-group input-group-sm">
        <DialogInput v-model="relationInput" label="Semantic Relation" />
        <span class="input-group-text" data-bs-toggle="tooltip" title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages.">
            <span class="fa fa-circle-info"></span>
        </span>
    </div>
</template>
