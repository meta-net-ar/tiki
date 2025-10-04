const COMMENT_SELECTORS = {
    addCommentZone: "div.add-comment-zone",
    commentTitleInput: "[name='title']:visible",
    commentInput: "[name='data']:visible",
    thread: "li[data-comment-thread-id]",
    postNewCommentButton: "div.submit > div.buttons.comment-form > a.custom-handling", // Button to display the new comment form
    commentForm: "form",
    elCommentTitleInput: ".mb-3 el-input",
    formObjectType: "input[name='type']",
    formObjectId: "input[name='objectId']",
    inputCommentPostClass: "input.comment-post",
    inputCommentEditClass: "input.comment-editclass",
    addCommentZoneClass: ".add-comment-zone",
};

let initTriggerFromToolbar = function () {
    const intervalId = setInterval(() => {
        const $textarea = $(`${COMMENT_SELECTORS.addCommentZone} ${COMMENT_SELECTORS.commentForm} textarea${COMMENT_SELECTORS.commentInput}`);

        if ($textarea.length) {
            // Handle toolbar text insertion
            triggerInputFromToolbar($textarea);
            clearInterval(intervalId);
        }
    }, 100);
};

// Update textarea toolbar (comment input) buttons to use the new unique ID
let updateToolbar = function ($form, uniqueId) {
    $form.find(`[onclick*="insertAt('editwiki'"]`).each(function () {
        const clickAction = $(this).attr("onclick");
        if (!clickAction) return;

        const newClickAction = clickAction.replace(/insertAt\('editwiki[^']*'/g, `insertAt('${uniqueId}'`);
        $(this).attr("onclick", newClickAction);
    });
};

// Trigger input action on text insertion via toolbar
let triggerInputFromToolbar = function ($commentInput) {
    let comment = $commentInput.val();
    setInterval(() => {
        const currentComment = $commentInput.val();
        if (currentComment !== comment) {
            comment = currentComment;
            $commentInput.trigger("input");
        }
    }, 300);
};

let restoreNewCommentDraft = function ($form, keys) {
    const $commentTitleInput = $form.find(COMMENT_SELECTORS.commentTitleInput);
    const $elCommentTitleInput = $form.find(COMMENT_SELECTORS.elCommentTitleInput)[0];
    const $commentInput = $form.find(COMMENT_SELECTORS.commentInput);

    // Get the draft comments
    const { title: autoSavedTitle, comment: autoSavedComment } = getDraft(keys);

    // When Element Plus is enabled, use Vue component
    if ($elCommentTitleInput && $elCommentTitleInput.shadowRoot) {
        setElementPlusInputValue($elCommentTitleInput, autoSavedTitle);
    } else {
        if ($commentTitleInput.length && autoSavedTitle) {
            $commentTitleInput.val(autoSavedTitle);
        }
    }

    if (autoSavedComment) {
        $commentInput.val(autoSavedComment);
    }
};

// Get Reply/Edit buttons from a specific thread
let getThreadButtons = function ($threadComment) {
    let $buttons = $threadComment.find("a.btn-primary[data-action]");
    if ($buttons.length > 1) {
        $buttons = $buttons.eq(0);
    }
    return $buttons;
};

// Set the value of comment title when element plus is enabled
let setElementPlusInputValue = function ($elWrapper, value) {
    const input = $elWrapper.shadowRoot.querySelector("input");
    input.value = value;

    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));
};

// Ensure that the new comment form is present on the page
let waitForNewCommentForm = function (callback) {
    const interval = setInterval(() => {
        const $newCommentForm = $("input.comment-post.btn.btn-primary")
            .closest("form")
            .filter(function () {
                return $(this).closest(COMMENT_SELECTORS.addCommentZoneClass).length > 0;
            });

        if ($newCommentForm.length) {
            clearInterval(interval);
            callback($newCommentForm);
        }
    }, 100);
};

