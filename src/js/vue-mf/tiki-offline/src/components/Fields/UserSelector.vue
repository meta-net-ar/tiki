<!--
field type: u
-->
<template>
    <select v-model="model"
        :id="field.ins_id"
        :name="field.html_name"
        class="form-select"
        :multiple="field.canHaveMultipleValues ? 'multiple' : null">
        <option value="" v-if="field.isMandatory != 'y' || !model"></option>
        <option v-for="(label, value) in field.possibilities" :value="value">
            {{label}}
        </option>
    </select>
</template>

<script setup>
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })
</script>

<script>
    export default {
        name: "UserSelector",
    };
</script>
