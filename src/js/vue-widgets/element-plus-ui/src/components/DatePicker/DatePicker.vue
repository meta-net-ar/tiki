<script setup>
import { ref, watchEffect } from 'vue';
import moment, { tz } from 'moment-timezone';
import ConfigWrapper from '../ConfigWrapper.vue';
import getShortcuts from '../../helpers/datePicker/getShortcuts';

const props = defineProps({
    _emit: Function,
    _expose: Function,
    type: {
        type: String,
        default: 'date'
    },
    placeholder: {
        type: String,
        default: 'Pick a date'
    },
    startPlaceholder: {
        type: String,
        default: 'Start date'
    },
    endPlaceholder: {
        type: String,
        default: 'End date'
    },
    customTimezone: String,
    timezonePlaceholder: {
        type: String,
        default: 'Pick a timezone'
    },
    language: {
        type: String,
        default: 'en',
    },
    value: String,
    timezone: String,
    rangeSeparator: {
        type: String,
        default: '-'
    },
    format: String,
});

const customTimezone = props.customTimezone === 'true';
const rangeMode = props.type.slice(-5) === 'range';

const dateModelValue = ref(props.value ? (rangeMode ? props.value.split(','): props.value): '');
const timezoneModelValue = ref(props.timezone ?? '');

const timezones = tz.names();

const shortcuts = getShortcuts(rangeMode);

const handleTimezoneChange = (value) => {
    if (Array.isArray(dateModelValue.value)) {
        dateModelValue.value = dateModelValue.value.map(date => {
            return moment(date).tz(value).toDate();
        });
    } else {
        dateModelValue.value = moment(dateModelValue.value).tz(value).toDate();
    }
    handleDateChange(dateModelValue.value);
    props._emit('timezoneChange', value);
};

const handleDateChange = (value) => {
    props._emit('change', value);
}

props._expose({
    date: dateModelValue,
    timezone: timezoneModelValue,
});

watchEffect(() => {
    dateModelValue.value = props.value ? (rangeMode ? props.value.split(','): props.value): '';
    timezoneModelValue.value = props.timezone ?? '';
});

</script>

<script>
export const DATA_TEST_ID = {
    DATE_PICKER: 'date-picker',
    TIMEZONE_PICKER: 'timezone-picker',
    TIMEZONE_PICKER_SELECT: 'timezone-picker-select',
    TIMEZONE_PICKER_OPTION: 'timezone-picker-option',
}
</script>

<template>
    <ConfigWrapper :language="language">
        <el-date-picker
            v-model="dateModelValue"
            :type="type"
            :placeholder="placeholder"
            :teleported="false"
            :range-separator="rangeSeparator"
            :start-placeholder="startPlaceholder"
            :end-placeholder="endPlaceholder"
            :shortcuts="shortcuts"
            :format="format"
            @change="handleDateChange"
            :data-testid="DATA_TEST_ID.DATE_PICKER"
        >
        </el-date-picker>
        <div class="timezone-picker" v-if="customTimezone" :data-testid="DATA_TEST_ID.TIMEZONE_PICKER">
            <el-select
                v-model="timezoneModelValue"
                :placeholder="timezonePlaceholder"
                :teleported="false"
                :filterable="true"
                @change="handleTimezoneChange"
                :data-testid="DATA_TEST_ID.TIMEZONE_PICKER_SELECT"
            >
                <el-option
                    v-for="timezone in timezones"
                    :key="timezone"
                    :label="timezone"
                    :value="timezone"
                    :data-testid="DATA_TEST_ID.TIMEZONE_PICKER_OPTION"
                />
            </el-select>
        </div>
    </ConfigWrapper>
</template>