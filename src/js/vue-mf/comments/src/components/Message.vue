<script setup>

import ReplyForm from "./ReplyForm.vue";
import {inject, onMounted} from "vue";
import useCommentsStore from "../store/comments";
import useCommentsUiStore from "../store/commentsUi";
import {highlightMatches} from "../utils/highlight";

const tr = inject('tr')
const store = useCommentsStore();
const uiStore = useCommentsUiStore();
</script>
<script>
export default {
    name: 'Message',
    props: {
        item: {
            type: Object,
            required: true,
        },
        pinnedTab: {
            type: Boolean,
            default: false,
        },
    },
    mounted() {
        useCommentsUiStore().drawMessagesTree();
    },
    computed: {
        highlightedTitle() {
            return highlightMatches(this.item.title, useCommentsUiStore().searchQuery);
        },
        highlightedMessage() {
            return highlightMatches(this.item.message, useCommentsUiStore().searchQuery);
        },
        isSearchMatch() {
            return useCommentsUiStore().itemMatchesSearch(this.item);
        },
        repliesVisible() {
            return useCommentsUiStore().isThreadForciblyVisible(this.item.messageId);
        },
    },
    methods: {
        create: function() {
            $("#message-" + this.item.messageId + " input[name='updatemessage']").val(0);
            $("#message-" + this.item.messageId + " input[name='title']").val("");
            $("#message-" + this.item.messageId + " el-input[name='title']").attr("value", "");
            $("#message-" + this.item.messageId + " textarea[name='message']").val("");
            $("#message-" + this.item.messageId + " .submit-btn").text("Preview/Publish");
            setTimeout(() => {
                useCommentsUiStore().drawMessagesTree();
            }, 20);//TODO: find a better way to do this
        },
        update: function(messageId) {
            $("#message-" + this.item.messageId + " input[name='updatemessage']").val(messageId);
            $("#message-" + this.item.messageId + " input[name='title']").val(this.item.title);
            $("#message-" + this.item.messageId + " el-input[name='title']").attr("value", this.item.title);
            $("#message-" + this.item.messageId + " textarea[name='message']").val(this.item.message);
            $("#message-" + this.item.messageId + " .submit-btn").text("Edit message");
            setTimeout(() => {
                useCommentsUiStore().drawMessagesTree();
            }, 20);//TODO: find a better way to do this
        },
        toggleResolved: function(messageId) {
            const resolved = useCommentsStore().toggleResolved(messageId);
            const timeout = resolved === 1 ? 20 : 500;
            setTimeout(() => {
                useCommentsUiStore().drawMessagesTree();
            }, timeout);//TODO: find a better way to do this
        },
        toggleThreadVisibility: function(messageId) {
            useCommentsUiStore().toggleManualExpand(messageId);
            setTimeout(() => {
                useCommentsUiStore().drawMessagesTree();
            }, 20);//TODO: find a better way to do this
        },
        goToThread: function(messageId) {
            const store = useCommentsStore();
            const uiStore = useCommentsUiStore();
            let root = store.getThreads.find((thread) => thread.messageId === messageId);
            while (root && root.parentId !== 0) {
                root = store.getThreads.find((thread) => thread.messageId === root.parentId);
            }
            if (root && root.resoved === 1 && !uiStore.isManuallyExpanded(root.messageId)) {
                uiStore.toggleManualExpand(root.messageId);
            }
            document.getElementById("tab-recent").click();
            setTimeout(() => {
                uiStore.drawMessagesTree();
                const target = document.getElementById("body-" + messageId);
                target?.scrollIntoView({ behavior: "smooth", block: "center" });
                target?.classList.add("search-match");
                setTimeout(() => {
                    target?.classList.remove("search-match");
                }, 5000);
            }, 100);//TODO: find a better way to do this
        }
    }
}
</script>

