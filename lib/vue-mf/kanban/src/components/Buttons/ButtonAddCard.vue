<script>
export default {
    name: 'ButtonAddCard'
}
</script>
<script setup>
import { ref, computed } from 'vue'
import { Button } from '@vue-mf/styleguide'
import FormAddCard from '../Forms/FormAddCard.vue'
import defineAbilityFor from '../../auth/defineAbility'
import store from '../../store'

defineProps({
    cellId: {
        type: Number
    },
    rowValue: [String, Number],
    columnValue: [String, Number],
});

const showForm = ref(false)

const canCreateCard = computed(() => {
    const ability = defineAbilityFor(store.getters.getRules)
    return ability.can('create', 'Tracker_Item')
})

const handleOpen = () => {
    showForm.value = true
}

const handleClose = () => {
    showForm.value = false
}

</script>

<template>
    <Button v-if="!showForm && canCreateCard" class="w-100" variant="default" sm @click="handleOpen">
        <i class="fas fa-plus"></i>
        <span class="ml-2">Add a card</span>
    </Button>
    <FormAddCard v-if="showForm" :cellId="cellId" :rowValue="rowValue" :columnValue="columnValue"  @close="handleClose"></FormAddCard>
</template>

<style lang="scss" scoped>
    .btn-default {
        // background-color: rgba(228, 230, 240, 0.658);
        &:hover {
            background-color: rgba(9, 30, 66, 0.08);
        }
    }
</style>
