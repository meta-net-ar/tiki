<script setup>
/*
 * Wrapper for bootstrap 5 Modals
 * Largely from https://stackoverflow.com/a/71461086/2459703
 */

import { Modal } from "bootstrap";
import { useId, useTemplateRef, onMounted } from "vue";

defineProps({
    title: {
        type: String,
        default: "<<Title goes here>>",
    },
    size: {
        type: String,
        default: "",
    }
});

const emit = defineEmits(["shown", "hidden"]);

let theModal = null;

const modalElement = useTemplateRef('modal');

const modalId = useId();

function _shown(e) {
    //console.log("Can't seem to get this to work in here: " + e);
}

function _show() {
    theModal.show();
}

function _close() {
    theModal.hide();
}


onMounted(() => {
    // initialise the bootstrap modal with focus _enabled_
    // prior to boostrap 5.3.3 the modal close button got the focus after each dialog's "exec"
    // so we needed focus: false - this seems to be fixed elsewhere now?
    theModal = new Modal(modalElement.value, { focus: true })

    $(modalElement.value)
    .on("show.bs.modal", () => emit("shown"))
    .on("hidden.bs.modal", () => emit("hidden"));
});

defineExpose({ show: _show, shown: _shown, close: _close, modalElement });
</script>

<template>
    <div ref="modal" :id="modalId" class="modal modal-sm fade" tabIndex="-1" :aria-labelledby="`${modalId}-title`" aria-hidden="true">
        <div :class="'modal-dialog' + size">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 :id="`${modalId}-title`" class="modal-title">{{ title }}</h6>
                    <button type="button" class="btn btn-close btn-sm" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <slot name="body" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <slot name="footer"></slot>
                </div>
            </div>
        </div>
    </div>
</template>
