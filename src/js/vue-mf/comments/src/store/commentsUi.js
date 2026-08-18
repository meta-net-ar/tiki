import { defineStore } from "pinia";
import useCommentsStore from "./comments";

// Local-only view state for this user's current session: never fetched from,
// or sent to, the backend. Keep it separate from comments.js (the data store)
// so backend sync code has no reason to ever import this store.
export default defineStore("commentsUiStore", {
    state: () => ({
        // TODO: replace with the actual logged-in user once auth is wired into this module
        currentUser: "Angelo",
        searchQuery: "",
        manuallyExpandedThreads: [], // messageIds of resolved root threads whose replies were manually expanded via the "Show replies" button.
    }),
    getters: {
        // Whether a single item (not its descendants) matches the current search query.
        itemMatchesSearch: (state) => (item) => {
            const query = state.searchQuery.trim().toLowerCase();
            if (!query) {
                return false;
            }
            return (
                item.title.toLowerCase().includes(query) || item.message.toLowerCase().includes(query) || item.author.toLowerCase().includes(query)
            );
        },
        // Whether a resolved thread's replies were manually expanded despite the collapse.
        isManuallyExpanded: (state) => (messageId) => state.manuallyExpandedThreads.includes(messageId),
        // Whether an item, or any of its descendants, matches the current search query.
        subtreeMatchesSearch(state) {
            return (messageId) => {
                if (!state.searchQuery.trim()) {
                    return false;
                }
                const threads = useCommentsStore().threads;
                const item = threads.find((element) => element.messageId === messageId);
                if (!item) {
                    return false;
                }
                if (this.itemMatchesSearch(item)) {
                    return true;
                }
                return threads.some((child) => child.parentId === messageId && this.subtreeMatchesSearch(child.messageId));
            };
        },
        // Root threads to display: all of them when not searching, otherwise only those whose subtree (self or a descendant) matches the query.
        getFilteredParentMessages(state) {
            const parents = useCommentsStore().getParentMessages;
            if (!state.searchQuery.trim()) {
                return parents;
            }

            return parents.filter((root) => this.subtreeMatchesSearch(root.messageId));
        },
        // Whether a resolved thread's replies should be shown despite the collapse: either the user expanded them manually, or the current search matches inside.
        isThreadForciblyVisible(state) {
            return (messageId) => this.isManuallyExpanded(messageId) || this.subtreeMatchesSearch(messageId);
        },
    },
    actions: {
        setSearchQuery(query) {
            this.searchQuery = query;
        },
        // Toggles messageId in/out of manuallyExpandedThreads; never touches resoved,
        // so this only affects display, not the actual resolved status.
        toggleManualExpand(messageId) {
            const index = this.manuallyExpandedThreads.indexOf(messageId);
            if (index !== -1) {
                this.manuallyExpandedThreads.splice(index, 1);
            } else {
                this.manuallyExpandedThreads.push(messageId);
            }
        },
        drawMessagesTree() {
            var arr = useCommentsStore().threads;
            arr.forEach((item) => {
                let margins = 6;
                let totalHeight = 0;
                let lastChildHeight = 0;
                let targetClass = "";
                let resolved = false;
                if (item.parentId === 0) {
                    targetClass = "replies-" + item.messageId;
                    margins = 16;
                    // Treat the thread as "not resolved" for tree-line purposes when its replies
                    // are actually being shown (manual expand or search match), so the connecting
                    // line still gets drawn instead of being zeroed out below.
                    resolved = item.resoved === 1 && !this.isThreadForciblyVisible(item.messageId);
                }
                let parentBody = $("#body-" + item.messageId).parent(".thread-block");
                totalHeight = parentBody.outerHeight();
                parentBody.children("div.row").each(function () {
                    lastChildHeight = $(this).children().last().outerHeight();
                });
                totalHeight -= lastChildHeight + margins;

                if (resolved && targetClass !== "" && (parentBody.hasClass(targetClass) || parentBody.find("." + targetClass).length > 0)) {
                    totalHeight = 0;
                }

                $("#border-" + item.messageId).css("height", totalHeight);
            });
        },
    },
});
