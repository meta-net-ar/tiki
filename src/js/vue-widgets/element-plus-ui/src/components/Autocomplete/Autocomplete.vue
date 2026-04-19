<script setup>
import { onMounted, ref, watch } from 'vue';
import { fetchSuggestions } from '../../helpers/autocomplete/remote';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['_expose', 'value', 'remoteSourceUrl', 'sourceList', 'emitCustomEvent', 'placeholder', 'valueKey', 'language']);

const valueKey = props.valueKey || 'value';
const placeholder = props.placeholder || TEXT.INPUT_PLACEHOLDER;
const shouldRefocusOnBlur = ref(false);

const modelValue = ref(props.value);
const autocompleteRef = ref(null);
const setValue = (val) => {
    modelValue.value = val;
};

// This ensures that if jQuery changes the 'value' prop/attribute, Vue reacts
watch(
    () => props.value,
    (newVal) => {
        modelValue.value = newVal;
    }
);

props._expose({
    value: modelValue,
    setValue,
});

const handleFetchSuggestions = (query, callback) => {
    const wrappedCallback = (results) => {
        callback(results);

        if (!results || results.length === 0) {
             // If there are no results, set a flag indicating that the next blur event is likely programmatic and should be counteracted by a refocus.
            shouldRefocusOnBlur.value = true;
        }
    };
    fetchSuggestions(query, wrappedCallback, props.remoteSourceUrl, (props.sourceList ? JSON.parse(props.sourceList): []));
}

const handleBlur = () => {
    // Refocuses the input if the blur was triggered programmatically by a "no results" event.
    if (shouldRefocusOnBlur.value) {
        autocompleteRef.value?.inputRef?.focus();
        shouldRefocusOnBlur.value = false;
    }
};

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
            ref="autocompleteRef"
            v-model="modelValue"
            :debounce="500"
            :trigger-on-focus="false"
            :fetch-suggestions="handleFetchSuggestions"
            :data-testid="DATA_TEST_ID.AUTOCOMPLETE_ELEMENT"
            :placeholder="placeholder"
            :value-key="valueKey"
            :highlight-first-item="true"
            @select="handleSelect"
            @input="handleInput"
            @keyup.enter="handlePressEnter"
            @blur="handleBlur"
            clearable
            :teleported="false"
        >
        </el-autocomplete>
    </ConfigWrapper>
</template>