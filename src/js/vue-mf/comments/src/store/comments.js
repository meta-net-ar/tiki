import { defineStore } from "pinia";
import { listComments } from "../api/comments";
import { mapThreads } from "../api/mapper";
import { escapeHtml } from "../utils/highlight";

// Comment data as served by the backend. Keep local-only UI state (search text,
// manual expand/collapse...) out of this store — see commentsUi.js — so backend
// sync code never has a reason to touch it.
export default defineStore("commentsStore", {
    state: () => ({
        // Tiki object the comments belong to, seeded by the hosting page at mount time.
        // The backend addresses comments as "<type>:<objectId>", so every API call needs both.
        context: { type: "", objectId: "", serviceUrl: "", showTitles: true },
        /** Flat list, nesting carried by parentId. @type {import("../api/mapper").Thread[]} */
        threads: [],
        loading: false,
        error: null,
    }),
    getters: {
        getThreads(state) {
            return state.threads;
        },
        //I don't understand what this one does.  It looks like it returns thread starters - benoitg - 2025-04-29
        getParentMessages(state) {
            var key = "parentId";
            var response = [];
            state.threads.forEach((item) => {
                if (item[key] === 0) {
                    response.push(item);
                }
            });
            return response;
        },
        getPinnedMessages(state) {
            return state.threads.filter((item) => item.pinned === 1);
        },
    },
    actions: {
        setContext({ type, objectId, serviceUrl, showTitles }) {
            this.context = { type, objectId, serviceUrl, showTitles };
        },
        async fetchThreads() {
            this.loading = true;
            this.error = null;
            try {
                const { type, objectId, serviceUrl } = this.context;
                this.threads = mapThreads(await listComments(serviceUrl, { type, objectId }));
            } catch (error) {
                this.error = error.message;
                this.threads = [];
            } finally {
                this.loading = false;
            }
        },
        // Builds a Thread locally, so it must match the mapper's output field for field —
        // see the Thread typedef in api/mapper.js.
        addMessage(title, parentId, author, message, date) {
            this.threads.push({
                title,
                // Not yet persisted, so there is no backend threadId to use. Taking the
                // highest known id keeps this from colliding with a real comment.
                messageId: this.threads.reduce((highest, item) => Math.max(highest, item.messageId), 0) + 1,
                parentId,
                author,
                message,
                // What the user typed is plain text: a real post comes back parsed by Tiki,
                // so until then escape it rather than trusting it as markup.
                messageHtml: escapeHtml(message),
                date,
                // Its author is the current user, so the backend would allow the edit too.
                canEdit: true,
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                pinned: 0,
                resoved: 0,
            });
        },
        updateMessage(messageId, title, message) {
            this.threads = this.threads.map((item) => {
                if (item.messageId === messageId) {
                    return { ...item, title, message, messageHtml: escapeHtml(message) };
                }
                return item;
            });
        },
        like(messageId, user) {
            const thread = this.threads[this.threads.findIndex((element) => element.messageId === messageId)];
            const dislikedIndex = thread.dislikedBy.indexOf(user);
            if (dislikedIndex !== -1) {
                thread.dislikedBy.splice(dislikedIndex, 1);
                thread.dislike -= 1;
            }
            const likedIndex = thread.likedBy.indexOf(user);
            if (likedIndex !== -1) {
                thread.likedBy.splice(likedIndex, 1);
                thread.like -= 1;
            } else {
                thread.likedBy.push(user);
                thread.like += 1;
            }
        },
        dislike(messageId, user) {
            const thread = this.threads[this.threads.findIndex((element) => element.messageId === messageId)];
            const likedIndex = thread.likedBy.indexOf(user);
            if (likedIndex !== -1) {
                thread.likedBy.splice(likedIndex, 1);
                thread.like -= 1;
            }
            const dislikedIndex = thread.dislikedBy.indexOf(user);
            if (dislikedIndex !== -1) {
                thread.dislikedBy.splice(dislikedIndex, 1);
                thread.dislike -= 1;
            } else {
                thread.dislikedBy.push(user);
                thread.dislike += 1;
            }
        },
        togglePinned(messageId) {
            const message = this.threads[this.threads.findIndex((element) => element.messageId === messageId)];
            message.pinned = message.pinned === 1 ? 0 : 1;
        },
        toggleResolved(messageId) {
            const index = this.threads.findIndex((element) => element.messageId === messageId);
            this.threads[index].resoved = this.threads[index].resoved === 1 ? 0 : 1;
            return this.threads[index].resoved;
        },
    },
});
