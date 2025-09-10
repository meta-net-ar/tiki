<script setup>
import { ref, defineProps, defineEmits, watch, computed } from "vue";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    modelValue: {
        type: String,
        default: "",
    },
    required: {
        type: Boolean,
        default: false,
    },
    ariaDescribedBy: {
        type: String,
        default: "",
    },
});

defineEmits(["update:modelValue"]);

const modelValue = ref("");
const inputId = computed(() => `dialog-input-${Math.random().toString(36).substr(2, 9)}`);

watch(
    () => props.modelValue,
    (nVal) => (modelValue.value = nVal),
    {
        immediate: true,
    }
);
</script>

<template>
    <div class="dialog-input-wrapper">
        <label 
            :for="inputId" 
            class="form-label"
            :class="{ 'required': required }"
        >
            {{ label }}
        </label>
        <input
            :id="inputId"
            v-model="modelValue"
            class="form-control form-control-sm"
            type="text"
            :placeholder="label"
            :aria-required="required"
            :aria-describedby="ariaDescribedBy"
            :aria-label="label"
            @input="$emit('update:modelValue', $event.target.value)"
            @keydown.enter="$event.target.blur()"
        />
    </div>
</template>

<style scoped>
.dialog-input-wrapper {
    margin-bottom: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
    color: var(--bs-body-color);
}

.form-label.required::after {
    content: " *";
    color: var(--bs-danger);
}

input:focus {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    input {
        border-width: 2px;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    input {
        transition: none;
    }
}
</style>
