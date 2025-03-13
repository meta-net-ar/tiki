<!--
    Field type: f
-->
<template>
    <el-date-picker
        :type="field.options_map.datetime == 'd' ? 'date' : 'datetime'"
        :language="language"
        :id="field.ins_id"
    ></el-date-picker>
</template>

<script setup>
    import { computed } from 'vue'
    import store from '../../store';

    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })
    const emit = defineEmits(['input'])

    const language = computed(() => store.getters.getPref('language'))

    handleDatePicker(`#${props.field.ins_id}`, {
        fieldName: props.field.ins_id,
        date: model.date,
    });
    $(`#${props.field.ins_id}`)[0].addEventListener('change', function() {
        emit('update:modelValue', {
            date: $(`input[name="${props.field.ins_id}"]`).val(),
        });
    });
</script>

<script>
export default {
    name: 'JsCalendar'
}
</script>
