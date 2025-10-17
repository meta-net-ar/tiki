function showMoveToTrackerModal(onSuccess) {
    $.openModal({
        title: tr("Move to tracker item"),
        variants: ["scrollable"],
        content: `
            <p class="fw-bold">${tr("Suggested items:")}</p>
            <div class="d-flex flex-column gap-2 mt-3 suggested-items">
                ${$.IMPORT_LOADER_MARKUP}
            </div>
            <div id="search-markup"></div>
        `,
        open: function () {
            const modal = $(this);

            Hm_Ajax.request([
                {name: 'hm_ajax_hook', value: 'ajax_tiki_msg_tracker_items'},
                {name: 'lookup', value: $('.js-header_subject').text().replace(/[^\w\s\.']/g, '')},
                {name: 'limit', value: 5},
                {name: 'sort_mode', value: 'score_desc'},
            ], (res) => {
                const [searchMarkup, searchHandler] = moveToTrackerItemSearchHandler(onSuccess, unserializeResponseValue(res, 'tracker_fields'));

                modal.find('#search-markup').html(searchMarkup);
                searchHandler(modal, getMsgUid(), getListPathParam(), true);

                const items = unserializeResponseValue(res);
                const suggestedItems = $(".suggested-items", this);
                suggestedItems.empty();

                if (!items.length) {
                    suggestedItems.append(getEmptyItemHtml());
                } else {
                    suggestedItems.append(items.map(item => getItemHtml(item)).join('\n'));
                    suggestedItems.find('.item').on('click', function () {
                        handleItemClick.call(this, modal, getMsgUid(), getListPathParam(), true, onSuccess);
                    });
                }
            });
        }
    });
}

function getMsgUid () {
    const ids = [];
    if (getPageNameParam() == 'message') {
        ids.push(getMessageUidParam());
    } else {
        $('input[type=checkbox]').each(function() {
            if (this.checked && this.id.search('imap') != -1) {
                if (['sent', 'unread', 'combined_inbox', 'flagged'].includes(getListPathParam())) {
                    ids.push(this.id);
                } else {
                    ids.push(this.id.split('_')[2]);
                }
            }
        });
        if (ids.length == 0) {
            return;
        }
    }
    return ids.join(',');
}