// Clear drafts for reply, edit, new comment, or when cancelling a comment (clcking on Cancel button)
let clearAutoSavedComment = function () {
    $(document).on(
        "click",
        "input[type='submit'].comment-post, input[type='submit'].comment-editclass, .comment-post .btn-link a[href='#']",
        function () {
            const $button = $(this);

            const action = getActionFromButton($button);
            if (!action) return;

            const dataAction = getClearActionData($button);
            const { $form, isNewCommentForm, $threadComment, threadId } = dataAction;

            // Do not continue if it is in thread container and there is no thread ID
            if (!isNewCommentForm && $threadComment.length && !threadId) return;

            const keysToClear = getKeysToClear({
                action,
                isNewCommentForm,
                threadId,
                objectType: dataAction.objectType,
                objectId: dataAction.objectId,
            });

            // When clicking on Cancel
            if ($button.is("a[href='#']")) {
                clearDraft(keysToClear);
                return;
            }

            const $commentInput = $form.find(COMMENT_SELECTORS.commentInput);
            if (!$commentInput.length) return;

            const titleValue = getTitleValueFromCommentForm($form);
            const commentValue = $commentInput.val().trim();

            // Ensure that a draft exists for the current comment (either the comment or the title and comment)
            if (shouldClearDraft(titleValue, commentValue)) {
                clearDraft(keysToClear);
            }
        }
    );
};

// Build the comment key structure for autosave
let buildCommentKeys = function ($button, threadId = null) {
    const objectType = $button.data("type");
    const objectId = $button.data("object");

    if (!threadId) {
        // New comment
        return buildKeys(null, null, objectType, objectId);
    }

    // Thread (reply/edit)
    return {
        reply: buildKeys("reply", threadId, objectType, objectId),
        edit: buildKeys("edit", threadId, objectType, objectId),
    };
};

let buildKeys = function (action, threadId, objectType, objectId) {
    const object = `_${objectType}${objectId}`;
    // Keys for new comment
    if (!threadId) {
        return {
            title: `draft_comment_title${object}`,
            comment: `draft_comment${object}`,
        };
    }

    // Keys for comment in thread
    const prefix = action === "reply" ? "draft_reply" : "draft_edit";
    return {
        title: `${prefix}_comment_title_thread:${threadId}${object}`,
        comment: `${prefix}_comment_thread:${threadId}${object}`,
    };
};

// Taking the action associated with the clicked button
let getActionFromButton = function ($button) {
    if ($button.is(COMMENT_SELECTORS.inputCommentPostClass)) return "reply";
    if ($button.is(COMMENT_SELECTORS.inputCommentEditClass)) return "edit";

    // If it's a click on Cancel, find the action linked to Cancel (reply or edit)
    if ($button.is("a[href='#']")) {
        const $parentDiv = $button.closest("div.comment-post");
        if ($parentDiv.find(COMMENT_SELECTORS.inputCommentPostClass).length) return "reply";
        if ($parentDiv.find(COMMENT_SELECTORS.inputCommentEditClass).length) return "edit";
    }
    return null;
};

// Get the data attached to the clicked button
let getClearActionData = function ($button) {
    const $form = $button.closest("form");

    let objectType = $form.find(COMMENT_SELECTORS.formObjectType).val();
    const objectId = $form.find(COMMENT_SELECTORS.formObjectId).val();

    const isNewCommentForm = $button.closest(COMMENT_SELECTORS.addCommentZoneClass).length > 0;
    const $threadComment = $button.closest(COMMENT_SELECTORS.thread);
    const threadId = $threadComment.data("comment-thread-id");

    return {
        $form,
        objectType,
        objectId,
        isNewCommentForm,
        $threadComment,
        threadId,
    };
};
// Get the value of the title field when Element Plus is enabled or disabled
let getTitleValueFromCommentForm = function ($form) {
    const $elCommentTitleInput = $form.find(COMMENT_SELECTORS.elCommentTitleInput)[0];
    if ($elCommentTitleInput && $elCommentTitleInput.shadowRoot) {
        return $elCommentTitleInput.shadowRoot.querySelector("input")?.value?.trim() || "";
    }
    return $form.find(COMMENT_SELECTORS.commentTitleInput).val()?.trim() || "";
};

// Take the keys to be cleared from the local storage
let getKeysToClear = function ({ action, isNewCommentForm, threadId, objectType, objectId }) {
    if (isNewCommentForm) {
        return buildKeys(null, null, objectType, objectId);
    }
    return buildKeys(action, threadId, objectType, objectId);
};

