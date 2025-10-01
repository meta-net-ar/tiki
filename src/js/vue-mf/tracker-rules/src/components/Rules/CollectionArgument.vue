<template>
    <div>
        <input class="form-control" type="text" @change="handleChange" :value="value">
    </div>
</template>

<script setup>
defineOptions({ name: "CollectionArgument" });

defineProps({
    value: {
        type: null,
        required: true,
    },
});

const emit = defineEmits(["change"]);

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>
