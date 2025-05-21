<script setup>
import { ref, watch, computed, onMounted, watchEffect } from 'vue';
import Sortable from "sortablejs";
import { sortOptions } from '../../helpers/select/sortable';
import ConfigWrapper from '../ConfigWrapper.vue';

defineOptions({ inheritAttrs: false })

const props = defineProps(['options', 'placeholder', 'emitValueChange', 'value', 'multiple', 'isInvalid', 'max', 'clearable', 'collapseTags', 'filterable', 'allowCreate', 'maxCollapseTags', 'ordering', 'group', 'language', 'size', 'remoteSourceUrl']);

const modelValue = ref(JSON.parse(props.value));

watch(() => props.value, (newValue) => {
    modelValue.value = JSON.parse(newValue);
});

const isInvalid = computed(() => props.isInvalid ? JSON.parse(props.isInvalid): false);

const clearable = props.clearable ? JSON.parse(props.clearable): false;
const collapseTags = props.collapseTags ? JSON.parse(props.collapseTags): false;
const filterable = Boolean(props.remoteSourceUrl) || (props.filterable ? JSON.parse(props.filterable): false);
const allowCreate = props.allowCreate ? JSON.parse(props.allowCreate): false;
const grouped = props.group ? JSON.parse(props.group): false;
const getOptionsProp = () => JSON.parse(props.options).reduce((acc, item) => {
    if (!grouped) {
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
const options = ref(getOptionsProp());
const wrapperRef = ref(null);

const handleValueChange = (value) => {
    props.emitValueChange({
        value,
    });
};

const remoteMethod = async (query) => {
    if (!query) return;
    
    const url = new URL(props.remoteSourceUrl);
    url.searchParams.append("q", query);
    const response = await fetch(url.href, {
        headers: {
        Accept: "application/json",
    },
    });
    const data = await response.json();
    const loadedOptions = data.map(item => (typeof item === "string" ? { value: item, label: item }: item));
    const newOptions = [
        ...options.value.filter(item => modelValue.value.includes(item.value)),
        ...loadedOptions,
    ];
    options.value = newOptions;
};

onMounted(() => {
    if (props.ordering && JSON.parse(props.ordering) && props.multiple) {
        if (wrapperRef.value) {
            new Sortable(wrapperRef.value.querySelector('.el-select__selection'), {
                animation: 150,
                onSort: () => sortOptions(wrapperRef.value, JSON.parse(props.options)),
            });
        }
    }
})

watchEffect(() => {
    options.value = getOptionsProp();
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
        <div 
            :class="{ 'invalid': isInvalid }"
            :data-testid="DATA_TEST_ID.SELECT_WRAPPER"
            ref="wrapperRef"
        >
            <el-select
                v-model="modelValue"
                :multiple="multiple"
                :filterable
                :allow-create
                default-first-option
                :reserve-keyword="false"
                :placeholder="placeholder"
                :teleported="false"
                @change="handleValueChange"
                :multiple-limit="parseInt(max ?? 0)" :clearable :collapse-tags
                :max-collapse-tags="parseInt(maxCollapseTags ?? 0)"
                :size="size"
                :data-testid="DATA_TEST_ID.SELECT_ELEMENT"
                :remote-method="remoteMethod"
                :remote="Boolean(remoteSourceUrl)"
            >
                <el-option-group 
                    v-if="grouped"
                    v-for="group in options"
                    :key="group.label"
                    :label="group.label"
                    :data-testid="DATA_TEST_ID.SELECT_OPTION_GROUP"
                >
                    <el-option 
                        v-for="item in group.options"
                        :key="item.value"
                        :label="item.label"
                        :value="item.value"
                        :disabled="item.disabled"
                        :data-testid="DATA_TEST_ID.SELECT_OPTION"
                    />
                </el-option-group>
                <el-option 
                    v-else
                    v-for="item in options"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                    :disabled="item.disabled"
                    :data-testid="DATA_TEST_ID.SELECT_OPTION"
                />
            </el-select>
        </div>
    </ConfigWrapper>
</template>