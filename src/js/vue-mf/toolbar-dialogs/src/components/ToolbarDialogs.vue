<script setup>
import { ref } from "vue";
import BootstrapModal from "./BootstrapModal.vue";
import ExternalLink from "./ExternalLink.vue";
import WikiLink from "./WikiLink.vue";
import TableBuilder from "./table/Table.vue";
import Find from "./FindAndReplace.vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
});

const toolbarObject = ref(props.toolbarObject);

// hopefully these refs could be automated somehow
const bootstrapModalRef = ref(null);
const link = ref(null);
const tikilink = ref(null);
const table = ref(null);
const find = ref(null);

toolbarObject.value.bootstrapModalRef = bootstrapModalRef;

function showModal() {
    // cheat with jQuery - get the DOM element from the "ref" object
    const $modal = $(bootstrapModalRef.value.$el);
    if ($modal.parent("body").length === 0) {
        // bootstrap modal divs need to be on the root of body to appear in front of the backdrop
        $modal.appendTo("body");
    }
    bootstrapModalRef.value.show();
}

function execute() {

    switch (toolbarObject.value.name) {
        case "link":
            link.value.execute();
            break;
        case "tikilink":
            tikilink.value.execute();
            break;
        case "table":
            table.value.execute();
            break;
        case "find":
        case "replace":
            find.value.execute();
            break;
    }
    switch (toolbarObject.value.name) {
        case "find":
        case "replace":
            setTimeout(function () {
                document.getElementById(toolbarObject.value.domElementId).focus();
            }, 1000);
            return;
        default:
            bootstrapModalRef.value.close();
    }
}
</script>

<template>
    <template v-if="toolbarObject.hasOwnProperty('iconname')">
        <a class="toolbar btn btn-sm px-2 tips bottom qt-inline" :title="toolbarObject.label" @click="showModal()">
            <span :class="'icon icon' + toolbarObject.iconname + ' fas fa-' + toolbarObject.iconname"></span>
        </a>
    </template>
    <span v-else @click="showModal()">{{ toolbarObject.labelText }}</span>
    <BootstrapModal ref="bootstrapModalRef" :title="toolbarObject.label" :size="toolbarObject.name === 'table' ? ' modal-lg' : ''">
        <template #body>
            <ExternalLink v-if="toolbarObject.name === 'link'" ref="link" :toolbar-object="toolbarObject" />
            <WikiLink v-if="toolbarObject.name === 'tikilink'" ref="tikilink" :toolbar-object="toolbarObject" />
            <TableBuilder v-if="toolbarObject.name === 'table'" ref="table" :toolbar-object="toolbarObject" />
            <Find v-if="toolbarObject.name === 'find' || toolbarObject.name === 'replace'" ref="find" :toolbar-object="toolbarObject" />
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
