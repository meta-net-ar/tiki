<script setup>
import Comment from "./Comment.vue";
import store from "../store";
defineProps({
    comments: {
        type: Object,
        default() {
            return store.getters.getParentMessages;
        }
    },
});
</script>
<script>
import store from '../store';

export default {
    name: 'Threads',
};
</script>

<template>
    <div class="row">
        <div :class="[{'card thread-card bg-body mt-5' : item.parentId == 0},{'ps-5' : item.parentId != 0}, 'col-md-12']" v-for="item in comments">
            <Comment :item=item />
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
</style>