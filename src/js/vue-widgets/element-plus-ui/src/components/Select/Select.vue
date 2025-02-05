<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import Sortable from "sortablejs";
import { sortOptions } from '../../helpers/select/sortable';
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['options', 'placeholder', 'emitValueChange', 'value', 'multiple', 'isInvalid', 'max', 'clearable', 'collapseTags', 'filterable', 'allowCreate', 'maxCollapseTags', 'ordering', 'group', 'language']);

const modelValue = ref(JSON.parse(props.value));

watch(() => props.value, (newValue) => {
    modelValue.value = JSON.parse(newValue);
});

const isInvalid = computed(() => props.isInvalid ? JSON.parse(props.isInvalid): false);

const clearable = props.clearable ? JSON.parse(props.clearable): false;
const collapseTags = props.collapseTags ? JSON.parse(props.collapseTags): false;
const filterable = props.filterable ? JSON.parse(props.filterable): false;
const allowCreate = props.allowCreate ? JSON.parse(props.allowCreate): false;
const grouped = props.group ? JSON.parse(props.group): false;
const options = JSON.parse(props.options).reduce((acc, item) => {
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
const wrapperRef = ref(null);

const handleValueChange = (value) => {
    props.emitValueChange({
        value,
    });
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
                :data-testid="DATA_TEST_ID.SELECT_ELEMENT"
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
                    v-for="item in JSON.parse(props.options)"
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