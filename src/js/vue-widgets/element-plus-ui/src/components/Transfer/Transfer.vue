<script setup>
import { ref, onMounted, computed } from 'vue';
import { Menu, Edit, Delete } from "@element-plus/icons-vue";
import Sortable from "sortablejs";
import ConfigWrapper from '../ConfigWrapper.vue';

const props = defineProps(['data', 'fieldName', 'filterable', 'defaultValue', 'sourceListTitle', 'targetListTitle', 'filterPlaceholder', 'ordering', 'minItems', 'maxItems', 'helperText', 'emitValueChange', 'isInvalid', 'language', 'showEdit', '_emit']);
const data = typeof props.data === 'string' ? JSON.parse(props.data) : props.data;
const defaultValue = typeof props.defaultValue === 'string' ? JSON.parse(props.defaultValue) : props.defaultValue;

const selected = ref(defaultValue ? [...defaultValue]: []);

const arrayData = Object.entries(data).map(([key, value]) => ({ key, label: value }));

const elTransferContainer = ref(null);

const infoMessage = computed(() => {
    if (props.helperText) return props.helperText;
    if (props.minItems > 1 && props.maxItems) return `A minimum of ${props.minItems} items and a maximum of ${props.maxItems} items are allowed`;
    if (props.minItems > 1) return `A minimum of ${props.minItems} items is allowed`;
    if (props.maxItems) return `A maximum of ${props.maxItems} items is allowed`;
});

const isInvalid = computed(() => props.isInvalid ? JSON.parse(props.isInvalid): false);
const showEdit = computed(() => props.showEdit ? JSON.parse(props.showEdit): false);

const handleValueChange = (value, direction) => {
    selected.value = value;
    props.emitValueChange({
        value,
        direction,
    });
};

const handleEdit = (value) => {
    props._emit('edit', { value });
};

onMounted(() => {
    const list = elTransferContainer.value.querySelectorAll(".el-transfer-panel__list")[1];
    new Sortable(list, {
        animation: 150,
        group: props.fieldName,
        sorting: JSON.parse(props.ordering),
        onAdd: (event) => {
            const item = event.item.querySelector(".el-checkbox__label > span").dataset.key;
            selected.value.push(item);
            props.emitValueChange({
                value: selected.value
            });
            // remove item to prevent duplication
            event.item.remove();
        },
        onUpdate: () => {
            if (!JSON.parse(props.ordering)) return;

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

    new Sortable(elTransferContainer.value.querySelectorAll(".el-transfer-panel__list")[0], {
        animation: 150,
        group: props.fieldName,
        sorting: false,
        onAdd: (event) => {
            const item = event.item.querySelector(".el-checkbox__label > span").dataset.key;
            const index = selected.value.indexOf(item);
            selected.value.splice(index, 1);
            props.emitValueChange({
                value: selected.value
            });
            // remove item to prevent duplication
            event.item.remove();
        },
    });
});

const translateFn = window.tr;
</script>

<script>
const uniqueId = new Date().getTime();
export const DATA_TEST_ID = {
    HIDDEN_SELECT: `hidden-select-${uniqueId}`,
    TRANSFER_CONTAINER: `transfer-container-${uniqueId}`,
    HELPER_TEXT: `helper-text-${uniqueId}`,
    EDIT_ITEM_BUTTON: `right-footer-edit-${uniqueId}`,
};
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
            <el-alert type="info" show-icon class="mb-2">{{ translateFn('You can drag and drop items between the lists.') }}</el-alert>
            <div ref="elTransferContainer" :class="['transfer-container', { 'invalid': isInvalid }]" :data-testid="DATA_TEST_ID.TRANSFER_CONTAINER">
                <el-transfer v-model="selected" :data="arrayData" :filterable="JSON.parse(filterable)" :titles="[sourceListTitle, targetListTitle]" :filter-placeholder="filterPlaceholder" :target-order="JSON.parse(ordering) ? 'push': 'original'" @change="handleValueChange">
                    <template #default="{ option }">
                        <span :data-key="option.key">{{ option.label }}</span>
                        <el-button type="primary" :text="true" :icon="Edit" v-if="showEdit && selected.includes(option.key)" @click="() => handleEdit(option.key)" :data-testid="DATA_TEST_ID.EDIT_ITEM_BUTTON"></el-button>
                    </template>
                </el-transfer>
            </div>
        </div>
    </ConfigWrapper>
</template>
