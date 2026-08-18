import { defineStore } from "pinia";

// Comment data: shared across users, meant to eventually sync with the backend.
// Keep local-only UI state (search text, manual expand/collapse...) out of this
// store — see commentsUi.js — so backend sync code never has a reason to touch it.
export default defineStore("commentsStore", {
    state: () => ({
        threads: [
            {
                title: "There should be no titles in replies, only for the thread",
                messageId: 1,
                parentId: 0,
                author: "Jeff Fortin",
                message:
                    "We could enforce the title field only for the first post in new top-level threads, but not for follow-up replies. Otherwise, when you enforce that, people just make up random/inconsequential titles, or split their message into two sentence halves that are started by the title and completed by the comment, which is counterproductive. Having more titles than strictly needed adds visual noise and uses precious vertical space, which means more scrolling across long threads.",
                date: "2024-07-3, 15:01",
                like: 6,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 1,
                pinned: 0,
            },
            {
                title: "This is a reply title",
                messageId: 2,
                parentId: 1,
                author: "Marc Laporte",
                message:
                    "Not being forced to write a title everytime is refreshing. Why did we subject ourselves to that for so many years? Good thing you're around to make those hard decisions!",
                date: "2024-07-3, 15:01",
                like: 2,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 3,
                parentId: 2,
                author: "BenoitG",
                message: "I must inform you that we will need nested threading for comments (though we can flatten linear branches)",
                date: "2024-07-3, 15:01",
                like: 1,
                dislike: 4,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "This is a reply title",
                messageId: 4,
                parentId: 3,
                author: "JM Kadimba",
                message: "People don't like long threads.",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 5,
                parentId: 4,
                author: "BenoitG",
                message: "Yeah, until they need them. But they can be flattened.",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "This is a reply title",
                messageId: 6,
                parentId: 4,
                author: "BenoitG",
                message: "But I feel the urge to talk about cute cats for some reason.",
                date: "2024-07-3, 15:01",
                like: 1,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 7,
                parentId: 5,
                author: "BenoitG",
                message: "Yeah, I forgot to tally about the possibility of someone branching in the middle of a (may or may not be a thread drift)",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 1,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 8,
                parentId: 1,
                author: "unknown",
                message: "@RyanGosling @accounting_dept: This is the IRS. We’ve been trying to reach you about your car’s extended warranty",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 9,
                parentId: 1,
                author: "unknown",
                message: "I need attention so I am subconsciously trying to get more by top posting.",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "What is Lorem Ipsum?",
                messageId: 10,
                parentId: 0,
                author: "Jeff Fortin",
                message:
                    "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 11,
                parentId: 10,
                author: "Marc Laporte",
                message:
                    "There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable.",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 12,
                parentId: 11,
                author: "BenoitG",
                message:
                    "If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text.",
                date: "2024-07-3, 15:01",
                like: 2,
                dislike: 1,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 13,
                parentId: 10,
                author: "JM Kadimba",
                message:
                    'The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from "de Finibus Bonorum et Malorum" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.',
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
            {
                title: "",
                messageId: 14,
                parentId: 10,
                author: "BenoitG",
                message:
                    "There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable.",
                date: "2024-07-3, 15:01",
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
                pinned: 0,
            },
        ],
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
        addMessage(title, parentId, author, message, date) {
            let num = this.threads.length + 1;
            var message = {
                title: title,
                messageId: num,
                parentId: parentId,
                author: author,
                message: message,
                date: date,
                like: 0,
                dislike: 0,
                likedBy: [],
                dislikedBy: [],
                resoved: 0,
            };
            this.threads.push(message);
        },
        updateMessage(messageId, title, message) {
            this.threads = this.threads.map((item) => {
                if (item.messageId === messageId) {
                    return { ...item, title: title, message: message };
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
