<template>
    <el-select
        v-if="isElementPlusActive"
        v-model="modelValue"
        placeholder="Select"
        :teleported="false"
        @change="handleElementPlusChange"
    >
        <el-option v-for="logicalType in columns.logicalTypes" :key="logicalType.label" :label="logicalType.label" :value="logicalType.logicalType_id" />
    </el-select>
    <select class="form-select" :value="predicate.logic.logicalType_id" @change="handleChange" v-else>
        <option v-for="logicalType in columns.logicalTypes" :key="logicalType.label"
            :value="logicalType.logicalType_id">
            {{ logicalType.label }}
        </option>
    </select>
</template>

<script setup>
import { ref } from "vue";

defineOptions({ name: "PredicateLogicalTypes" });

const props = defineProps({
    predicate: {
        type: Object,
        required: true,
    },
    columns: {
        type: Object,
        required: true,
    },
});

const modelValue = ref(props.predicate.logic.logicalType_id);

const isElementPlusActive = ref(!!window.elementPlus);

const emit = defineEmits(["change"]);

const handleElementPlusChange = (value) => {
    emit("change", value);
};

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>