<template>
    <div id="app">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-12">
                    <p class="lead"><span class="fw-bold">144</span> previously read comments in <span class="fw-bold">17</span> discussions</p>
                    <p class="text-muted sub-lead">From February 4th, 2021 to July 1st, 2024</p>
                    <div id="tab" class="tabs btn-group mt-3" role="tablist">
                        <a class="btn" id="tab-everything" data-bs-toggle="tab" href="#tabpanel-everything" role="tab" aria-controls="tabpanel-everything" aria-selected="false"> Show everything </a>
                        <a class="btn active" id="tab-recent" data-bs-toggle="tab" href="#tabpanel-recent" role="tab" aria-controls="tabpanel-recent" aria-selected="true"> Show recent </a>
                        <a class="btn" id="tab-pinned" data-bs-toggle="tab" href="#tabpanel-pinned" role="tab" aria-controls="tabpanel-pinned" aria-selected="false"> Show pinned only </a>
                    </div>
                    <div class="tab-content pt-5 mx-4" id="tab-content">
                        <div class="tab-pane" id="tabpanel-everything" role="tabpanel" aria-labelledby="tab-everything">Everything content</div>
                        <div class="tab-pane active" id="tabpanel-recent" role="tabpanel" aria-labelledby="tab-recent">
                            <SearchBar />
                            <p class="lead mt-5"><span class="fw-bold">10</span> comments in <span class="fw-bold">3</span> discussions in the last 3 months</p>
                            <p class="text-muted sub-lead"><span class="fw-bold">1 new comment</span> since your last visit</p>
                            <Messages :messages=msgs :key="identifier" />
                            <h5 class="mt-5 mb-3">Start new discussion thread</h5>
                            <div class="row">
                                <div id="message-0" class="col-md-8 mx-auto">
                                    <ReplyForm :parentId=0 :author='`Angelo`' /><!-- Todo: Don't forget to change the author -->
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="tabpanel-pinned" role="tabpanel" aria-labelledby="tab-pinned">
                            <PinnedSearchBar />
                            <PinnedMessages :messages=pinnedMsgs :key="identifier" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import Messages from "./components/Messages.vue";
    import SearchBar from "./components/SearchBar.vue";
    import PinnedSearchBar from "./components/PinnedSearchBar.vue";
    import ReplyForm from "./components/ReplyForm.vue";
    import PinnedMessages from "./components/PinnedMessages.vue";
    import {provide} from "vue";
    import { computed } from 'vue';
    import useCommentsUiStore from "./store/commentsUi";
    provide('tr', tr)

    const uiStore = useCommentsUiStore();

    const msgs = computed(() => uiStore.getFilteredParentMessages);
    const pinnedMsgs = computed(() => uiStore.getFilteredPinnedMessages);
</script>

<script>
    export default {
        data() {
            return {
            identifier: +new Date()
            };
        }
    }
</script>

<style scoped>
    .btn-group>.btn:first-child {
        border-top-left-radius: 14px;
        border-bottom-left-radius: 14px;
    }
    .btn-group>.btn:last-child {
        border-top-right-radius: 14px;
        border-bottom-right-radius: 14px;
    }
    .tabs .btn:focus, .tabs .btn:active, .tabs .btn.active {
        color: #ffffff;
        background-color: #aca8a8;
        box-shadow: inset 3px 4px 5px rgba(0, 0, 0, .30);
    }
    .tabs .btn {
        text-shadow: none;
        color: #000000;
        background-color: #e9e9eb;
        background-image: none;
    }

    #app {
        display: grid;
    }

    .sub-lead {
        margin-top: -17px;
    }
</style>
