<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { fetchSuggestions } from '../../helpers/autocomplete/remote';
import { getSearchDelay } from '../../helpers/autocomplete/delay';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps([
    '_expose',
    'value',
    'remoteSourceUrl',
    'sourceList',
    'emitCustomEvent',
    'placeholder',
    'valueKey',
    'language',
    'transformResultFn',
    'remoteQueryKey',
    'minChars',
]);

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

let pendingSearch = null;
let pendingRequest = null;
// Used to ignore responses for older queries
let searchSequence = 0;

// Cache recent results to avoid repeat requests
const CACHE_SIZE = 30;
const CACHE_TTL = 60 * 1000;
const resultCache = new Map();

const readCache = (key) => {
    const entry = resultCache.get(key);
    if (!entry) {
        return null;
    }
    if (Date.now() - entry.time > CACHE_TTL) {
        resultCache.delete(key);
        return null;
    }
    return entry.results;
};

const writeCache = (key, results) => {
    resultCache.delete(key);
    resultCache.set(key, { results, time: Date.now() });
    if (resultCache.size > CACHE_SIZE) {
        resultCache.delete(resultCache.keys().next().value);
    }
};

const cancelPendingSearch = () => {
    searchSequence++;
    if (pendingSearch) {
        clearTimeout(pendingSearch);
        pendingSearch = null;
    }
    if (pendingRequest) {
        pendingRequest.abort();
        pendingRequest = null;
    }
};

const handleFetchSuggestions = (query, callback) => {
    cancelPendingSearch();
    const sequence = searchSequence;

    const wrappedCallback = (results) => {
        if (sequence !== searchSequence) {
            return; // stale response
        }
        callback(props.transformResultFn ? window[props.transformResultFn](results) : results);

        if (!results || results.length === 0) {
             // If there are no results, set a flag indicating that the next blur event is likely programmatic and should be counteracted by a refocus.
            shouldRefocusOnBlur.value = true;
        }
    };
    const sourceList = props.sourceList ? JSON.parse(props.sourceList) : [];

    // Local lists need no delay
    if (sourceList.length) {
        fetchSuggestions(query, wrappedCallback, props.remoteSourceUrl, sourceList, props.remoteQueryKey);
        return;
    }

    // Wait longer for short queries and skip those below minChars
    const trimmedQuery = (query || '').trim();
    const delay = getSearchDelay(trimmedQuery.length, Number(props.minChars) || 1);
    if (delay === null) {
        callback([]);
        return;
    }

    const cached = readCache(trimmedQuery);
    if (cached) {
        wrappedCallback(cached);
        return;
    }

    pendingSearch = setTimeout(() => {
        pendingSearch = null;
        const controller = new AbortController();
        pendingRequest = controller;
        fetchSuggestions(
            query,
            (results) => {
                if (pendingRequest === controller) {
                    pendingRequest = null;
                }
                if (!controller.signal.aborted && Array.isArray(results)) {
                    writeCache(trimmedQuery, results);
                }
                wrappedCallback(results);
            },
            props.remoteSourceUrl,
            sourceList,
            props.remoteQueryKey,
            controller.signal
        );
    }, delay);
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
    if (!value) {
        // Cancel any pending search when the input is cleared
        cancelPendingSearch();
    }
    props.emitCustomEvent('input', value);
}

const handlePressEnter = () => {
    props.emitCustomEvent('pressEnter', modelValue.value);
}

onBeforeUnmount(cancelPendingSearch);

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
            :debounce="0"
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
            @clear="cancelPendingSearch"
            clearable
            :teleported="false"
        >
            <template #default="{ item }">
                <span v-if="item.html" v-html="item.html"></span>
                <span v-else>{{ item[valueKey] }}</span>
            </template>
        </el-autocomplete>
    </ConfigWrapper>
</template>