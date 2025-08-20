<script setup>
import DialogInput from "./DialogInput.vue";
import { onMounted, defineProps, defineExpose, ref, nextTick } from "vue";

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
const isCalendarContext = ref(false);
const autocompleteInitialized = ref(false);

onMounted(() => {
    $(labelInputElement.value.$el)
        .parents(".modal").first()
        .on("show.bs.modal", (event) => {
            _shown(event);
            $(this).find('[data-bs-toggle="tooltip"]').tooltip();
        });
});

/**
 * Check if we're in a calendar context
 * @returns {boolean} True if in calendar modal
 */
function checkCalendarContext() {
    const modal = document.querySelector('.modal.show');
    if (modal && modal.classList.contains('calendar-modal')) {
        isCalendarContext.value = true;
        console.info('WikiLink dialog: Calendar context detected');
        return true;
    }
    return false;
}

/**
 * Initialize autocomplete with error handling and retry mechanism
 */
async function initializeAutocomplete() {
    if (autocompleteInitialized.value || isCalendarContext.value) {
        return;
    }

    const maxRetries = 3;
    let retryCount = 0;

    const attemptInitialization = async () => {
        try {
            await nextTick();
            
            if (!pageInputElement.value?.$el) {
                throw new Error('Page input element not found');
            }

            // Check if jQuery Tiki is available
            if (typeof jqueryTiki === 'undefined' || !jqueryTiki.autocomplete) {
                throw new Error('jQuery Tiki autocomplete not available');
            }

            // Initialize autocomplete
            $(pageInputElement.value.$el).tiki("autocomplete", "pagename", {
                minLength: 1,
                delay: 100,
                select: function(event, ui) {
                    pageInput.value = ui.item.value;
                }
            });

            autocompleteInitialized.value = true;
            console.info('WikiLink dialog: Autocomplete initialized successfully');
        } catch (error) {
            retryCount++;
            console.warn(`WikiLink dialog: Autocomplete initialization failed (attempt ${retryCount}/${maxRetries}):`, error);
            
            if (retryCount < maxRetries) {
                setTimeout(attemptInitialization, 100 * retryCount);
            } else {
                console.error('WikiLink dialog: Failed to initialize autocomplete after', maxRetries, 'attempts');
            }
        }
    };

    await attemptInitialization();
}

function _shown() {
    // Check calendar context first
    checkCalendarContext();

    const textArea = document.getElementById(toolbarObject.value.domElementId);
    if (!textArea) {
        console.warn('WikiLink dialog: Text area not found');
        return;
    }

    const selection = getTASelection(textArea);

    // Parse existing wiki link syntax
    let parts = selection.match(/\((.*?)\((.*?)\|(.*?)\)\)/);
    if (!parts) {
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

    // Initialize autocomplete if not in calendar context
    if (!isCalendarContext.value) {
        initializeAutocomplete();
    }
}

function _insert() {
    let output = "";

    // Get the actual value from the input element
    const pageValue = pageInputElement.value?.$el?.value || pageInput.value;
    
    if (pageValue) {
        output += "(";
        if (relationInput.value) {
            output += relationInput.value;
        }
        output += `(${pageValue}`;
        if (labelInput.value && labelInput.value !== pageValue) {
            output += `|${labelInput.value}`;
        }
        output += "))";
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
        ref="labelInputElement" 
        v-model="labelInput" 
        label="Label" 
        class="mb-2" 
    />
    <DialogInput 
        ref="pageInputElement" 
        v-model="pageInput" 
        label="Page" 
        :required="true"
        class="mb-2" 
    />
    <div class="input-group input-group-sm">
        <DialogInput 
            ref="tdgRelation" 
            v-model="relationInput" 
            label="Semantic Relation" 
            aria-describedby="relation-help"
        />
        <span 
            class="input-group-text" 
            data-bs-toggle="tooltip" 
            id="relation-help"
            title="Going beyond Backlinks functionality, this allows some semantic relationships to be defined between wiki pages."
        >
            <span class="fa fa-circle-info" aria-hidden="true"></span>
        </span>
    </div>
</template>
