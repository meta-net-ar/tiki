<script setup>
import Threads from "./Threads.vue";
import Comment from "./Comment.vue";

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
    name: 'Comments',
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
    <Comment :item=item />
    <Threads :comments=getDescendants(item.commentId) />
</template>

<style scoped lang="scss">

</style>