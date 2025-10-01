<template>
    <select class="form-select" :value="predicate.target.target_id" @change="handleChange">
        <option v-for="target in columns.targets" :key="target.label" :value="target.target_id">{{ target.label }}
        </option>
    </select>
</template>
<script setup>
defineOptions({ name: "PredicateTargets" });

defineProps({
    columns: {
        type: Object,
        required: true,
    },
    predicate: {
        type: Object,
        required: true,
    },
})
const emit = defineEmits(["change"]);

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>