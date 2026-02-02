<template>
    <el-select
        v-if="isElementPlusActive"
        v-model="modelValue"
        placeholder="Select"
        @change="() => emit('change', modelValue)"
    >
        <el-option v-for="operator in predicate.target.$type.$operators" :key="operator.label" :label="operator.label" :value="operator.operator_id" />
    </el-select>
    <select class="form-select" :value="predicate.operator.operator_id" @change="handleChange" v-else>
        <option v-for="operator in predicate.target.$type.$operators" :key="operator.label"
            :value="operator.operator_id">{{ operator.label }}
        </option>
    </select>
</template>
<script setup>
import { ref } from "vue";

defineOptions({ name: "PredicateOperators" });

const props = defineProps({
    columns: {
        type: Object,
        required: true,
    },
    predicate: {
        type: Object,
        required: true,
    },
});

const modelValue = ref(props.predicate.operator.operator_id);

const isElementPlusActive = ref(!!window.elementPlus);

const emit = defineEmits(["change"]);

const handleChange = (event) => {
    emit("change", event.target.value);
};
</script>