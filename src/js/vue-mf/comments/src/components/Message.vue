<script setup>

import ReplyForm from "./ReplyForm.vue";
import {inject} from "vue";

import useMessageStore from "../store";
const store = useMessageStore();

const tr = inject('tr')
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});

function update(messageId) {
    $("#message-" + props.item.messageId + " input[name='updatemessage']").val(messageId);
    $("#message-" + props.item.messageId + " input[name='title']").val(props.item.title);
    $("#message-" + props.item.messageId + " textarea[name='message']").val(props.item.message);
    $("#message-" + props.item.messageId + " .submit-btn").text("Edit message");
}

function create() {
    $("#message-" + props.item.messageId + " input[name='updatemessage']").val(0);
    $("#message-" + props.item.messageId + " input[name='title']").val("");
    $("#message-" + props.item.messageId + " textarea[name='message']").val("");
    $("#message-" + props.item.messageId + " .submit-btn").text("Preview/Publish");
}
</script>
<script>
export default {
    name: 'Message',
}
</script>

<template>
    <div class="message-body card-body text-start">
        <div class="thread-border"></div>
        <p class="small">
            <span class="border rounded-1 p-1 pr-3 bg-light message-author">
                <img src="/img/icons/user.png"/>{{item.author}}
            </span>
            <span class="mx-1 pt-1">on {{item.date}}</span>
            <span class="text-muted pt-1">(17:01 their time)</span>
        </p>
        <h5 class="card-title thread-title ps-2" v-if="item.title != ''">{{ item.title }}</h5>
        <hr class="ms-2" v-if="item.parentId == 0 && item.title != ''" />
        <div>
            <div :id="'message-'+item.messageId" class="thread-messages ps-2">
                <p class="card-text">{{ item.message }}</p>
                <div class="message-footer ms-3">
                    <i class="icon fa-solid fa-thumbs-up fa-xs mt-3" @click="store.like(item.messageId)"></i><span class="like small text-muted mx-1 me-4">{{ item.like }}</span>
                    <i class="icon fa-solid fa-thumbs-down fa-xs mt-3" @click="store.dislike(item.messageId)"></i><span class="dislike small text-muted mx-1">{{ item.dislike }}</span>
                    <div :class="[{'d-thread-button-block' : item.parentId != 0}, 'thread-button-block float-end']">
                        <a href="#" class="btn btn-outline-primary m-1 btn-sm" v-if="item.parentId == 0">{{ tr('Mark as resolved') }}</a>
                        <a href="#" class="btn btn-outline-primary m-1 btn-sm">{{ tr('Pin') }}</a>
                        <a :href="'#message-'+item.messageId" class="btn btn-outline-primary m-1 btn-sm" @click="update(item.messageId)">{{ tr('Edit') }}</a>
                        <a :href="'#message-'+item.messageId" class="btn btn-outline-primary m-1 btn-sm" @click="create()">{{ tr('Reply') }}</a>
                    </div>
                    <div :id="'reply-block-'+item.messageId" class="col-md-8 mt-5 mb-5 mx-auto">
                        <ReplyForm :parentId=item.messageId :author=item.author /> <!-- Todo: Don't forget to change the author -->
                    </div>
                </div>
            </div>
            <hr class="bottom-line mt-4 ms-2" v-if="item.parentId == 0">
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>