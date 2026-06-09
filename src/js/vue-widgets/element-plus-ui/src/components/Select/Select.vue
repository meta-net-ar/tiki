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
const normalizeBooleanAttribute = (value) => {
    const normalized = normalize(value, false);
    // HTML boolean attributes can be serialized as an empty string.
    return normalized === "" || Boolean(normalized);
}
const multiple = computed(() => normalizeBooleanAttribute(props.multiple))
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
};

const remoteMethod = async (query) => {
    if (!query) return;

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
    options.value = getOptionsProp.value;
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
                default-first-option :reserve-keyword="false" :placeholder="placeholder" :teleported="false" :automatic-dropdown="multiple"
                @change="handleValueChange" :multiple-limit="parseInt(max ?? 0, 10)" :clearable="clearable"
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
