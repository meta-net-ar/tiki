<!--
    Field type 'd' (dropdown) or 'D' (dropdown + other) or 'M' (multiselect)
-->
<template>
    <select v-model="model"
        :id="field.ins_id"
        :name="field.html_name"
        class="form-select"
        :class="[field.type == 'D' ? `group_${field.ins_id}` : '']"
        :multiple="field.type == 'M' ? 'multiple' : null">
        <option value="" v-if="field.isMandatory != 'y' || !model"></option>
        <option v-for="(label, value) in field.possibilities" :value="value">
            {{label}}
        </option>
        <option :value="otherValue" style="font-style: italic" v-if="field.type == 'D'">
            Other
        </option>
    </select>
    <div class="offset-md-1" v-if="field.type == 'D'">
        <label :for="`other_${field.ins_id}`" v-show="otherShouldShow()">
            Other:
            <input type="text"
                class="form-control"
                :class="`group_${field.ins_id}`"
                :name="`other_${field.ins_id}`"
                :id="`other_${field.ins_id}`"
                :value="otherFieldValue()"
                @change="handleOtherChange">
        </label>
    </div>
</template>

<script setup>
    import { ref } from 'vue'
    const model = defineModel()
    const props = defineProps({
        field: {
            type: Object
        }
    })

    const otherValue = ref('other')

    const otherShouldShow = () => {
        return !props.field.possibilities[model.value] && model.value
    }

    const otherFieldValue = () => {
        if (props.field.possibilities[model.value]) {
            return ''
        }
        if (model.value == 'other') {
            return ''
        }
        return model.value
    }

    const handleOtherChange = (event) => {
        otherValue.value = model.value = event.target.value
    }
</script>

<script>
    export default {
        name: "Dropdown",
    };
</script>
