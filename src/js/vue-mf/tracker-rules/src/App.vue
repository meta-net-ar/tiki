<script setup>
import TrackerRules from "./components/TrackerRules.vue";

defineProps({
    customProps: {
        type: Object,
        required: true,
    },
});

</script>

<template>
    <TrackerRules :tracker-rules-object="customProps.trackerRulesObject" />
</template>
