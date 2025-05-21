<template>
    <div class="col-auto">
        <select class="form-control" :id="`${props.field.ins_id}Day`" :name="`${field.ins_id}Day`" :value="model.day" @change="$emit('update:modelValue', { ...model, ['day']: $event.target.value })">
            <option v-for="day in 31" :value="zeroPad(day)">{{day}}</option>
        </select>
    </div>
</template>

<script setup>
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        },
        zeroPad: {
        }
    })
</script>

<script>
export default {
    name: 'Day'
}
</script>