<template>
    <div :id="'body-'+item.messageId" class="card-body text-start" :class="[{'search-match': isSearchMatch }, {'message-body': !pinnedTab} , {'pinnedMessageCard': pinnedTab}]">
        <!-- div :class="[{'t-left-border-background' : item.parentId !== 0}]"></div -->
        <div v-if="!pinnedTab" :class="[{'t-left-border' : item.parentId !== 0}]"></div>
        <div v-if="!pinnedTab" :id="'border-'+item.messageId" class="thread-border"></div>
        <p class="small">
            <span class="border rounded-1 p-1 pr-3 bg-light message-author">
                <img src="/img/icons/user.png"/>{{item.author}}
            </span>
            <span class="mx-1 pt-1">on {{item.date}}</span>
            <span class="text-muted pt-1">(17:01 their time)</span>
            <span class="text-warning pt-1 float-end me-3" v-if="item.pinned === 1"><i class="fa-solid fa-thumbtack"></i></span>
        </p>
        <h5 class="card-title thread-title ps-2" v-if="item.title != ''" v-html="highlightedTitle"></h5>
        <hr class="ms-2" v-if="item.parentId == 0 && item.title != '' && !pinnedTab" />
        <div>
            <div :id="'message-'+item.messageId" class="thread-messages ps-2">
                <p class="card-text" v-html="highlightedMessage"></p>
                <div class="message-footer ms-3">
                    <i v-if="!pinnedTab" class="icon fa-solid fa-thumbs-up fa-xs mt-3" :class="{ 'text-primary': item.likedBy.includes(uiStore.currentUser) }" @click="store.like(item.messageId, uiStore.currentUser)"></i><span v-if="!pinnedTab" class="like small text-muted mx-1 me-4">{{ item.like }}</span>
                    <i v-if="!pinnedTab" class="icon fa-solid fa-thumbs-down fa-xs mt-3" :class="{ 'text-danger': item.dislikedBy.includes(uiStore.currentUser) }" @click="store.dislike(item.messageId, uiStore.currentUser)"></i><span v-if="!pinnedTab" class="dislike small text-muted mx-1">{{ item.dislike }}</span>
                    <div :class="[{'d-thread-button-block' : item.parentId != 0 || pinnedTab}, 'thread-button-block float-end']">
                        <a href="#" data-bs-toggle="collapse" :data-bs-target="`.replies-${item.messageId}`" class="btn resolved-btn m-1 btn-sm" :class="item.resoved === 1 ? 'bg-success border-success text-white' : 'btn-outline-primary'" :aria-expanded="item.resoved !== 1" @click="toggleResolved(item.messageId)" v-if="item.parentId == 0 && !pinnedTab">{{ item.resoved === 1 ? tr('Resolved') : tr('Mark as resolved') }}</a>
                        <a href="#" class="btn btn-outline-secondary m-1 btn-sm" :class="{ active: repliesVisible }" :aria-expanded="repliesVisible" @click.prevent="toggleThreadVisibility(item.messageId)" v-if="item.parentId == 0 && item.resoved === 1 && !pinnedTab">{{ repliesVisible ? tr('Hide replies') : tr('Show replies') }}</a>
                        <a class="btn pinned-btn m-1 btn-sm" :class="item.pinned === 1 ? 'bg-warning border-warning text-white' : 'btn-outline-primary'" @click="store.togglePinned(item.messageId)">{{ item.pinned === 1 ? tr('Unpin') : tr('Pin') }}</a>
                        <a v-if="pinnedTab" :href="'#body-'+item.messageId" class="btn btn-outline-primary m-1 btn-sm" @click.prevent="goToThread(item.messageId)">{{ tr('View in thread') }}</a>
                        <a v-if="!pinnedTab" :href="'#message-'+item.messageId" class="btn btn-outline-primary m-1 btn-sm" @click="update(item.messageId)">{{ tr('Edit') }}</a>
                        <a v-if="!pinnedTab" :href="'#message-'+item.messageId" class="btn btn-outline-primary m-1 btn-sm" @click="create()">{{ tr('Reply') }}</a>
                    </div>
                    <div v-if="!pinnedTab" :id="'reply-block-'+item.messageId" class="col-md-8 mt-5 mb-5 mx-auto">
                        <ReplyForm :parentId=item.messageId :author=item.author /> <!-- Todo: Don't forget to change the author -->
                    </div>
                </div>
            </div>
            <!--<hr class="bottom-line mt-4 ms-2" v-if="item.parentId == 0">-->
        </div>
    </div>
</template>

<style scoped lang="scss">
.message-body.search-match {
    background-color: rgba(255, 193, 7, 0.08);
    box-shadow: inset 0 0 0 1px rgba(255, 193, 7, 0.5);
    border-radius: 4px;
}

.message-body :deep(mark) {
    background-color: #fff3a3;
    padding: 0 1px;
    border-radius: 2px;
}

.message-footer .icon.text-primary {
    color: #0d6efd !important;
}

.message-footer .icon.text-danger {
    color: #dc3545 !important;
}

.message-footer a.resolved-btn.btn-outline-primary:hover {
    background-color: var(--bs-btn-active-bg);
    border-color: var(--bs-btn-active-bg);
}

.message-footer a.resolved-btn.bg-success:hover {
    background-color: #ffffff;
    border-color: #198754;
    color: #198754;
}

.message-footer a.pinned-btn.bg-warning:hover {
    background-color: #ffffff;
    border-color: #A5660D;
    color: #A5660D;
}
</style>
