<template>
    <div class="col-auto">
        <select class="form-control" :id="`${props.field.ins_id}Year`" :name="`${props.field.ins_id}Year`" :value="model.year" @change="$emit('update:modelValue', { ...model, ['year']: $event.target.value })">
            <option v-for="year in years" :value="year">{{year}}</option>
        </select>
    </div>
</template>

<script setup>
    import { computed } from 'vue'
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })

    const computeYear = (year) => {
        let m = null;
        if (m = year.match(/^(\+|\-)\s*(\d+)/)) {
            let dt = new Date()
            if (m[1] == '+') {
                year = dt.getFullYear() + parseInt(m[2])
            } else {
                year = dt.getFullYear() - parseInt(m[2])
            }
        }
        return year
    }

    const startYear = computed(() => {
        let year = props.field.options_map.startyear
        if (! year) {
            year = jqueryTiki.display_start_year
        }
        if (! year) {
            year = '-4'
        }
        return computeYear(year)
    })

    const endYear = computed(() => {
        let year = props.field.options_map.endyear
        if (! year) {
            year = jqueryTiki.display_end_year
        }
        if (! year) {
            year = '+4'
        }
        return computeYear(year)
    })

    const years = computed(() => {
        let res = []
        let i = 0
        for (i = parseInt(startYear.value); i <= parseInt(endYear.value); i++) {
            res.push(i)
        }
        return res
    });
</script>

<script>
export default {
    name: 'Year'
}
</script>
