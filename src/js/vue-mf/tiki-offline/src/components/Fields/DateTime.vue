<!--
    Field type: f
-->
<template>
    <div class="row gy-2 gx-3 align-items-center mb-3" v-if="field.options_map.datetime != 't'">
        <component :is="c" v-for="c in orderedComponents" :field="field" v-model="model" :zeroPad="zeroPad"></component>
    </div>
    <span v-if="field.options_map.datetime == 'dt'">at</span>
    <div class='row gy-2 gx-3 align-items-center mb-3 html-select-time' v-if="field.options_map.datetime != 'd'">
        <div class="col-auto">
            <select class="form-control" :id="`${props.field.ins_id}Hour`" :name="`${props.field.ins_id}Hour`" :value="model.hour" @change="$emit('update:modelValue', { ...model, ['hour']: $event.target.value })">
                <option v-for="hour in use24hrClock ? 24 : 12" :value="zeroPad(hour-1)">{{zeroPad(hour-1)}}</option>
            </select>
        </div>
        <div class="col-auto">
            <select class="form-control" :id="`${props.field.ins_id}Minute`" :name="`${props.field.ins_id}Minute`" :value="model.minute" @change="$emit('update:modelValue', { ...model, ['minute']: $event.target.value })">
                <option v-for="minute in minutes" :value="zeroPad(minute)">{{zeroPad(minute)}}</option>
            </select>
        </div>
        <div class="col-auto" v-if="!use24hrClock">
            <select class="form-control" :name="`${props.field.ins_id}Meridian`" :value="model.meridian" @change="$emit('update:modelValue', { ...model, ['meridian']: $event.target.value })">
                <option value="am">AM</option>
                <option value="pm">PM</option>
            </select>
        </div>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    import Day from './DateTime/Day.vue'
    import Month from './DateTime/Month.vue'
    import Year from './DateTime/Year.vue'
    import store from '../../store';

    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        },
        minuteStep: {
            type: Number,
            default: 1
        }
    })
    const emit = defineEmits(['input'])

    const orderedComponents = computed(() => jqueryTiki.display_field_order.split('').map((o) => {
        switch (o) {
            case 'D':
                return Day;
            case 'M':
                return Month;
            case 'Y':
                return Year;
        }
        return null;
    }))

    const use24hrClock = computed(() => store.getters.getPref('use_24hr_clock'))
    const minuteStep = computed(() => Math.max(1, Number(props.minuteStep) || 1));
    const minutes = computed(() => {
        const vals = [];
        for (let m = 0; m < 60; m += minuteStep.value) {
            vals.push(m);
        }
        return vals;
    });

    const zeroPad = (num) => {
        if (parseInt(num) < 10) {
            return '0' + num.toFixed(0);
        } else {
            return num.toFixed(0);
        }
    }
</script>

<script>
export default {
    name: 'DateTime'
}
</script>
