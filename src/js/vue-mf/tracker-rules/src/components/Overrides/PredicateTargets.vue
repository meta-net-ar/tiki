<template>
    <el-select
        v-if="isElementPlusActive"
        v-model="modelValue"
        placeholder="Select"
        :teleported="false"
        @change="handleElementPlusChange"
    >
        <el-option v-for="target in columns.targets" :key="target.label" :label="target.label" :value="target.target_id" />
    </el-select>
    <select class="form-select" :value="predicate.target.target_id" @change="handleChange" v-else>
        <option v-for="target in columns.targets" :key="target.label" :value="target.target_id">{{ target.label }}
        </option>
    </select>
</template>
<script setup>
import { ref } from "vue";

defineOptions({ name: "PredicateTargets" });

const props = defineProps({
    columns: {
        type: Object,
        required: true,
    },
    predicate: {
        type: Object,
        required: true,
    },
})

const modelValue = ref(props.predicate.target.target_id);

const isElementPlusActive = ref(!!window.elementPlus);

const emit = defineEmits(["change"]);

const handleElementPlusChange = (value) => {
    emit("change", value);
};

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>