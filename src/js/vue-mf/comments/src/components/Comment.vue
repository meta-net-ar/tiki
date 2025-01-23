<script setup>
import Threads from "./Threads.vue";

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
    name: 'Comment',
    methods: {
        getMessages: function (threadId) {
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
    <div class="thread-body card-body text-start">
        <p class="small">
            <span class="border rounded-3 p-1 pr-3 bg-light comment-author">
                <img src="https://as2.ftcdn.net/jpg/00/65/77/27/1000_F_65772719_A1UV5kLi5nCEWI0BNLLiFaBPEkUbv5Fv.webp"/>{{item.author}}
            </span>
            <span class="mx-1 pt-1">on {{item.date}}</span>
            <span class="text-muted pt-1">(17:01 their time)</span>
        </p>
        <h5 class="card-title thread-title" v-if="item.parentId == 0">{{ item.title }}</h5>
        <hr v-if="item.parentId == 0" />
        <div>
            <div class="thread-messages">
                <p class="card-text">{{ item.comment }}</p>
                <div class="message-footer ms-3">
                    <i class="icon fa-solid fa-thumbs-up mt-3"></i><span class="like small text-muted mx-1 me-4">6</span>
                    <i class="icon fa-solid fa-thumbs-down mt-3"></i><span class="dislike small text-muted mx-1">1</span>
                    <div class="thread-button-block float-end">
                        <a href="#" class="btn btn-primary m-1" v-if="item.parentId == 0">Mark as resolved</a>
                        <a href="#" class="btn btn-primary m-1">Pin</a>
                        <a href="#" class="btn btn-primary m-1">Edit</a>
                        <a href="#" class="btn btn-primary m-1">Reply</a>
                    </div>
                </div>
            </div>
            <hr class="bottom-line mt-4" v-if="item.parentId == 0">
        </div>
    </div>
    <Threads :comments=getMessages(item.threadId) />
</template>

<style scoped lang="scss">
.comment-author {
    color:#066399 !important;
}
.comment-author img {
    width: 25px;
    margin-top: -5px;
    margin-right: 12px;
}
.message-footer .btn {
    box-shadow: 5px 5px 3px rgba(0, 0, 0, 0.1);
}
.message-footer .icon {
    color: #ffdb5e;
    font-size: 20px;
}
.bottom-line {
    border-top: 2px dashed black;
}
.thread-body p {
    min-width: 300px;
}
.thread-title {
    font-weight: bold;
}
</style>