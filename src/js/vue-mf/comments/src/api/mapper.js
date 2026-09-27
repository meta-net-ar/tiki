// Translates the comment service payload into the flat shape the components expect.
//
// The service returns a tree: each comment carries its children under
// replies_info.replies, recursively. The components instead resolve children by
// scanning a flat list for a matching parentId (see Replies.vue), so the tree has
// to be flattened here rather than reshaping every component.

/**
 * One entry of the commentsStore `threads` array — the only shape the components read.
 *
 * Produced here from the service payload, and by commentsStore.addMessage /
 * updateMessage for a comment not yet sent to the backend. Every producer must fill
 * every field: the components read them unconditionally, so a missing one renders
 * blank rather than failing (an omitted messageHtml showed comments with no body).
 *
 */

function flatten(comments, collected = []) {
    for (const comment of comments) {
        collected.push(comment);
        const replies = comment.replies_info?.replies;
        if (replies?.length) {
            flatten(replies, collected);
        }
    }
    return collected;
}

// `parsed` is wiki markup already rendered to HTML. Searching and editing need the
// text without tags, otherwise a query like "div" would match every comment.
function toPlainText(html) {
    const holder = document.createElement("div");
    holder.innerHTML = html ?? "";
    // Without a separator at block boundaries "<p>foo</p><p>bar</p>" reads as "foobar",
    // which would match searches for text the reader never sees as one word.
    holder.querySelectorAll("br, p, div, li, tr, h1, h2, h3, h4, h5, h6, blockquote, pre").forEach((element) => {
        element.parentNode.insertBefore(document.createTextNode(" "), element.nextSibling);
    });
    return (holder.textContent ?? "").replace(/\s+/g, " ").trim();
}

function formatDate(commentDate) {
    if (!commentDate) {
        return "";
    }
    // The column is a unix timestamp in seconds.
    return new Date(commentDate * 1000).toLocaleString();
}

/** @returns {Thread} */
function mapComment(comment) {
    const html = comment.parsed ?? comment.data ?? "";
    return {
        messageId: comment.threadId,
        parentId: comment.parentId ?? 0,
        author: comment.userName || comment.anonymous_name || "",
        title: comment.title ?? "",
        message: toPlainText(html),
        messageHtml: html,
        date: formatDate(comment.commentDate),
        canEdit: Boolean(comment.can_edit),
        // No backend counterpart yet — the components read these unconditionally.
        like: 0,
        dislike: 0,
        likedBy: [],
        dislikedBy: [],
        pinned: 0,
        resoved: 0,
    };
}

/** @returns {Thread[]} */
export function mapThreads(payload) {
    return flatten(payload.comments ?? []).map(mapComment);
}
