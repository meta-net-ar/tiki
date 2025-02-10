<script setup>

</script>

<template>
    <div class="thread-form">
        <input type="text" class="thread-input form-control" placeholder="Optional:type a title/subject for your new discussion...">
        <div class="thread-textarea">
            <i class="fa fa-question-circle" style="color: #8ca5af;"></i>
            <textarea class="thread-input form-control mt-3" rows="5" placeholder="Type your comment here, in Tiki-flavored MarkDown format."></textarea>
            <input type="submit" class="btn btn-secondary mt-2" style="float:right;" value="Preview/Publish">
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>