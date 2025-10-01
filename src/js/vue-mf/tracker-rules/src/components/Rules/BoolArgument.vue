<template>
    <div>
        <select class="form-select" @change="handleChange" :value="value">
            <option value="1">true</option>
            <option value="0">false</option>
        </select>
    </div>
</template>

<script setup>
defineOptions({ name: "BoolArgument" });

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