let shouldClearDraft = function (title, comment) {
    return comment && comment.trim() && (!title || title.trim());
};

let autoSaveField = function (keys, fieldName, value) {
    const key = fieldName === "title" ? keys.title : keys.comment;
    saveDraft(key, value.trim());
};

let saveDraft = function (key, value) {
    if (value) {
        localStorage.setItem(key, value);
    } else {
        localStorage.removeItem(key);
    }
};

let clearDraft = function (keys) {
    localStorage.removeItem(keys.title);
    localStorage.removeItem(keys.comment);
};

let hasDraft = function (keys) {
    return !!(localStorage.getItem(keys.title) || localStorage.getItem(keys.comment));
};

let getDraft = function (keys) {
    return {
        title: localStorage.getItem(keys.title),
        comment: localStorage.getItem(keys.comment),
    };
};

// Auto save any new comment
let autoSaveNewComment = function () {
    const namespace = ".autosave-new-comment";

    $(COMMENT_SELECTORS.addCommentZoneClass)
        .off("input" + namespace)
        .on("input" + namespace, `${COMMENT_SELECTORS.commentTitleInput}, ${COMMENT_SELECTORS.commentInput}`, function () {
            const $input = $(this);
            const fieldName = $input.attr("name"); // title | data

            const $newCommentForm = $input.closest(COMMENT_SELECTORS.addCommentZone).find(COMMENT_SELECTORS.commentForm);
            if (!$newCommentForm.length) return;

            const objectType = $newCommentForm.find(COMMENT_SELECTORS.formObjectType).val();
            const objectId = $newCommentForm.find(COMMENT_SELECTORS.formObjectId).val();

            let newCommentKeys = buildKeys(null, null, objectType, objectId);

            // Autosave comment (title or comment)
            autoSaveField(newCommentKeys, fieldName, $input.val());

            // Update toolbar id
            updateToolbar($newCommentForm, $input.attr("id"));
        });

    // Initiating trigger text insertion from the toolbar
    initTriggerFromToolbar();
};

// Autosave reply/edit comment
let autoSaveThreadComment = function () {
    const namespace = ".autosave-thread";

    $(document)
        .off("input" + namespace)
        .on("input" + namespace, `${COMMENT_SELECTORS.commentInput}, ${COMMENT_SELECTORS.commentTitleInput}`, function () {
            const $input = $(this);
            const fieldName = $input.attr("name"); // title | data

            const $form = $input.closest(COMMENT_SELECTORS.commentForm);
            if (!$form.length) return;

            const $thread = $form.closest(COMMENT_SELECTORS.thread);
            if (!$thread.length) return;

            const threadId = $thread.data("comment-thread-id");
            if (!threadId) return;

            const objectType = $form.find(COMMENT_SELECTORS.formObjectType).val();
            const objectId = $form.find(COMMENT_SELECTORS.formObjectId).val();

            // Get action (reply|edit)
            const action = $form.find(COMMENT_SELECTORS.inputCommentEditClass).length ? "edit" : "reply";

            const keys = buildKeys(action, threadId, objectType, objectId);

            let uniqueId = $form.data("autosave-id");
            if (!uniqueId) {
                uniqueId = `editwiki-${threadId}-${Date.now()}`;
                $form.data("autosave-id", uniqueId);
                $form.find(COMMENT_SELECTORS.commentInput).attr("id", uniqueId);
            }

            // Update toolbar Id
            updateToolbar($form, uniqueId);

            // Handle toolbar text insertion
            triggerInputFromToolbar($form.find(COMMENT_SELECTORS.commentInput));

            // Autosave comment
            autoSaveField(keys, fieldName, $input.val());
        });
};

