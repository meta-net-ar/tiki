<script>
export default {
    name: 'FormEditCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Button } from '@vue-mf/styleguide'
import kanban from '../../api/kanban'
import store from '../../store'
import defineAbilityFor from '../../auth/defineAbility'
import { subject } from "@casl/ability"

const props = defineProps({
    id: [Number, String],
    title: {
        type: String
    },
    desc: {
        type: String,
        default: ''
    },
    reference: {
        type: String,
        default: ''
    }
})

const trackerId = ref(store.getters.getTrackerId)
const showEditField = ref(false)
const toast = useToast()
const editDesc = ref(false)
const titleField = ref(props.title)
const description = ref('')
const textarea = ref(null)

watchEffect(() => {
    description.value = props.desc
})

const handleEditClick = event => {
    const ability = defineAbilityFor(store.getters.getRules)
    const canUpdate = ability.can('update', subject('Tracker_Item', {itemId: props.id}), store.getters.getTitleField)
    if (canUpdate) showEditField.value = true
}

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
            toast.success(`Success! Title saved.`)
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

const handleDescriptionInput = event => {
    description.value = event.target.value
}
const handleEditDesc = () => {
    const ability = defineAbilityFor(store.getters.getRules)
    const canUpdate = ability.can('update', subject('Tracker_Item', {itemId: props.id}), store.getters.getDescriptionField)
    if (canUpdate) editDesc.value = true
}
const handleSaveDesc = () => {
    kanban.setItem(
        { trackerId: trackerId.value, itemId: props.id },
        { fields: {
                [store.getters.getDescriptionField]: description.value
            }
        }
    )
        .then(res => {
            toast.success(`Success! Description saved.`)
        })
        .catch(err => {
            if (!err.response) toast.error('Error: description not saved')
            const { code, errortitle, message } = err.response.data
            const msg = `Code: ${code} - ${message}`
            toast.error(msg)
        })
    store.dispatch('editCardField', {
        id: props.id,
        field: 'description',
        data: description.value
    })
    editDesc.value = false
}
const handleCancel = () => {
    description.value = props.desc
    editDesc.value = false
}
const handleCancelEditTitle = () => {
    titleField.value = props.title
    showEditField.value = false
}
</script>

<template>
    <div class="mb-3">
        <h4 v-if="!showEditField" @click="handleEditClick">{{ titleField }}</h4>
        <div v-if="showEditField" class="editable-container">
            <Field
                class="form-control mb-1"
                v-focus
                v-autosize
                as="textarea"
                rows="1"
                v-model="titleField"
                name="cardTitle"
                type="text"
                :rules="{ minLength: 1 }"
            />
            <div class="editable-controls">
                <Button class="d-inline-block" variant="default" sm @click="handleSaveTitle">
                    <i class="fas fa-check"></i>
                </Button>
                <Button class="d-inline-block ml-2" variant="default" sm @click="handleCancelEditTitle">
                    <i class="fas fa-times"></i>
                </Button>
            </div>
        </div>
    </div>
    <div class="mb-2" v-if="reference">
        <small>
            <span class="mr-2">
                <i class="fas fa-link"></i>
            </span>
            <a :href="reference" target="_blank">item-{{ id }}</a>
        </small>
    </div>

    <h6 class="d-inline-block mr-2"><i class="fas fa-align-left mr-2"></i> Description</h6>
    <p v-if="!editDesc" @click="handleEditDesc">
        <div v-if="description.length === 0" @click="handleEditDesc">Click to add description...</div>
        {{ description }}
    </p>
    <div v-if="editDesc">
        <textarea v-autosize v-focus @input="handleDescriptionInput" class="form-control mb-2" name="" id="">{{ description }}</textarea>
        <div>
            <Button class="d-inline-block" variant="default" sm @click="handleSaveDesc"><i class="fas fa-check"></i></Button>
            <Button class="d-inline-block ml-2" variant="default" sm @click="handleCancel">
                <i class="fas fa-times"></i>
            </Button>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.btn-default {
    // background-color: rgba(228, 230, 240, 0.658);
    &:hover {
        background-color: rgba(9, 30, 66, 0.08);
    }
}

.editable-container {
    position: relative;

    .form-control {
        font-size: 1.5rem;
        font-weight: 500;
        line-height: 1.2;
    }
}
.editable-controls {
    position: absolute;
    right: 0;
}
</style>
