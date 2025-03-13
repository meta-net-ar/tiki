<script setup>
import { ref, watch } from "vue";

defineProps({
    label: {
        type: String,
        required: true,
    },
});

const model = defineModel()

</script>

<template>
    <input
        v-model="model"
        class="form-control form-control-sm"
        type="text"
        :placeholder="label"
    />
</template>
