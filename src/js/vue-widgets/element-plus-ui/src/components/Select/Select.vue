<script setup>
import { ref, watch, computed, onMounted, watchEffect } from 'vue';
import Sortable from "sortablejs";
import { sortOptions } from '../../helpers/select/sortable';
import ConfigWrapper from '../ConfigWrapper.vue';
import { usePropParsers } from '../../composables/usePropParsers'
const { normalize, parseValue } = usePropParsers()



defineOptions({ inheritAttrs: false })

const props = defineProps(['options', 'placeholder', 'emitValueChange', 'value', 'multiple', 'isInvalid', 'max', 'clearable', 'collapseTags', 'filterable', 'allowCreate', 'maxCollapseTags', 'ordering', 'group', 'language', 'size', 'remoteSourceUrl']);

const modelValue = ref(parseValue(props.value));

watch(() => props.value, (newValue) => {
    modelValue.value = parseValue(newValue);
});

const isInvalid = computed(() => normalize(props.isInvalid, false));
const multiple = computed(() => normalize(props.multiple, false))
const clearable = computed(() => normalize(props.clearable, false));
const collapseTags = computed(() => normalize(props.collapseTags, false));
const filterable = computed(() =>
    Boolean(props.remoteSourceUrl) || normalize(props.filterable, false)
);
const allowCreate = computed(() => normalize(props.allowCreate, false));
const grouped = computed(() => normalize(props.group, false));
const getOptionsProp = computed(() => {
    const parsedOptions = parseValue(props.options) || [];
    // Ensure we have an array to work with
    const optionsArray = Array.isArray(parsedOptions) ? parsedOptions : [];
    return optionsArray.reduce((acc, item) => {
        if (!grouped.value) {
            acc.push(item);
            return acc;
        }
        const group = acc.find(group => group.label === item.group);
        if (group) {
            group.options.push({
                label: item.label,
                value: item.value,
            });
        } else {
            acc.push({
                label: item.group,
                options: [{
                    label: item.label,
                    value: item.value,
                }],
            });
        }
        return acc;
    }, []);
});
const options = ref([]);
const wrapperRef = ref(null);
const remoteSearchActive = ref(false);
const selectionMadeInRemote = ref(false);

const focusInput = () => {
    // .el-select__input lives inside el-select's shadow DOM — querySelector won't find it.
    // Focusing el-select itself delegates focus to the inner input via the custom element.
    (wrapperRef.value?.querySelector('.el-select__input')
        ?? wrapperRef.value?.querySelector('el-select'))
        ?.focus();
};

// In remote mode, option clicks inside shadow DOM cause two problems:
// 1. mousedown moves focus to the popper div → El Plus afterBlur fires → dropdown closes.
//    Fix: capture-phase mousedown preventDefault stops the browser focus change.
// 2. mousedown/mouseup bubble to document retargeted as EL-SELECT → El Plus's
//    document-level click-outside handler (using startClick/mouseup) fires → dropdown closes.
//    Fix: direct addEventListener (not Vue template binding) stops propagation reliably in shadow DOM.
const isRemoteOption = (event) => props.remoteSourceUrl && event.target.closest('[role="option"]');

const handleValueChange = (value) => {
    // check if the checkbox with id "intertrans-active" is checked
    const checkbox = document.getElementById("intertrans-active");
    if (!checkbox || !checkbox.checked) {
        props.emitValueChange({
            value,
        });
    }

    if (typeof window.vueselectIntertransHandler === "function") {
        const option = options.value.find(opt => opt.value === value);
        window.vueselectIntertransHandler(option); // on envoie le label
    }

    if (props.remoteSourceUrl && !multiple.value) {
        // Single mode only: dropdown closes after selection, handleVisibleChange refocuses.
        // Multiple mode: reserve-keyword keeps the query and dropdown open; no flag needed.
        selectionMadeInRemote.value = true;
    }
};

const handleVisibleChange = (isVisible) => {
    if (!isVisible) {
        // Dropdown closed: allow watchEffect to resync static options on next open.
        remoteSearchActive.value = false;
        // Single mode: refocus input after selection closes the dropdown.
        if (selectionMadeInRemote.value) {
            selectionMadeInRemote.value = false;
            focusInput();
        }
    }
};

