<script setup>

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
                <img src="https://as2.ftcdn.net/jpg/00/65/77/27/1000_F_65772719_A1UV5kLi5nCEWI0BNLLiFaBPEkUbv5Fv.webp"/>{{item.author}}
            </span>
            <span class="mx-1 pt-1">on {{item.date}}</span>
            <span class="text-muted pt-1">(17:01 their time)</span>
        </p>
        <div class="thread-border"></div>
        <h5 class="card-title thread-title ps-2" v-if="item.title != ''">{{ item.title }}</h5>
        <hr class="ms-2" v-if="item.parentId == 0" />
        <div>
            <div class="thread-messages ps-2">
                <p class="card-text">{{ item.comment }}</p>
                <div :class="[{'d-none' : item.parentId != 0}, 'message-footer ms-3']">
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
            <hr class="bottom-line mt-4 ms-2" v-if="item.parentId == 0">
        </div>
    </div>
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
.comment-body p {
    min-width: 400px;
}
.thread-title {
    font-weight: bold;
}
.thread-border {
    border-left: 0.01rem solid rgba(167, 167, 167, 0.3);
    position: absolute;
    height: -webkit-fill-available;
    width: 10px;
    z-index: 9999;
}
.thread-border:hover {
    border-left: 0.01rem solid rgba(0, 0, 0, 1);
}
.thread-replies .thread-border:before {
    content: "";
    display: block;
    width: 35px;
    height: 1px;
    background: #a7a7a7;
    left: -36px;
    top: -28px;
    position: absolute;
    z-index: 9999;
}
.thread-replies .thread-border:hover::before {
    background: #000000;
}
.comment-body:hover .d-none {
    display: block;
}
</style>