<script setup>
import Threads from "./Threads.vue";
import Message from "./Message.vue";

defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>
<script>
import store from "../store";

export default {
    name: 'Messages',
    methods: {
        getDescendants: function (threadId) {
            var arr = store.getters.getThreads;
            var key = "parentId";
            var response = [];
            arr.forEach(item => {
                if (item[key] === threadId) {
                    response.push(item);
                }
            });
            return response;
        }
    },
}


</script>

<template>
    <Message :item=item />
    <Threads :messages=getDescendants(item.messageId) />
</template>

<style scoped lang="scss">

</style>