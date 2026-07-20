<script setup>
import Messages from "./Messages.vue";
import Message from "./Message.vue";
import useMessageStore from "../store";
const store = useMessageStore();

function getDescendants(threadId) {
    //console.log("getDescendants:", threadId);
    var arr = store.getThreads;
    var key = "parentId";
    var response = [];
    arr.forEach(item => {
        if (item[key] === threadId) {
            //console.log("getDescendants found:", item.messageId);
            response.push(item);
        }
    });
    return response;
}
defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>

<script>
export default {
    data() {
        return {
            identifier: +new Date()
        };
    }
}
</script>

<template>
    <Messages :key="identifier" :messages=getDescendants(item.messageId) :resolved="item.resoved === 1" />
</template>

<style scoped lang="scss">

</style>