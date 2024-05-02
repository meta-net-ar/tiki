<script setup>
import DialogInput from "./DialogInput.vue";
import { onMounted, defineProps, defineExpose, ref } from "vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const labelInputElement = ref();
const pageInputElement = ref();
const toolbarObject = ref(props.toolbarObject);

const labelInput = ref("");
const pageInput = ref("");
const relationInput = ref("");

onMounted(() => {
    $(labelInputElement.value.$el)
        .parents(".modal").first()
        .on("show.bs.modal", (event) => {
            _shown(event);
            $(this).find('[data-bs-toggle="tooltip"]').tooltip();
        });
});

function _shown() {

    const textArea = document.getElementById(toolbarObject.value.domElementId);
    const selection = getTASelection(textArea);

    $(pageInputElement.value.$el).tiki("autocomplete", "pagename");

    let parts = selection.match(/\((.*?)\((.*?)\|(.*?)\)\)/);
    if (! parts) {
        parts = selection.match(/\((.*?)\((.*?)\)\)/);
    }

    if (parts) {
        labelInput.value = parts[3] ?? "";
        pageInput.value = parts[2] ?? "";
        relationInput.value = parts[1];
    } else {
        labelInput.value = "";
        pageInput.value = "";
        relationInput.value = "";
    }
}

function _insert() {
    let output = "";

    // pageInput.value doesn't get updated by tiki=>autocomplete yet
    if (pageInputElement.value.$el.value) {
        output += "(";
        if (relationInput.value) {
            output += relationInput.value;
        }
        output += `(${pageInputElement.value.$el.value}`;
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
    <DialogInput ref="labelInputElement" v-model="labelInput" label="Label" class="mb-2" />
    <DialogInput ref="pageInputElement" v-model="pageInput" label="Page" class="mb-2" />
    <div class="input-group input-group-sm">
        <DialogInput ref="tdgRelation" v-model="relationInput" label="Semantic Relation" />
        <span class="input-group-text" data-bs-toggle="tooltip" title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages.">
            <span class="fa fa-circle-info"></span>
        </span>
    </div>
</template>
