<template>
    <div>
        <el-select
            v-if="isElementPlusActive"
            v-model="modelValue"
            placeholder="Select"
            :teleported="false"
            @change="handleElementPlusChange"
        >
            <el-option label="true" value="1" />
            <el-option label="false" value="0" />
        </el-select>
        <select class="form-select" @change="handleChange" :value="value" v-else>
            <option value="1">true</option>
            <option value="0">false</option>
        </select>
    </div>
</template>

<script setup>
import { ref } from "vue";

defineOptions({ name: "BoolArgument" });

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