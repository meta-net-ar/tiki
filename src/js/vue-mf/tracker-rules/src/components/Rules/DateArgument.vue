<template>
    <div>
        <el-date-picker
            v-if="isElementPlusActive"
            v-model="modelValue"
            type="date"
            placeholder="Select date"
            :teleported="false"
            @change="handleElementPlusChange"
        />
        <input class="form-control" type="date" @change="handleChange" :value="value" v-else />
    </div>
</template>

<script setup>
import { ref } from "vue";

defineOptions({ name: "DateArgument" });

const props = defineProps({
    value: {
        type: null,
        required: true,
    },
});

const modelValue = ref(props.value);

const isElementPlusActive = ref(!!window.elementPlus);

const emit = defineEmits(["change"]);

const handleElementPlusChange = (value) => {
    emit("change", value);
};

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>