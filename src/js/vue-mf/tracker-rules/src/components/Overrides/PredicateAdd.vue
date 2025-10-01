<template>
    <button class="btn btn-outline-secondary btn-predicate-add" type="button">{{ isInAddCompoundMode ? '…' : '+'
        }}</button>
</template>

<script setup>
defineOptions({ name: "PredicateAdd" });

defineProps({
    isInAddCompoundMode: {
        type: Boolean,
        default: false,
    },
    predicate: {
        type: Object,
    },
})
</script>