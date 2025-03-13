<script setup>
import { ref, onMounted, computed } from 'vue';
import { Menu } from "@element-plus/icons-vue";
import Sortable from "sortablejs";
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['data', 'fieldName', 'filterable', 'defaultValue', 'sourceListTitle', 'targetListTitle', 'filterPlaceholder', 'ordering', 'minItems', 'maxItems', 'helperText', 'emitValueChange', 'isInvalid', 'language']);
const data = typeof props.data === 'string' ? JSON.parse(props.data) : props.data;
const defaultValue = typeof props.defaultValue === 'string' ? JSON.parse(props.defaultValue) : props.defaultValue;

const selected = ref(defaultValue ?? []);

const arrayData = Object.entries(data).map(([key, value]) => ({ key, label: value }));

const elTransferContainer = ref(null);

const infoMessage = computed(() => {
    if (props.helperText) return props.helperText;
    if (props.minItems > 1 && props.maxItems) return `A minimum of ${props.minItems} items and a maximum of ${props.maxItems} items are allowed`;
    if (props.minItems > 1) return `A minimum of ${props.minItems} items is allowed`;
    if (props.maxItems) return `A maximum of ${props.maxItems} items is allowed`;
});

const isInvalid = computed(() => props.isInvalid ? JSON.parse(props.isInvalid): false);

const handleValueChange = (value, direction) => {
    selected.value = value;
    props.emitValueChange({
        value,
        direction,
    });
};

onMounted(() => {
    if(elTransferContainer.value && JSON.parse(props.ordering)) {
        const list = elTransferContainer.value.querySelectorAll(".el-transfer-panel__list")[1];
        new Sortable(list, {
            animation: 150,
            handle: '.' + DRAG_HANDLER_CLASS,
            onSort: () => {
                const items = list.querySelectorAll(".el-transfer-panel__item");
                const sorted = [];
                items.forEach((item) => {
                    const value = item.querySelector(".el-checkbox__label > span").dataset.key;
                    sorted.push(value);
                });
                selected.value = sorted;
                props.emitValueChange({
                    value: sorted
                })
            },
        });
    
    }
});
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    HIDDEN_SELECT: `hidden-select-${uniqueId}`,
    TRANSFER_CONTAINER: `transfer-container-${uniqueId}`,
    HELPER_TEXT: `helper-text-${uniqueId}`,
};
export const DRAG_HANDLER_CLASS = "handle-drag";
</script>

<template>
    <ConfigWrapper :language="language">
        <div>
            <select multiple aria-hidden="true" :name="fieldName" style="display: none;" :data-testid="DATA_TEST_ID['HIDDEN_SELECT']">
                <option v-for="key in selected" :value="key" :key="key" selected></option>
            </select>
            <el-alert :type="isInvalid ? 'error': 'info'" show-icon :closable="false" class="mb-2" v-if="infoMessage">
                <p :data-testid="DATA_TEST_ID.HELPER_TEXT">{{ infoMessage }}</p>
            </el-alert>
            <div ref="elTransferContainer" :class="['transfer-container', { 'invalid': isInvalid }]" :data-testid="DATA_TEST_ID.TRANSFER_CONTAINER">
                <el-transfer v-model="selected" :data="arrayData" :filterable="JSON.parse(filterable)" :titles="[sourceListTitle, targetListTitle]" :filter-placeholder="filterPlaceholder" :target-order="JSON.parse(ordering) ? 'push': 'original'" @change="handleValueChange">
                    <template #default="{ option }">
                        <el-button v-if="selected.includes(option.key) && JSON.parse(ordering)" :class="DRAG_HANDLER_CLASS" :icon="Menu" size="small" link /> <span :data-key="option.key">{{ option.label }}</span>
                    </template>
                </el-transfer>
            </div>
        </div>
    </ConfigWrapper>
</template>
