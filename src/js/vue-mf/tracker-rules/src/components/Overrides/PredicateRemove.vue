<template>
    <button class="btn btn-outline-secondary btn-predicate-remove" type="button" :disabled="disabled">-</button>
</template>

<script setup>
defineOptions({ name: "PredicateRemove" });

defineProps({
    disabled: {
        type: Boolean,
        default: false,
    },
    predicate: {
        type: Object,
    },
});
</script>