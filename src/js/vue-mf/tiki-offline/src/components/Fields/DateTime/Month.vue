<template>
    <div class="col-auto">
        <select class="form-control" :id="`${props.field.ins_id}Month`" :name="`${field.ins_id}Month`" :value="model.month" @change="$emit('update:modelValue', { ...model, ['month']: $event.target.value })">
            <option v-for="(label, index) in months" :value="zeroPad(index+1)">{{label}}</option>
        </select>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        },
        zeroPad: {
        }
    })

    const months = computed(() => {
        return [
            tr('January'),
            tr('February'),
            tr('March'),
            tr('April'),
            tr('May'),
            tr('June'),
            tr('July'),
            tr('August'),
            tr('September'),
            tr('October'),
            tr('November'),
            tr('December'),
        ]
    })
</script>

<script>
export default {
    name: 'Month'
}
</script>
