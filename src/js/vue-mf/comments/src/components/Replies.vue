<script setup>
import Messages from "./Messages.vue";
import Message from "./Message.vue";
import useCommentsStore from "../store/comments";
const store = useCommentsStore();

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
    <Messages :key="identifier" :messages=getDescendants(item.messageId) />
</template>

<style scoped lang="scss">

</style>