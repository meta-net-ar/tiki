<script setup>

import CommentForm from "./CommentForm.vue";
import {inject} from "vue";

const tr = inject('tr')
defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>
<script>
export default {
    name: 'Comment',
}
</script>

<template>
    <div class="comment-body card-body text-start">
        <p class="small">
            <span class="border rounded-3 p-1 pr-3 bg-light comment-author">
                <img src="/img/icons/user.png"/>{{item.author}}
            </span>
            <span class="mx-1 pt-1">on {{item.date}}</span>
            <span class="text-muted pt-1">(17:01 their time)</span>
        </p>
        <div class="thread-border"></div>
        <h5 class="card-title thread-title ps-2" v-if="item.title != ''">{{ item.title }}</h5>
        <hr class="ms-2" v-if="item.parentId == 0" />
        <div>
            <div :id="'comment-'+item.commentId" class="thread-messages ps-2">
                <p class="card-text">{{ item.comment }}</p>
                <div :class="[{'d-message-footer' : item.parentId != 0}, 'message-footer ms-3']">
                    <i class="icon fa-solid fa-thumbs-up mt-3"></i><span class="like small text-muted mx-1 me-4">6</span>
                    <i class="icon fa-solid fa-thumbs-down mt-3"></i><span class="dislike small text-muted mx-1">1</span>
                    <div class="thread-button-block float-end">
                        <a href="#" class="btn btn-primary m-1" v-if="item.parentId == 0">{{ tr('Mark as resolved') }}</a>
                        <a href="#" class="btn btn-primary m-1">{{ tr('Pin') }}</a>
                        <a href="#" class="btn btn-primary m-1">{{ tr('Edit') }}</a>
                        <a :href="'#comment-'+item.commentId" class="btn btn-primary m-1">{{ tr('Reply') }}</a>
                    </div>
                    <div :id="'reply-block-'+item.commentId" class="col-md-8 mt-5 mb-5 mx-auto">
                        <CommentForm />
                    </div>
                </div>
            </div>
            <hr class="bottom-line mt-4 ms-2" v-if="item.parentId == 0" style="margin-top: 70px;">
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>