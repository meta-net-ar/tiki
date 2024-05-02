<script>
export default {
    name: 'KanbanBoard'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Field } from 'vee-validate'
import { useToast } from "vue-toastification"
import { Button } from '@vue-mf/styleguide'
import KanbanRow from './KanbanRow.vue'
import ButtonAddColumn from './Buttons/ButtonAddColumn.vue'
import KanbanColumns from './KanbanColumns.vue'
import store from '../store'

const props = defineProps({
    id: {
        type: Number
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const board = computed(() => store.getters.getBoard(props.id))
const getUserName = computed(() => {
    const user = store.getters.getUser
    if (user && user.user) return user.user
    return user ? user : 'guest'
})

const showEditField = ref(false)
const toast = useToast()

const handleTitleBlur = event => {
    showEditField.value = false

    if (event.target.value.length < 1) {
        toast.error(`This field must be at least 1 character`)
        return
    }

    store.dispatch('editBoardField', {
        id: props.id,
        field: 'title',
        data: event.target.value
    })
}

const handleEditClick = event => {
    showEditField.value = true
}

const handleAddRow = event => {
    store.dispatch('addRow', {
        boardId: props.id,
        title: 'New swimlane'
    })
}
</script>

<template>
    <div class="kanban-container" :style="{backgroundImage: board.imageUrl ? `url(${board.imageUrl})` : 'none'}">
        <!-- <nav class="navbar navbar-light rounded-lg bg-color-grey" :class="{'bg-color-transparent': board.imageUrl, 'bg-light': !board.imageUrl}"> -->
        <nav class="navbar navbar-light" :class="{'bg-color-transparent': board.imageUrl}">
            <div>
                <span v-if="!showEditField" style="font-size: 1.25rem; font-weight: 500;">{{ board.title }}</span>
                <span v-if="loading" class="mx-2"><i class="fas fa-spinner fa-spin"></i></span>
                <span v-if="!loading" class="mx-2">({{ getUserName }})</span>
                <Field
                    v-if="showEditField"
                    v-focus
                    v-autosize
                    as="textarea"
                    rows="1"
                    :value="board.title"
                    @blur="handleTitleBlur"
                    name="rowTitle"
                    type="text"
                    :rules="{ minLength: 1 }"
                />
            </div>
            <Button v-if="false" sm variant="light" @click="handleAddRow"><i class="fas fa-plus mr-1"></i>Add swimlane</Button>
        </nav>
        <PerfectScrollbar class="d-flex" :options="{}">
            <div class="board-container">
                <KanbanRow
                    v-for="(row, index) in store.getters.getRows(board.rows)"
                    :key="row.title"
                    :title="row.title"
                    :transparentTitleBg="board.imageUrl ? true : false"
                    :boardId="id"
                    :rowId="row.id"
                    :rowValue="row.value"
                    :index="index"
                >
                    <KanbanColumns :rowId="row.id" :rowIndex="index" :rowValue="row.value" :cellIds="row.cells"></KanbanColumns>
                    <ButtonAddColumn v-if="index === 0" :rowId="row.id"></ButtonAddColumn>
                </KanbanRow>
            </div>
        </PerfectScrollbar>
    </div>
</template>

<style lang="scss" scoped>
.kanban-container {
    border-radius: 6px;
    // background: #f3f4fa;
    // padding: 5px 0;
    background-size: cover;
    background-color: rgba(115, 155, 183, 0.18);
}
.board-container {
    flex-grow: 1;
    // padding-top: 5px;
    margin-bottom: 8px;
}
.navbar {
    // padding: 0rem 1rem;
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    background: rgba(212, 225, 255, 0.45);
    // margin: 0 5px;
}
</style>
