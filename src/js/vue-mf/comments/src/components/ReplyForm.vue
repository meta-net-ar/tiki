<script setup>
import store from "../store";

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

function addMsg() {
    store.dispatch('addMessage', {
        title: $("#message-" + props.parentId + " input[name='title']").val(),
        parentId: $("#message-" + props.parentId + " input[name='parentid']").val(),
        author: $("#message-" + props.parentId + " input[name='author']").val(),
        message: $("#message-" + props.parentId + " textarea[name='message']").val(),
        date: new Date().toLocaleString(),
    });
    store.state.identifier = +new Date();
}


</script>

<template>
    <div class="thread-form">
        <input type="text" name="title" class="thread-input form-control" placeholder="Optional:type a title/subject for your new discussion...">
        <input type="hidden" name="parentid" :value="parentId" />
        <input type="hidden" name="author" :value="author" />

        <div class="thread-textarea">
            <i class="fa fa-question-circle" style="color: #8ca5af;"></i>
            <textarea name="message" class="thread-input form-control mt-3" rows="5" placeholder="Type your comment here, in Tiki-flavored MarkDown format."></textarea>
            <input type="submit" class="btn btn-secondary mt-2" style="float:right;" value="Preview/Publish" v-on:click="addMsg()">
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>