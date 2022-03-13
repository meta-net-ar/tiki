<script>
export default {
    name: 'FormAddCard'
}
</script>
<script setup>
import { ref, watchEffect } from 'vue'
import { Button } from '@vue-mf/styleguide'
import KanbanCard from '../KanbanCard.vue'
import { useToast } from "vue-toastification"
import autosize from 'autosize'
import kanban from '../../api/kanban'
import store from '../../store'

const props = defineProps({
    cellId: {
        type: Number
    },
    rowValue: [String, Number],
    columnValue: [String, Number],
})
const emit = defineEmits(['close'])

const toast = useToast()
const trackerId = ref(store.getters.getTrackerId)
const title = ref('')
const textarea = ref(null)
const loading = ref(false)

watchEffect(() => {
    autosize(textarea.value)
})

const handleAddCard = () => {
    loading.value = true
    let sortOrder = 1
    let cardIds = store.getters.getCell(props.cellId).cards
    let lastCard = store.getters.getCard(cardIds[cardIds.length - 1])
    if (lastCard) sortOrder = parseFloat(lastCard.sortOrder) + 1

    kanban.createItem(
        { trackerId: trackerId.value },
        { fields: {
                [store.getters.getTitleField]: title.value,
                [store.getters.getSwimlaneField]: props.rowValue,
                [store.getters.getXaxisField]: props.columnValue,
                [store.getters.getYaxisField]: sortOrder
            },
        }
    )
        .then(res => {
            loading.value = false
            emit('close')
            store.dispatch('addNewCard', {
                id: res.data.itemId,
                title: title.value,
                cellId: props.cellId,
                row: props.rowValue,
                column: props.columnValue,
                sortOrder: sortOrder,
            })
            store.dispatch('addRules', [
                {
                    action: "update",
                    subject: "Tracker_Item",
                    fields: [
                        store.getters.getTitleField,
                        store.getters.getDescriptionField,
                        store.getters.getXaxisField,
                        store.getters.getYaxisField,
                        store.getters.getSwimlaneField
                    ],
                    conditions: {
                        itemId: res.data.itemId
                    }
                },
                {
                    action: "delete",
                    subject: "Tracker_Item",
                    conditions: {
                        itemId: res.data.itemId
                    }
                }
            ])
            console.log(res.data)
            toast.success(`${res.status} ${res.statusText}! Item created.`)
        })
        .catch(err => {
            loading.value = false
            const { code, errortitle, message } = err.response.data
            const msg = `Code: ${code} - ${message}`
            toast.error(msg)
        })
}
</script>

<template>
    <div class="add-cart-container">
        <KanbanCard>
            <textarea
                ref="textarea"
                v-model="title"
                class="form-control"
                rows="3"
                placeholder="Enter a title for this card..."
            >{{ title }}</textarea>
        </KanbanCard>
        <Button sm @click="handleAddCard">
            Add card
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
        </Button>
        <Button class="ml-2" variant="default" sm @click="$emit('close')">
            <i class="fas fa-times"></i>
        </Button>
    </div>
</template>

<style lang="scss" scoped>
:deep(.card-body) {
    padding: 0;
}
.add-cart-container {
    padding: 10px;
}
.btn-default {
    // background-color: rgba(228, 230, 240, 0.658);
    &:hover {
        background-color: rgba(9, 30, 66, 0.08);
    }
}
</style>
