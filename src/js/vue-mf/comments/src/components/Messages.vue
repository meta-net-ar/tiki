<script setup>
import { computed } from 'vue';
import Replies from "./Replies.vue";
import useMessageStore from "../store";
const store = useMessageStore();

import Message from "./Message.vue";

const messages = computed(() => store.getParentMessages)
</script>

<template>
    <div class="row">
        <div :class="[{'card thread-card bg-body mt-5' : item.parentId == 0},{'ps-5 thread-replies' : item.parentId != 0}, 'col-md-12 thread-block']"
            v-for="item in messages">
            <Message :item=item />
            We have an infinite loop there
            <!--<Replies :item=item />-->
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