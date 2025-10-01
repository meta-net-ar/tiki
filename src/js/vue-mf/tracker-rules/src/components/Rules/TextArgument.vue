<template>
    <div>
        <input class="form-control" type="text" @change="$emit('change', $event.target.value)" :value="value">
    </div>
</template>

<script setup>
defineOptions({ name: "TextArgument" });

defineEmits(["change"])

defineProps({
    value: {
        type: null,
        required: true,
    },
});
</script>
