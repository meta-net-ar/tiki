<script setup>
import { ref } from "vue";
import BootstrapModal from "./BootstrapModal.vue";
import ExternalLink from "./ExternalLink.vue";
import WikiLink from "./WikiLink.vue";
import TableBuilder from "./table/Table.vue";

const props = defineProps({
    toolbarObject: {
        type: Object,
        required: true,
    },
    syntax: {
        type: String,
        default: "",
    },
});

const toolbarObject = ref(props.toolbarObject);

// hopefully these refs could be automated somehow
const bootstrapModalRef = ref(null);
const link = ref(null);
const tikilink = ref(null);
const table = ref(null);

function showModal() {
    // cheat with jQuery - get the DOM element from the "ref" object
    const $modal = $(bootstrapModalRef.value.$el);
    if ($modal.parent("body").length === 0) {
        // bootstrap modal divs need to be on the root of body to appear in front of the backdrop
        $modal.appendTo("body");
    }
    bootstrapModalRef.value.show();
}

function save() {
    let syntax = "";

    switch (toolbarObject.value.name) {
        case "link":
            syntax = link.value.save();
            break;
        case "tikilink":
            syntax = tikilink.value.save();
            break;
        case "table":
            syntax = table.value.save();
            break;
    }
    bootstrapModalRef.value.close();

    // return not used yet
    return syntax;
}
</script>

<template>
    <a class="toolbar btn btn-sm px-2 tips bottom qt-inline" :title="toolbarObject.label" @click="showModal()">
        <span :class="'icon icon' + toolbarObject.iconname + ' fas fa-' + toolbarObject.iconname"></span>
    </a>
    <BootstrapModal ref="bootstrapModalRef" :title="toolbarObject.label" :size="toolbarObject.name === 'table' ? ' modal-lg' : ''">
        <template #body>
            <ExternalLink v-if="toolbarObject.name === 'link'" ref="link" :toolbar-object="toolbarObject" />
            <WikiLink v-if="toolbarObject.name === 'tikilink'" ref="tikilink" :toolbar-object="toolbarObject" />
            <TableBuilder v-if="toolbarObject.name === 'table'" ref="table" :toolbar-object="toolbarObject" />
        </template>
        <template #footer>
            <button class="btn btn-primary btn-sm" @click="save()">Apply</button>
        </template>
    </BootstrapModal>
</template>

<style>
.toolbar-dialogs .single-spa-container {
    /* toolbar icons need to be inline */
    display: inline-block !important;
}
</style>
