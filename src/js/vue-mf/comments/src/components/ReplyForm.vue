<script setup>
import {inject} from "vue";

const tr = inject('tr')
</script>

<script>
import useCommentsStore from "../store/comments";
import useCommentsUiStore from "../store/commentsUi";

export default {
    name: 'ReplyForm',
    props: {
        parentId: {
            type: Number,
            required: true,
        },
        author: {
            type: String,
            required: true,
        },
    },
    mounted() {
        useCommentsUiStore().drawMessagesTree();
    },
    methods: {
        updateMsg: function() {
            if ($("#message-" + this.parentId + " input[name='updatemessage']").val() == "0") {
                useCommentsStore().addMessage(
                        $("#message-" + this.parentId + " input[name='title']").val(),
                        parseInt($("#message-" + this.parentId + " input[name='parentid']").val()),
                        $("#message-" + this.parentId + " input[name='author']").val(),
                        $("#message-" + this.parentId + " textarea[name='message']").val(),
                        new Date().toLocaleString()
                );
            } else {
                useCommentsStore().updateMessage(
                        parseInt($("#message-" + this.parentId + " input[name='parentid']").val()),
                        $("#message-" + this.parentId + " input[name='title']").val(),
                        $("#message-" + this.parentId + " textarea[name='message']").val()
                );
            }
            $("#message-" + this.parentId + " textarea[name='message']").val("");
            $("#message-" + this.parentId + " input[name='title']").val("");
            setTimeout(() => {
                useCommentsUiStore().drawMessagesTree();
            }, 20);//TODO: find a better way to do this
        }
    }
}
</script>

<template>
    <div class="thread-form">
        <input type="text" name="title" class="thread-input form-control" placeholder="Optional:type a title/subject for your new discussion...">
        <input type="hidden" name="parentid" :value="parentId" />
        <input type="hidden" name="author" :value="author" />
        <input type="hidden" name="updatemessage" value="0" />

        <div class="thread-textarea">
            <i class="fa fa-question-circle" style="color: #8ca5af;"></i>
            <textarea name="message" class="thread-input form-control mt-3" rows="5" placeholder="Type your comment here, in Tiki-flavored MarkDown format."></textarea>
            <a :href="'#reply-block-'+parentId" class="btn btn-secondary submit-btn mt-2" style="float:right;" v-on:click="updateMsg()">{{ tr('Preview/Publish') }}</a>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>