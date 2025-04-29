import { defineStore } from "pinia";

var message1 =
    "We could enforce the title field only for the first post in new top-level threads, but not for follow-up replies. Otherwise, when you enforce that, people just make up random/inconsequential titles, or split their message into two sentence halves that are started by the title and completed by the comment, which is counterproductive. Having more titles than strictly needed adds visual noise and uses precious vertical space, which means more scrolling across long threads.";
var message2 =
    "Not being forced to write a title everytime is refreshing. Why did we subject ourselves to that for so many years? Good thing you're around to make those hard decisions!";
var message3 = "I must inform you that we will need nested threading for comments (though we can flatten linear branches)";
var message4 = "People don't like long threads.";
var message5 = "Yeah, until they need them. But they can be flattened.";
var message6 = "But I feel the urge to talk about cute cats for some reason.";
var message7 = "Yeah, I forgot to tally about the possibility of someone branching in the middle of a (may or may not be a thread drift)";
var message8 = "@RyanGosling @accounting_dept: This is the IRS. We’ve been trying to reach you about your car’s extended warranty";
var message9 = "I need attention so I am subconsciously trying to get more by top posting.";

export default defineStore("messageStore", {
    state: () => ({
        threads: [
            {
                title: "There should be no titles in replies, only for the thread",
                messageId: 1,
                parentId: 0,
                author: "Jeff Fortin",
                message: message1,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                messageId: 2,
                parentId: 1,
                author: "Marc Laporte",
                message: message2,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 3,
                parentId: 2,
                author: "BenoitG",
                message: message3,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                messageId: 4,
                parentId: 3,
                author: "JM Kadimba",
                message: message4,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 5,
                parentId: 4,
                author: "BenoitG",
                message: message5,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                messageId: 6,
                parentId: 4,
                author: "BenoitG",
                message: message6,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 7,
                parentId: 5,
                author: "BenoitG",
                message: message7,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 8,
                parentId: 1,
                author: "unknown",
                message: message8,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 9,
                parentId: 1,
                author: "unknown",
                message: message9,
                date: "2024-07-3, 15:01",
            },
            {
                title: "There should be no titles in replies, only for the thread",
                messageId: 10,
                parentId: 0,
                author: "Jeff Fortin",
                message: message1,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 11,
                parentId: 10,
                author: "Marc Laporte",
                message: message2,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 12,
                parentId: 11,
                author: "BenoitG",
                message: message3,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 13,
                parentId: 10,
                author: "JM Kadimba",
                message: message4,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                messageId: 14,
                parentId: 10,
                author: "BenoitG",
                message: message5,
                date: "2024-07-3, 15:01",
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
    },
    actions: {
        addMessage(data) {
            let num = this.threads.length;
            var message = {
                title: data.title,
                messageId: num,
                parentId: data.parentId,
                author: data.author,
                message: data.message,
                date: data.date,
            };
            this.threads.push(message);
        },
    },
});
