<script setup>
/*
 * Wrapper for bootstrap 5 Modals
 * Largely from https://stackoverflow.com/a/71461086/2459703
 */

import {Modal} from "bootstrap";
import {reactive, onMounted, ref} from "vue";

const state = reactive({
    theModal: null,
});

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
let modalElement = ref(null);

onMounted(() => {
    // initialise the bootstrap modal with focus disabled otherwise
    // the modal close button gets the focus after each dialog's "exec"
    state.theModal = new Modal(modalElement.value, { focus: false })
});

function _shown(e) {
    console.log("Can't seem to get this to work in here: " + e);
}

function _show() {
    state.theModal.show();
}

function _close() {
    state.theModal.hide();
}

defineExpose({ show: _show, shown: _shown, close: _close });
</script>

<template>
    <div ref="modalElement" class="modal modal-sm fade" tabIndex="-1" aria-labelledby="" aria-hidden="true">
        <div :class="'modal-dialog' + size">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 id="exampleModalLabel" class="modal-title">{{ title }}</h6>
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