// Restore all replies and edited comments in the threads
let restoreAutoSavedComments = function () {
    setTimeout(() => {
        $(COMMENT_SELECTORS.thread).each(function () {
            const $thread = $(this);
            const threadId = $thread.data("comment-thread-id");

            const $threadComment = $thread.children(".flex-grow-1").children(".comment-item");
            if (!$threadComment.length) return;

            const $buttons = getThreadButtons($threadComment);
            if (!$buttons.length) return;

            const threadKeys = buildCommentKeys($buttons, threadId);
            const hasReplyDraft = hasDraft(threadKeys.reply);
            const hasEditDraft = hasDraft(threadKeys.edit);

            // For each thread, do not continue executing the script if there is no draft
            if (!hasReplyDraft && !hasEditDraft) return;

            const isReply = hasReplyDraft;
            const action = isReply ? "reply" : "edit";
            const concernedKeys = isReply ? threadKeys.reply : threadKeys.edit;

            const $actionButton = $threadComment.find(`a.btn[data-action='${action}']`);
            if (!$actionButton.length) return;
            // Automatically click on reply/edit button to display the form comment
            $actionButton[0].click();

            const interval = setInterval(() => {
                const $commentForm = $threadComment.find(COMMENT_SELECTORS.commentForm);
                const $commentTitleInput = $commentForm.find(COMMENT_SELECTORS.commentTitleInput);
                const $elCommentTitleInput = $commentForm.find(COMMENT_SELECTORS.elCommentTitleInput)[0];
                const $commentInput = $commentForm.find(COMMENT_SELECTORS.commentInput);

                if (!$commentInput.length) return;

                clearInterval(interval);

                let uniqueId = $commentForm.data("autosave-id");
                if (!uniqueId) {
                    uniqueId = `editwiki-${threadId}-${Date.now()}`;
                    $commentForm.data("autosave-id", uniqueId);
                }
                // Add unique ID to textarea (comment input) to prevent unfold textareas from having the same ID
                $commentInput.attr("id", uniqueId);

                // Update toolbar Id
                updateToolbar($commentForm, uniqueId);
                triggerInputFromToolbar($commentForm.find(COMMENT_SELECTORS.commentInput));

                const { title: autoSavedTitle, comment: autoSavedComment } = getDraft(concernedKeys);

                if ($elCommentTitleInput && $elCommentTitleInput.shadowRoot) {
                    setElementPlusInputValue($elCommentTitleInput, autoSavedTitle);
                } else if ($commentTitleInput.length && autoSavedTitle) {
                    $commentTitleInput.val(autoSavedTitle);
                }

                if (autoSavedComment) {
                    $commentInput.val(autoSavedComment);
                }
            }, 100);
        });
    }, 1000);
};

// Restore the new comment if it is autosaved
let restoreAutoSavedComment = function () {
    setTimeout(function () {
        const $button = $(COMMENT_SELECTORS.postNewCommentButton);
        const newCommentKeys = buildCommentKeys($button, null);

        if (!hasDraft(newCommentKeys)) return;

        // Automatically click on Post new comment button to display the form comment
        $button[0].click();

        waitForNewCommentForm(function ($form) {
            restoreNewCommentDraft($form, newCommentKeys);
        });
    }, 1000);
};

// Restore auto-saved comments after canceling
let restoreAutoSavedCommentsOnCancel = function () {
    $(document).on("click", ".comment-cancel", function () {
        restoreAllAutoSavedComments();
        return false;
    });
};

let restoreAllAutoSavedComments = function () {
    restoreAutoSavedComments();
    restoreAutoSavedComment();
    // Restore drafts when cancel button is clicked
    restoreAutoSavedCommentsOnCancel();
};

let initCommentsAutosave = function () {
    // Setup auto-save for reply/edit (in thread) and new comment
    autoSaveThreadComment();
    autoSaveNewComment();

    // Restore any auto-saved comments
    restoreAllAutoSavedComments();

    // Setup clear comment
    clearAutoSavedComment();

    // If the user clicks on "Post new comment" button
    $(document).on("click", COMMENT_SELECTORS.postNewCommentButton, function () {
        autoSaveNewComment();
    });

    // When toggling the comment container in wiki page
    $("#comment-toggle").on("click", function () {
        restoreAllAutoSavedComments();
    });
};

$(function () {
    initCommentsAutosave();
});

window.restoreAutoSavedComments = restoreAutoSavedComments;
window.restoreAutoSavedComment = restoreAutoSavedComment;
