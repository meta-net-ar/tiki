<script setup>
import DialogInput from "./DialogInput.vue";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const labelInput = ref("");
const urlInput = ref("");
const relationInput = ref("");

const toolbarObject = computed(() => props.toolbarObject);

onMounted(() => {
    _shown();
    $(toolbarObject.value.modalElement)
    .find('[data-bs-toggle="tooltip"]')
    .tooltip();
});

function _shown() {
    const textArea = document.getElementById(toolbarObject.value.domElementId);
    const selection = getTASelection(textArea);

    let parts;

    if (! toolbarObject.value.editor.isMarkdown) {
        parts = selection.match(/\[(.*?)\|(.*?)\|(.*?)]/);
        if (!parts) {
            parts = selection.match(/\[(.*?)\|(.*?)]/);
        }
        if (!parts) {
            parts = selection.match(/\[(.*?)]/);
        }
    } else {
        parts = selection.match(/\[(.*?)]\((.*?)\)/);
        if (parts) {
            const label = parts[1];
            parts[1] = parts[2];
            parts[2] = label;
        }
    }

    if (parts) {
        urlInput.value = parts[1];
        labelInput.value = parts[2] ?? "";
        relationInput.value = parts[3] ?? "";
    } else {
        labelInput.value = toolbarObject.value.labelText != null ? toolbarObject.value.labelPage : selection;
        urlInput.value = toolbarObject.value.labelPage != null ? toolbarObject.value.labelPage : "";
        relationInput.value = "";
    }
}

function _insert() {
    let output = "";
    if (! toolbarObject.value.editor.isMarkdown) {
        if (urlInput.value) {
            output = "[";
            output += urlInput.value;
            if (labelInput.value) {
                output += `|${labelInput.value}`;
                if (relationInput.value) {
                    output += `|${relationInput.value}`;
                }
                output += "]";
            }
        }
    } else {    // markdown
        if (toolbarObject.value.editor.isWysiwyg) {
            output = {linkText: labelInput.value, linkUrl: urlInput.value};
            const foo = tuiEditors[toolbarObject.value.domElementId].exec("addLink", output);
            return;
        } else {
            output = `[${labelInput.value}](${urlInput.value})`;
        }
    }
    insertAt(toolbarObject.value.domElementId, output, false, false, true);

    return output;
}

defineExpose({ execute: _insert, shown: _shown });
</script>

<template>
    <DialogInput v-model="labelInput" label="Label" class="mb-2" />
    <DialogInput v-model="urlInput" label="URL" class="mb-2" />
    <div class="input-group mr-sm-2" v-if="! toolbarObject.editor.isMarkdown">
        <DialogInput v-model="relationInput" label="Relation" />
        <div class="input-group-text" data-bs-toggle="tooltip" title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages.">
            <span class="fa fa-circle-info"></span>
        </div>
    </div>
</template>
