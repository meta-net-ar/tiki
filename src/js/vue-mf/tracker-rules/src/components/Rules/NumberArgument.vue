<template>
    <div>
        <input class="form-control" type="number" @change="$emit('change', $event.target.value)" :value="value">
    </div>
</template>

<script setup>
defineOptions({ name: "NumberArgument" });

defineEmits(["change"])

defineProps({
    value: {
        type: null,
        required: true,
    },
});
</script>