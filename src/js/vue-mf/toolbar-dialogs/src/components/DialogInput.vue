<script setup>
import { ref, defineProps, defineEmits, watch } from "vue";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: "",
    },
});

defineEmits(["update:modelValue"]);

const modelValue = ref("");

watch(
    () => props.modelValue,
    (nVal) => (modelValue.value = nVal),
    {
        immediate: true,
    }
);
</script>

<template>
    <input
        v-model="modelValue"
        class="form-control form-control-sm"
        type="text"
        :placeholder="label"
        @input="$emit('update:modelValue', $event.target.value)"
    />
</template>
