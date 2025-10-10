function getSentMsgTrackerItems() {
    Hm_Ajax.request([
        {name: 'hm_ajax_hook', value: 'ajax_tiki_msg_tracker_items'},
    ], (globalRes) => {
        if (globalRes.error) return;
        const trackerItems = unserializeResponseValue(globalRes);

        const [searchMarkup, searchHandler] = moveToTrackerItemSearchHandler(null, unserializeResponseValue(globalRes, 'tracker_fields'));

        $.openModal({
            title: tr("Move to tracker item"),
            variants: ["scrollable"],
            content: (() => {
                return `
                <p class="fw-bold">${tr("Would you like to move the sent message to a tracker item?")}</p>
                <p class="fw-lighter fst-italic">${tr("The following items seem to be good matches")}:</p>
                <div class="d-flex flex-column gap-2 mt-3">
                    ${trackerItems.map(item => getItemHtml(item)).join('\n')}
                    ${!trackerItems.length ? getEmptyItemHtml(): ''}
                </div>
                ${searchMarkup}
            `;
            })(),
            open: function () {
                const modal = $(this);
                $(this).find('.item').on('click', function () { handleItemClick.call(this, modal, globalRes.msg_uid, globalRes.list_path); });
                searchHandler(modal, globalRes.msg_uid, globalRes.list_path);
            }
        });
    });
}
