<script setup>
import Message from "./Message.vue";
import Replies from "./Replies.vue";
import useCommentsUiStore from "../store/commentsUi";
const uiStore = useCommentsUiStore();

defineProps({
    messages: {
        type: Object,
        required: true,
    },
    resolved: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="row">
        <div :class="[
          {'card thread-card bg-body mt-5 pt-2 pb-2' : item.parentId == 0},
          item.parentId != 0 ? `ps-5 thread-replies replies-${item.parentId} collapse${(resolved && !uiStore.isThreadForciblyVisible(item.parentId)) ? '' : ' show'}` : '',
          'col-md-12 thread-block'
        ]" v-for="item in messages">
            <Message :item=item />
            <Replies :item=item />
        </div>
    </div>
</template>

<style scoped lang="scss">
.thread-card {
    box-shadow: 0.5rem 0.5rem 1rem rgba(0, 0, 0, .15) !important;
    display: grid;
}
.thread-card > div:first-child {
    margin-left: 0px;
}
.card.thread-card:first-of-type {
    margin-top: 0px;
}
.thread-block {
    position: sticky;
}
</style>