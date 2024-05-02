<script>
export default {
    name: "App",
};
</script>
<script setup>
import ToolbarDialogs from "./components/ToolbarDialogs.vue";
import { ref } from "vue";

const props = defineProps({
    customProps: {
        type: Object,
        required: true,
    },
});

// console.log("toolbarObject: " + props.customProps.toolbarObject);
const toolbarObject = ref(props.customProps.toolbarObject);
const syntax = ref(props.customProps.syntax);
</script>

<template>
    <ToolbarDialogs :toolbar-object="toolbarObject" :syntax="syntax" />
</template>
