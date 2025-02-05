import { createStore } from "vuex";

var comment1 = "We could enforce the title field only for the first post in new top-level threads, but not for follow-up replies. Otherwise, when you enforce that, people just make up random/inconsequential titles, or split their message into two sentence halves that are started by the title and completed by the comment, which is counterproductive. Having more titles than strictly needed adds visual noise and uses precious vertical space, which means more scrolling across long threads.";
var comment2 = "Not being forced to write a title everytime is refreshing. Why did we subject ourselves to that for so many years? Good thing you're around to make those hard decisions!";
var comment3 = "I must inform you that we will need nested threading for comments (though we can flatten linear branches)";
var comment4 = "People don't like long threads.";
var comment5 = "Yeah, until they need them. But they can be flattened.";
var comment6 = "But I feel the urge to talk about cute cats for some reason.";
var comment7 = "Yeah, I forgot to taly about the possibility of someone branching in the middle of a (may or may not be a thread drift)";
var comment8 = "@RyanGosling @accounting_dept: This is the IRS. We’ve been trying to reach you about your car’s extended warranty";
var comment9 = "I need attention so I am subcouciously trying to get more by top posting.";

export default createStore({
    state: {
        threads: [
            {
                title: "There should be no titles in replies, only for the thread",
                commentId: 1,
                parentId: 0,
                author: "Jeff Fortin",
                comment: comment1,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                commentId: 2,
                parentId: 1,
                author: "Marc Laporte",
                comment: comment2,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 3,
                parentId: 2,
                author: "BenoitG",
                comment: comment3,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                commentId: 4,
                parentId: 3,
                author: "JM Kadimba",
                comment: comment4,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 5,
                parentId: 4,
                author: "BenoitG",
                comment: comment5,
                date: "2024-07-3, 15:01",
            },
            {
                title: "This is a reply title",
                commentId: 6,
                parentId: 4,
                author: "BenoitG",
                comment: comment6,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 7,
                parentId: 5,
                author: "BenoitG",
                comment: comment7,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 8,
                parentId: 1,
                author: "unknown",
                comment: comment8,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 9,
                parentId: 1,
                author: "unknown",
                comment: comment9,
                date: "2024-07-3, 15:01",
            },
            {
                title: "There should be no titles in replies, only for the thread",
                commentId: 10,
                parentId: 0,
                author: "Jeff Fortin",
                comment: comment1,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 11,
                parentId: 10,
                author: "Marc Laporte",
                comment: comment2,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 12,
                parentId: 11,
                author: "BenoitG",
                comment: comment3,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 13,
                parentId: 10,
                author: "JM Kadimba",
                comment: comment4,
                date: "2024-07-3, 15:01",
            },
            {
                title: "",
                commentId: 14,
                parentId: 10,
                author: "BenoitG",
                comment: comment5,
                date: "2024-07-3, 15:01",
            },
        ],
    },
    getters: {
        getThreads(state) {
            return state.threads;
        },
        getParentMessages(state) {
            var arr = state.threads;
            var key = "parentId";
            var response = [];
            arr.forEach((item) => {
                if (item[key] === 0) {
                    response.push(item);
                }
            });
            return response;
        },
    },
});
