<script setup>
import { onMounted, ref } from 'vue';
import { fetchSuggestions } from '../../helpers/autocomplete/remote';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['value', 'remoteSourceUrl', 'sourceList', 'emitCustomEvent', 'placeholder', 'valueKey', 'language']);

const valueKey = props.valueKey || 'value';
const placeholder = props.placeholder || TEXT.INPUT_PLACEHOLDER;

const modelValue = ref(props.value);

const handleFetchSuggestions = (query, callback) => fetchSuggestions(query, callback, props.remoteSourceUrl, (props.sourceList ? JSON.parse(props.sourceList): []));

const handleSelect = (value) => {
    props.emitCustomEvent('select', value);
}

const handleInput = (value) => {
    props.emitCustomEvent('input', value);
}

const handlePressEnter = () => {
    props.emitCustomEvent('pressEnter', modelValue.value);
}

onMounted(() => {
    if (!props.remoteSourceUrl && !props.sourceList) {
        console.error(TEXT.ERROR_NO_REQUIRED_PROPS);
    }
})
</script>

<script>
export const TEXT = {
    ERROR_NO_REQUIRED_PROPS: "The Autocomplete component requires either a remoteSourceUrl or sourceList prop to be set.",
    INPUT_PLACEHOLDER: "Type to search...",
}

const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    AUTOCOMPLETE_ELEMENT: `autocomplete-element-${uniqueId}`,
}
</script>

<template>
    <ConfigWrapper :language="language">
        <el-autocomplete
            v-model="modelValue"
            :debounce="500"
            :trigger-on-focus="false"
            :fetch-suggestions="handleFetchSuggestions"
            :data-testid="DATA_TEST_ID.AUTOCOMPLETE_ELEMENT"
            :placeholder="placeholder"
            :value-key="valueKey"
            @select="handleSelect"
            @input="handleInput"
            @keyup.enter="handlePressEnter"
            clearable
            :teleported="false"
        >
        </el-autocomplete>
    </ConfigWrapper>
</template>