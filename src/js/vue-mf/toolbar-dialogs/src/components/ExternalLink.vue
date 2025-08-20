<script setup>
import DialogInput from "./DialogInput.vue";
import { onMounted, defineProps, defineExpose, ref } from "vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const tdgLabel = ref();
const toolbarObject = ref(props.toolbarObject);

const labelInput = ref("");
const urlInput = ref("");
const relationInput = ref("");

onMounted(() => {
    $(tdgLabel.value.$el)
        .parents(".modal").first()
        .on("show.bs.modal", (event) => {
            _shown(event);
            $(this).find('[data-bs-toggle="tooltip"]').tooltip();
        });
});

/**
 * Validate URL format and add protocol if needed
 * @param {string} url - The URL to validate
 * @returns {string} The validated URL
 */
function validateAndFormatUrl(url) {
    if (!url) return "";
    
    // Add protocol if missing
    if (!/^https?:\/\//i.test(url)) {
        url = 'http://' + url;
    }
    
    return url;
}

/**
 * Parse existing link syntax from selection
 * @param {string} selection - The selected text
 * @returns {Object} Parsed link components
 */
function parseExistingLink(selection) {
    let parts;

    if (!toolbarObject.value.editor.isMarkdown) {
        // Parse Tiki wiki syntax: [url|label|relation]
        parts = selection.match(/\[(.*?)\|(.*?)\|(.*?)]/);
        if (!parts) {
            parts = selection.match(/\[(.*?)\|(.*?)]/);
        }
        if (!parts) {
            parts = selection.match(/\[(.*?)]/);
        }
    } else {
        // Parse Markdown syntax: [label](url)
        parts = selection.match(/\[(.*?)]\((.*?)\)/);
        if (parts) {
            const label = parts[1];
            parts[1] = parts[2]; // URL
            parts[2] = label;    // Label
        }
    }

    return parts;
}

function _shown() {
    const textArea = document.getElementById(toolbarObject.value.domElementId);
    if (!textArea) {
        console.warn('ExternalLink dialog: Text area not found');
        return;
    }

    const selection = getTASelection(textArea);
    const parts = parseExistingLink(selection);

    if (parts) {
        urlInput.value = parts[1] || "";
        labelInput.value = parts[2] || "";
        relationInput.value = parts[3] || "";
    } else {
        labelInput.value = toolbarObject.value.labelText != null ? toolbarObject.value.labelPage : selection;
        urlInput.value = toolbarObject.value.labelPage != null ? toolbarObject.value.labelPage : "";
        relationInput.value = "";
    }
}

function _insert() {
    let output = "";
    
    // Validate and format URL
    const validatedUrl = validateAndFormatUrl(urlInput.value);
    
    if (!validatedUrl) {
        console.warn('ExternalLink dialog: No URL provided');
        return "";
    }

    if (!toolbarObject.value.editor.isMarkdown) {
        // Tiki wiki syntax: [url|label|relation]
        output = "[" + validatedUrl;
        if (labelInput.value && labelInput.value !== validatedUrl) {
            output += "|" + labelInput.value;
            if (relationInput.value) {
                output += "|" + relationInput.value;
            }
        }
        output += "]";
    } else {
        // Markdown syntax
        if (toolbarObject.value.editor.isWysiwyg) {
            output = {
                linkText: labelInput.value || validatedUrl,
                linkUrl: validatedUrl
            };
            const result = tuiEditors[toolbarObject.value.domElementId].exec("addLink", output);
            return result;
        } else {
            output = `[${labelInput.value || validatedUrl}](${validatedUrl})`;
        }
    }
    
    if (output) {
        insertAt(toolbarObject.value.domElementId, output, false, false, true);
    }

    return output;
}

defineExpose({ execute: _insert, shown: _shown });
</script>

<template>
    <DialogInput 
        ref="tdgLabel" 
        v-model="labelInput" 
        label="Label" 
        class="mb-2" 
    />
    <DialogInput 
        ref="tdgUrl" 
        v-model="urlInput" 
        label="URL" 
        :required="true"
        class="mb-2" 
    />
    <div class="input-group mr-sm-2" v-if="! toolbarObject.editor.isMarkdown">
        <DialogInput 
            ref="tdgRelation" 
            v-model="relationInput" 
            label="Relation" 
            aria-describedby="external-relation-help"
        />
        <div 
            class="input-group-text" 
            data-bs-toggle="tooltip" 
            id="external-relation-help"
            title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages."
        >
            <span class="fa fa-circle-info" aria-hidden="true"></span>
        </div>
    </div>
</template>
