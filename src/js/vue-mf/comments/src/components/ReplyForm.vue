<script setup>
import useMessageStore from "../store";
const store = useMessageStore();

const props = defineProps({
    parentId: {
        type: Number,
        required: true,
    },
    author: {
        type: String,
        required: true,
    },
});

function updateMsg() {
    if ($("#message-" + props.parentId + " input[name='updatemessage']").val() == "0") {
        store.addMessage(
            $("#message-" + props.parentId + " input[name='title']").val(),
            parseInt($("#message-" + props.parentId + " input[name='parentid']").val()),
            $("#message-" + props.parentId + " input[name='author']").val(),
            $("#message-" + props.parentId + " textarea[name='message']").val(),
            new Date().toLocaleString()
        );
    } else {
        store.updateMessage(
            parseInt($("#message-" + props.parentId + " input[name='parentid']").val()),
            $("#message-" + props.parentId + " input[name='title']").val(),
            $("#message-" + props.parentId + " textarea[name='message']").val()
        );
    }
    $("#message-" + props.parentId + " textarea[name='message']").val("");
    $("#message-" + props.parentId + " input[name='title']").val("");
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
            <a :href="'#reply-block-'+parentId" class="btn btn-secondary submit-btn mt-2" style="float:right;" v-on:click="updateMsg()">Preview/Publish</a>
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>