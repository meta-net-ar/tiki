function getSentMsgTrackerItems() {
    Hm_Ajax.request([
        {name: 'hm_ajax_hook', value: 'ajax_tiki_msg_tracker_items'},
    ], (globalRes) => {
        if (globalRes.error) return;
        const trackerItems = unserializeTrackerItems(globalRes);

        $.openModal({
            title: tr("Move to tracker item"),
            content: (() => {
                return `
                <p class="fw-bold">${tr("Would you like to move the sent message to a tracker item?")}</p>
                <p class="fw-lighter fst-italic">${tr("The following items seem to be good matches")}:</p>
                <div class="d-flex flex-column gap-2 mt-3">
                    ${trackerItems.map(item => getItemHtml(item)).join('\n')}
                    ${!trackerItems.length ? getEmptyItemHtml(): ''}
                </div>
                <p class="mt-4 fw-bold">Looking for more?</p>
                <p class="fw-lighter fst-italic">${tr("Enter a keyword below to find more items")}.</p>
                <div class="input-group mt-3">
                    <input type="text" class="form-control lookup" placeholder="${tr("Search for more items")}" />
                    <button class="btn btn-sm btn-primary lookup-btn">${$.fn.getIcon("search").prop("outerHTML")}</button>
                </div>
                <div class="d-flex flex-column gap-2 mt-3 lookup-results"></div>
            `;
            })(),
            open: function () {
                const modal = $(this);
                $(this).find('.item').on('click', function () { handleItemClick.call(this, modal, globalRes.msg_uid, globalRes.list_path); });

                $(this).find('.lookup-btn').on('click', function () {
                    const input = modal.find('.lookup');
                    const keyword = input.val().trim();
                    if (!keyword) {
                        return;
                    }

                    $(this).prop('disabled', true).html($.BUTTON_LOADER_MARKUP);
                    Hm_Ajax.request([
                        {name: 'hm_ajax_hook', value: 'ajax_tiki_msg_tracker_items'},
                        {name: 'lookup', value: keyword},
                    ], (res) => {
                        const items = unserializeTrackerItems(res);
                        const resultsContainer = modal.find('.lookup-results');
                        resultsContainer.empty();

                        if (!items.length) {
                            resultsContainer.append(getEmptyItemHtml());
                        } else {
                            resultsContainer.append(items.map(item => getItemHtml(item)).join('\n'));
                            resultsContainer.find('.item').on('click', function () {
                                handleItemClick.call(this, modal, globalRes.msg_uid, globalRes.list_path);
                            });
                        }

                        $(this).prop('disabled', false).html($.fn.getIcon("search").prop("outerHTML"));
                    });
                });
            }
        });
    });
}

function unserializeTrackerItems(response) {
    const trackerItems = [];
    if (response.tracker_items) {
        for (const [_, item] of Object.entries(response.tracker_items)) {
            trackerItems.push(item);
        }
    }
    return trackerItems;
}

function getItemHtml(item) {
    return `<div class="item p-2 rounded bg-secondary-subtle text-primary" data-item-id="${item.object_id}" data-tracker-id="${item.parent_id}" data-field-id="${item.field_id}" role="button">
        ${$.fn.getIcon("arrow-right").prop("outerHTML")} ${item.title}
    </div>`;
}

function getEmptyItemHtml() {
    return `<div class="item p-2 rounded bg-secondary-subtle text-muted">
        ${$.fn.getIcon("exclamation-triangle").prop("outerHTML")} ${tr("No items found.")}
    </div>`;
}

function handleItemClick(modal, msgUid, listPath) {
    const itemId = $(this).data('item-id');
    const fieldId = $(this).data('field-id');

    modal.tikiModal(tr("Loading..."));
    Hm_Ajax.request([
        {name: 'hm_ajax_hook', value: 'ajax_move_to_tracker'},
        {name: 'tracker_item_id', value: itemId},
        {name: 'tracker_field_id', value: fieldId},
        {name: 'imap_msg_ids', value: msgUid},
        {name: 'list_path', value: listPath},
        {name: 'folder', value: 'sent'},
    ], () => {
        modal.tikiModal();
        $.closeModal();
    });
}
