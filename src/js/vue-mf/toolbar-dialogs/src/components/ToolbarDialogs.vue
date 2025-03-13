<script setup>
import { ref, useTemplateRef, onMounted, defineAsyncComponent } from "vue";
import BootstrapModal from "./BootstrapModal.vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
    syntax: {
        type: String,
        required: false,
    },
});

const toolbarObject = ref(props.toolbarObject);
// usefull to mount the dynamically imported component only when the modal is shown
const modalLoaded = ref(false);

const bootstrapModalRef = useTemplateRef('bootstrapModalElement');
const resolvedComponentRef = useTemplateRef('resolvedComponent');

// loading the components asynchronously on demand
const toolbarComponents = {
    link: defineAsyncComponent(() => import('./ExternalLink.vue')),
    tikilink: defineAsyncComponent(() => import('./WikiLink.vue')),
    table: defineAsyncComponent(() => import('./table/Table.vue')),
    find: defineAsyncComponent(() => import('./FindAndReplace.vue')),
    replace: defineAsyncComponent(() => import('./FindAndReplace.vue')),
}

function showModal() {
    const $modal = $(toolbarObject.value.modalElement);
    if ($modal.parent("body").length === 0) {
        // bootstrap modal divs need to be on the root of body to appear in front of the backdrop
        $modal.appendTo("body");
    }
    bootstrapModalRef.value.show();
}

function execute() {

    resolvedComponentRef.value?.execute();

    switch (toolbarObject.value.name) {
        case "find":
        case "replace":
            const tm = setTimeout(function () {
                document.getElementById(toolbarObject.value.domElementId).focus();
                clearTimeout(tm);
            }, 1000);
            return;
        default:
            bootstrapModalRef.value.close();
    }
}
onMounted(() => {
    toolbarObject.value.bootstrapModalRef = bootstrapModalRef;
    toolbarObject.value.modalElement = bootstrapModalRef.value.modalElement;
});
</script>

<template>
    <template v-if="toolbarObject.hasOwnProperty('iconname')">
        <a class="toolbar btn btn-sm px-2 tips bottom qt-inline" :title="toolbarObject.label" @click="showModal()">
            <span :class="'icon icon' + toolbarObject.iconname + ' fas fa-' + toolbarObject.iconname"></span>
        </a>
    </template>
    <span v-else @click="showModal()">{{ toolbarObject.labelText }}</span>
    <BootstrapModal 
        @shown="modalLoaded = true" 
        @hidden="modalLoaded = false" 
        ref="bootstrapModalElement" 
        :title="toolbarObject.label" 
        :size="toolbarObject.name === 'table' ? ' modal-lg' : ''">
        <template #body>
            <component v-if="modalLoaded" :is="toolbarComponents[toolbarObject.name]" ref="resolvedComponent" :toolbar-object="toolbarObject"></component>
        </template>
        <template #footer>
            <button class="btn btn-primary btn-sm" @click="execute()">Apply</button>
        </template>
    </BootstrapModal>
</template>

<style>
.toolbar-dialogs .single-spa-container {
    /* toolbar icons need to be inline */
    display: inline-block !important;
}
</style>
