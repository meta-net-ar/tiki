function autoMoveReplyToTrackerItem() {
    window.addEventListener("new-message", (event) => {
        const messageRow = $(event.detail.row);
        const inReplyTo = messageRow.data('in-reply-to')?.split('@')[0];
        const listPath = messageRow[0].classList[0].replace(`_${event.detail.uid}_`, '_');
        if (!inReplyTo) return;

        Hm_Ajax.request([
            {name: 'hm_ajax_hook', value: 'ajax_tiki_auto_move_reply_to_tracker'},
            {name: 'in_reply_to', value: inReplyTo}
        ], (res) => {
            if (res.auto_move) {
                Hm_Ajax.request([
                    {name: 'hm_ajax_hook', value: 'ajax_move_to_tracker'},
                    {name: 'tracker_item_id', value: res.item_id},
                    {name: 'tracker_field_id', value: res.field_id},
                    {name: 'imap_msg_ids', value: event.detail.uid},
                    {name: 'list_path', value: listPath},
                    {name: 'folder', value: 'inbox'},
                    {name: 'auto_move', value: true},
                ], () => {
                });
            }
        });
    });

    // refresh the unread messages state
    setInterval(() => {
        // undefined_undefined: load with no filter and no keyword
        new Hm_MessagesStore('unread', 1, 'undefined_undefined').load(true, true).then((store) => {
            store.newMessages.forEach((messageRow) => {
                triggerNewMessageEvent($(messageRow).data('uid'), $(messageRow)[0]);
            });
        });
    }, 60000);
}

function promptFutureReplyAutoMove() {
    return new Promise((resolve) => {
        $.openModal({
            title: tr("Move futre replies"),
            content: `<p class="fw-bold">${tr("Do you want to automatically move future replies to this tracker item?")}</p>`,
            buttons: [
                {
                    text: tr("Yes"),
                    class: "btn-primary",
                    onClick: function () {
                        resolve(true);
                        $.closeModal();
                    }
                },
            ],
            open: function () {
                $(this).find('.modal-footer .btn[data-bs-dismiss="modal"]').text(tr("No, thanks"));
                $(this).find('.btn[data-bs-dismiss="modal"]').on('click', function () {
                    resolve(false);
                });
            },
        });
    });
}
