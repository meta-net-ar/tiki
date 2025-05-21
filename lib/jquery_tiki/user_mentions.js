function renderUserMentionModal(areaId) {
    const usernamePattern = jqueryTiki.usernamePattern.substring(2, jqueryTiki.usernamePattern.length - 2); // trim /^ and $/
    $.openModal({
        title: tr("Mention a user"),
        content: `<input type="text" id="user-mentions" placeholder="${tr("Type a username")}" />`,
        dialogVariants: ["centered"],
        open: function () {
            autocomplete($("#user-mentions", this)[0], "userrealname", {
                select: (event) => {
                    const value = event.detail[0].value;
                    const username = value.match(new RegExp("\\((" + usernamePattern + ")\\)$"))[1];
                    insertAt(areaId, "@" + username);
                    $.closeModal();
                },
            });
        },
    });
}

/**
 * Show a dropdown of users following a @ char if...
 */

$.fn.userMentions = function () {
    let usernamePattern = jqueryTiki.usernamePattern;
    usernamePattern = usernamePattern.substring(2, usernamePattern.length - 2);    // trim /^ and $/

    if (window.elementPlus?.autocomplete) {
        const textarea = this;
        $(textarea).on("keydown", function (event) {
            if (event.key === "@") {
                event.preventDefault();
                renderUserMentionModal($(textarea).attr("id"));
            }
        });
    }
};
