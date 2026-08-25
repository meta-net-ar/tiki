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
        manuallyToggledThreads: [],
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
        // Whether a root thread's reply visibility was manually toggled away from its default.
        isManuallyToggled: (state) => (messageId) => state.manuallyToggledThreads.includes(messageId),
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
        // Whether a root thread's replies should currently be shown: resolved threads default to hidden and
        // others to visible, a manual toggle flips that default either way, and a search match always wins.
        // messageId must be a root thread's own id (parentId === 0) — every call site already only ever
        // passes one, so this intentionally does not walk up the parentId chain to find it.
        areRepliesVisible(state) {
            return (messageId) => {
                const rootThread = useCommentsStore().threads.find((element) => element.messageId === messageId);
                const defaultVisible = !rootThread || rootThread.resoved !== 1;
                const visible = this.isManuallyToggled(messageId) ? !defaultVisible : defaultVisible;
                return visible || this.subtreeMatchesSearch(messageId);
            };
        },
    },
    actions: {
        setSearchQuery(query) {
            this.searchQuery = query;
        },
        // Toggles messageId in/out of manuallyToggledThreads; never touches resoved,
        // so this only affects display, not the actual resolved status.
        toggleRepliesVisibility(messageId) {
            const index = this.manuallyToggledThreads.indexOf(messageId);
            if (index !== -1) {
                this.manuallyToggledThreads.splice(index, 1);
            } else {
                this.manuallyToggledThreads.push(messageId);
            }
        },
        drawMessagesTree() {
            var arr = useCommentsStore().threads;
            arr.forEach((item) => {
                let margins = 6;
                let totalHeight = 0;
                let lastChildHeight = 0;
                let targetClass = "";
                let repliesHidden = false;
                if (item.parentId === 0) {
                    targetClass = "replies-" + item.messageId;
                    margins = 16;
                    repliesHidden = !this.areRepliesVisible(item.messageId);
                }
                let parentBody = $("#body-" + item.messageId).parent(".thread-block");
                totalHeight = parentBody.outerHeight();
                parentBody.children("div.row").each(function () {
                    lastChildHeight = $(this).children().last().outerHeight();
                });
                totalHeight -= lastChildHeight + margins;

                if (repliesHidden && targetClass !== "" && (parentBody.hasClass(targetClass) || parentBody.find("." + targetClass).length > 0)) {
                    totalHeight = 0;
                }

                $("#border-" + item.messageId).css("height", totalHeight);
            });
        },
    },
});
