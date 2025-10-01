<template>
    <select class="form-select" :value="predicate.operator.operator_id" @change="handleChange">
        <option v-for="operator in predicate.target.$type.$operators" :key="operator.label"
            :value="operator.operator_id">{{ operator.label }}
        </option>
    </select>
</template>
<script setup>
defineOptions({ name: "PredicateOperators" });

defineProps({
    columns: {
        type: Object,
        required: true,
    },
    predicate: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["change"]);

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>