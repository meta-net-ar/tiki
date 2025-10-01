<script setup>
import { ref } from 'vue';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['min', 'max', 'step', 'value', 'showStops', 'showInput', 'range', 'vertical', 'language', '_emit', '_expose']);
const min = props.min ? JSON.parse(props.min) : 0;
const max = props.max ? JSON.parse(props.max) : 100;
const step = props.step ? JSON.parse(props.step) : 1;
const range = props.range === 'true';

const modelValue = ref(props.value ? (range ? props.value.split(",").map(v => parseInt(v)): parseInt(props.value)) : min);

props._expose({ value: modelValue });

const handleChange = (value) => {
    props._emit("change", value);
};

</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    SLIDER_ELEMENT: `slider-element-${uniqueId}`,
};
</script>

<template>
    <ConfigWrapper :language="language">
        <el-slider
            :min="min"
            :max="max"
            :step="step"
            :range
            :vertical="vertical === 'true'"
            :show-stops="showStops === 'true'"
            :show-input="showInput === 'true'"
            v-model="modelValue"
            @change="handleChange"
            :data-testid="DATA_TEST_ID.SLIDER_ELEMENT"
        />
    </ConfigWrapper>
</template>
