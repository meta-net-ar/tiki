<script setup>
import DialogInput from "./DialogInput.vue";
import { onMounted, defineProps, defineExpose, ref } from "vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const toolbarObject = ref(props.toolbarObject);

const findInputElement = ref();
const findInput = ref("");
const replaceInputElement = ref();
const replaceInput = ref("");

const caseSensitiveCheck = ref(false);
const regexCheck = ref(false);
const replaceAllCheck = ref(false);

onMounted(() => {
    $(findInputElement.value.$el)
        .parents(".modal").first()
        .on("show.bs.modal", (event) => {
            _shown(event);
        });
});

function _shown(event) {
    const $modal = $(findInputElement.value.$el).parents(".modal").first();
    $modal.find('[data-bs-toggle="tooltip"]').tooltip();
    if (toolbarObject.value.name === "replace") {
        $modal.find(".btn.btn-primary").text(tr("Replace"));
    } else {
        $modal.find(".btn.btn-primary").text(tr("Find"));
    }

    const textArea = document.getElementById(toolbarObject.value.domElementId);
    findInput.value = getTASelection(textArea);
}

function _find() {
    if (findInput.value) {
        console.log("find: " + findInput.value);
        // do the finding

        const $textArea = $("#" + toolbarObject.value.domElementId);

        const $textareaEditor = syntaxHighlighter.get($textArea); //codemirror functionality
        if ($textareaEditor) {
            syntaxHighlighter.find($textareaEditor, findInput.val());
        } else {
            // plain text editor standard functionality

            let toFind, regexOptions, textAreaContent, re, pos = 0, matches;
            toFind = findInput.value;
            regexOptions = "";
            if (! caseSensitiveCheck.value) {
                regexOptions += "i";
            }
            if (! regexCheck.value) {
                // TODO find a js version of preg_quote, possibly https://locutus.io/php/pcre/preg_quote/
            }
            textAreaContent = $textArea.val();
            re = new RegExp(toFind, regexOptions);
            pos = getCaretPos($textArea.get(0));
            if (pos && pos < textAreaContent.length) {
                // find next
                matches = re.exec(textAreaContent.substring(pos));
            } else {
                pos = 0;
            }
            if (! matches) {
                matches = re.exec(textAreaContent);
                pos = 0;
            }
            if (matches) {
                if (toolbarObject.value.name === "replace") {
                    if (replaceAllCheck.value) {
                        regexOptions += "g";
                        re = new RegExp(toFind, regexOptions);
                        toolbarObject.value.bootstrapModalRef.close();
                    }
                    const scrollTop = $textArea.scrollTop();
                    $textArea.val(textAreaContent.replace(re, replaceInput.value));
                    // close the modal
                    setTimeout(function () {
                        setSelectionRange($textArea, matches.index + pos, matches.index + replaceInput.value.length + pos);
                    }, 100);
                } else {
                    setTimeout(function () {
                        setSelectionRange($textArea, matches.index + pos, matches.index + toFind.length + pos);
                    }, 100);
                }
            }
        }
    }

    return false;
}

function onEnter() {
    _find();
    // unfortunately a combination of the bootstrap modal and vue.js keeps the focus
    // so the selection doesn't show if iut's still open
    //toolbarObject.value.bootstrapModalRef.close()
}

defineExpose({ execute: _find, shown: _shown });
</script>

<template>
    <DialogInput ref="findInputElement" v-model="findInput" v-on:keyup.enter="onEnter" label="Find" class="mb-2"/>
    <DialogInput v-if="toolbarObject.name === 'replace'" ref="replaceInputElement" v-model="replaceInput" label="Replace" class="mb-2"/>
    <div class="form-check mr-sm-2">
        <input
                class="form-check-input"
                type="checkbox"
                @input="$emit('update:modelValue', $event.target.value)"
                id="replaceAllCheck"
                v-model="caseSensitiveCheck"
        />
        <label class="form-check-label" for="replaceAllCheck">
            Case sensitive
        </label>
    </div>
    <div v-if="toolbarObject.name === 'replace'" class="form-check mr-sm-2">
        <input
                class="form-check-input"
                type="checkbox"
                @input="$emit('update:modelValue', $event.target.value)"
                id="replaceAllCheck"
                v-model="replaceAllCheck"
        />
        <label class="form-check-label" for="replaceAllCheck">
            Replace all
        </label>
    </div>
<!--
    <div class="form-check mr-sm-2">
        <input
                class="form-check-input"
                type="checkbox"
                @input="$emit('update:modelValue', $event.target.value)"
                id="regexCheck"
                v-model="regexCheck"
        />
        <label class="form-check-label" for="regexCheck">
            Use regular expressions
        </label>
    </div>
-->
</template>