const remoteMethod = async (query) => {
    if (!query) {
        // Do nothing: any options/state change here triggers an async Vue re-render that
        // fires during the next keystroke and steals focus from the inner input.
        // remoteSearchActive stays true for the whole session; it resets when the dropdown closes.
        return;
    }

    remoteSearchActive.value = true;
    try {
        const url = new URL(props.remoteSourceUrl);
        url.searchParams.append("q", query);
        const response = await fetch(url.href, {
            headers: {
                Accept: "application/json",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        const loadedOptions = data.map(item => (typeof item === "string" ? { value: item, label: item } : item));
        const newOptions = [
            ...options.value.filter(item => modelValue.value?.includes(item.value)),
            ...loadedOptions,
        ];
        options.value = newOptions;
    } catch (error) {
        console.error('Error loading remote options:', error);
    }
};

onMounted(() => {
    if (wrapperRef.value) {
        // Capture-phase: prevent focus leaving input when clicking an option (fixes blur → dropdown close).
        wrapperRef.value.addEventListener('mousedown', (e) => {
            if (isRemoteOption(e)) e.preventDefault();
        }, true);

        // Bubble-phase: stop mousedown/mouseup reaching El Plus's document-level click-outside handler.
        // Vue template @mouseup binding doesn't reliably stop propagation in shadow DOM, so we use
        // addEventListener directly — matching what evaluate-injected listeners confirmed works.
        const stopProp = (e) => { if (isRemoteOption(e)) e.stopPropagation(); };
        wrapperRef.value.addEventListener('mousedown', stopProp);
        wrapperRef.value.addEventListener('mouseup', stopProp);

        // el-select is a custom element (shadow DOM host). document.activeElement returns the shadow
        // host, so document.activeElement.parentElement is the Tiki container div in the light DOM
        // — NOT wrapperRef (which lives inside the shadow DOM).
        // El Plus's z() handler calls pn(tikaContainer) → tikaContainer.focus(), stealing focus.
        // Fix: override .focus() on that outer container div so pn()'s call redirects to the inner
        // input. Applied unconditionally: remoteSourceUrl may not be set yet when onMounted fires
        // (custom element props arrive async via the elements/select.js watch).
        const tikaContainer = wrapperRef.value.getRootNode()?.host?.parentElement;
        if (tikaContainer instanceof HTMLElement) {
            tikaContainer.focus = () => {
                wrapperRef.value.querySelector('.el-select__input')?.focus();
            };
        }
    }

    try {
        const orderingConfig = props.ordering ? JSON.parse(props.ordering) : null;
        if (orderingConfig && multiple.value && wrapperRef.value) {
            const selectionElement = wrapperRef.value.querySelector('.el-select__selection');
            if (selectionElement) {
                new Sortable(selectionElement, {
                    animation: 150,
                    onSort: () => sortOptions(wrapperRef.value, JSON.parse(props.options)),
                });
            }
        }
    } catch (error) {
        console.warn('Invalid ordering configuration:', error);
    }
})

watchEffect(() => {
    // In remote search mode, El Plus owns options.value — don't override it.
    // The reset happens when remoteMethod is called with an empty query.
    // so that clearing the input or selecting an item never triggers a re-render here.
    if (!remoteSearchActive.value) {
        options.value = getOptionsProp.value;
    }
});
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    SELECT_WRAPPER: `select-wrapper-${uniqueId}`,
    SELECT_ELEMENT: `select-element-${uniqueId}`,
    SELECT_OPTION: `select-option-${uniqueId}`,
    SELECT_OPTION_GROUP: `select-option-group-${uniqueId}`,
};
</script>

<template>
    <ConfigWrapper :language="language">
        <div :class="{ 'invalid': isInvalid }" :data-testid="DATA_TEST_ID.SELECT_WRAPPER" ref="wrapperRef">
            <el-select v-model="modelValue" :multiple="multiple" :filterable="filterable" :allow-create="allowCreate"
                default-first-option :reserve-keyword="Boolean(remoteSourceUrl) && multiple" :placeholder="placeholder" :teleported="false" :automatic-dropdown="true"
                @change="handleValueChange" @visible-change="handleVisibleChange" :multiple-limit="parseInt(max ?? 0, 10)" :clearable="clearable"
                :collapse-tags="collapseTags" :max-collapse-tags="parseInt(maxCollapseTags ?? 0, 10)" :size="size"
                :data-testid="DATA_TEST_ID.SELECT_ELEMENT" :remote-method="remoteMethod"
                :remote="Boolean(remoteSourceUrl)" v-bind="$attrs" :empty-values="[null, undefined]">
                <template v-if="grouped">
                    <el-option-group v-for="group in options" :key="group.label" :label="group.label"
                        :data-testid="DATA_TEST_ID.SELECT_OPTION_GROUP">
                        <el-option v-for="item in group.options" :key="item.value" :label="item.label"
                            :value="item.value" :disabled="item.disabled" :data-testid="DATA_TEST_ID.SELECT_OPTION" />
                    </el-option-group>
                </template>
                <template v-else>
                    <el-option v-for="item in options" :key="item.value" :label="item.label" :value="item.value"
                        :disabled="item.disabled" :data-testid="DATA_TEST_ID.SELECT_OPTION" />
                </template>
            </el-select>
        </div>
    </ConfigWrapper>
</template>