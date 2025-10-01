<template>
    <select class="form-select" :value="predicate.logic.logicalType_id" @change="handleChange">
        <option v-for="logicalType in columns.logicalTypes" :key="logicalType.label"
            :value="logicalType.logicalType_id">
            {{ logicalType.label }}
        </option>
    </select>
</template>

<script setup>
defineOptions({ name: "PredicateLogicalTypes" });

defineProps({
    predicate: {
        type: Object,
        required: true,
    },
    columns: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["change"]);

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>