<template>
    <div id="app">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-12">
                    <p class="lead"><span class="fw-bold">144</span> previously read comments in <span class="fw-bold">17</span> discussions</p>
                    <p class="text-muted sub-lead">From February 4th, 2021 to July 1st, 2024</p>
                    <div id="tab" class="tabs btn-group mt-3" role="tablist">
                        <a class="btn" id="fill-tab-0" data-bs-toggle="tab" href="#fill-tabpanel-0" role="tab" aria-controls="fill-tabpanel-0" aria-selected="true"> Show everything </a>
                        <a class="btn active" id="fill-tab-1" data-bs-toggle="tab" href="#fill-tabpanel-1" role="tab" aria-controls="fill-tabpanel-1" aria-selected="false"> Show recent </a>
                        <a class="btn" id="fill-tab-2" data-bs-toggle="tab" href="#fill-tabpanel-2" role="tab" aria-controls="fill-tabpanel-2" aria-selected="false"> Show pinned only </a>
                    </div>
                    <div class="tab-content pt-5 mx-4" id="tab-content">
                        <div class="tab-pane" id="fill-tabpanel-0" role="tabpanel" aria-labelledby="fill-tab-0">Everything content</div>
                        <div class="tab-pane active" id="fill-tabpanel-1" role="tabpanel" aria-labelledby="fill-tab-1">
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
                        <div class="tab-pane" id="fill-tabpanel-2" role="tabpanel" aria-labelledby="fill-tab-2">Pinned Content</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
    import Messages from "./components/Messages.vue";
    import SearchBar from "./components/SearchBar.vue";
    import ReplyForm from "./components/ReplyForm.vue";
    import {provide} from "vue";
    import { computed } from 'vue';
    import useMessageStore from "./store";
    provide('tr', tr)

    const store = useMessageStore();

    const msgs = computed(() => store.getFilteredParentMessages);
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
