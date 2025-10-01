<!--
    Field type 'w'
-->
<template>
    <select v-model="model"
        :id="field.ins_id"
        :name="field.html_name"
        class="form-select"
        :multiple="field.canHaveMultipleValues ? 'multiple' : null">
        <option value="" v-if="field.options_map.hideBlank != '1'"></option>
        <option v-for="(label, value) in possibleValues" :value="value">
            {{label}}
        </option>
    </select>
</template>

<script setup>
    import { computed } from 'vue'
    import store from '../../store'

    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })

    const possibleValues = computed(() => {
        let possibilities = {}
        store.getters.getRemoteLinkedItems(store.state.editedItem[props.field.trackerId].values, props.field).forEach((item) => {
            const key = item.offlineAutoId ? item.offlineAutoId : item.itemId
            possibilities[key] = props.field.possibilities[key]
        })
        return possibilities
    })
</script>

<script>
    export default {
        name: "DynamicList",
    };
</script>
