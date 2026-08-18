<script setup>
import { inject } from "vue";
import Message from "./Message.vue";
const tr = inject('tr')

defineProps({
    messages: {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <div class="row">
        <p class="text-muted text-center mt-4" v-if="messages.length === 0">{{ tr('No pinned messages yet.') }}</p>
        <div class="card pinnedMessageBody mb-3 thread-replies" v-for="item in messages">
            <Message :item=item :pinnedTab=true />
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