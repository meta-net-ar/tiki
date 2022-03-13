<script>
export default {
    name: 'FormEditField'
}
</script>
<script setup>
import { ref } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import kanban from '../../api/kanban'
import store from '../../store'
import defineAbilityFor from '../../auth/defineAbility'
import { subject } from "@casl/ability"

const props = defineProps({
    id: [Number, String],
    title: {
        type: String
    }
})

const trackerId = ref(store.getters.getTrackerId)
const showEditField = ref(false)
const toast = useToast()
const titleField = ref(props.title)

const handleSaveTitle = event => {
    showEditField.value = false

    if (titleField.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    kanban.setItem(
        { trackerId: trackerId.value, itemId: props.id },
        { fields: {
                [store.getters.getTitleField]: titleField.value
            }
        }
    )
        .then(res => {
            // toast.success(`Success! Title saved.`)
        })
        .catch(err => {
            if (!err.response) toast.error('Error: title not saved')
            const { code, errortitle, message } = err.response.data
            const msg = `Code: ${code} - ${message}`
            toast.error(msg)
        })

    store.dispatch('editCardField', {
        id: props.id,
        field: 'title',
        data: titleField.value
    })
}

const handleEditClick = event => {
    const ability = defineAbilityFor(store.getters.getRules)
    const canUpdate = ability.can('update', subject('Tracker_Item', {itemId: props.id}), store.getters.getTitleField)
    if (canUpdate) showEditField.value = true
}
</script>

<template>
    <div>
        <div v-if="!showEditField" @click="handleEditClick">{{ titleField }}</div>
        <div v-if="showEditField" class="editable-container">
            <Field
                class="form-control"
                v-focus
                v-autosize
                as="textarea"
                rows="1"
                v-model="titleField"
                name="cardTitle"
                type="text"
                :rules="{ minLength: 1 }"
                @blur="handleSaveTitle"
            />
        </div>
    </div>
</template>

<style lang="scss" scoped>
.btn-default {
    &:hover {
        background-color: rgba(9, 30, 66, 0.08);
    }
}

.editable-container {
    position: relative;

    .form-control {
        font-weight: 500;
        line-height: 1.2;
    }
}
.editable-controls {
    position: absolute;
    right: 0;
}
</style>
