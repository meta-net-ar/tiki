function moveToTrackerItemSearchHandler() {
    const markup = `
    <p class="mt-4 fw-bold">Looking for more?</p>
    <p class="fw-lighter fst-italic">${tr("Enter a keyword below to find more items")}.</p>
    <div class="input-group mt-3">
        <input type="text" class="form-control lookup" placeholder="${tr("Search for more items")}" />
        <button class="btn btn-sm btn-primary lookup-btn">${$.fn.getIcon("search").prop("outerHTML")}</button>
    </div>
    <div class="d-flex flex-column gap-2 mt-3 lookup-results"></div>
    `;

    const handler = (modal, msgUid, listPath, chooseFolder = false) => {
        modal.find('.lookup-btn').on('click', function () {
            const input = modal.find('.lookup');
            const keyword = input.val().trim();
            if (!keyword) {
                return;
            }

            $(this).prop('disabled', true).html($.BUTTON_LOADER_MARKUP);
            Hm_Ajax.request([
                {name: 'hm_ajax_hook', value: 'ajax_tiki_msg_tracker_items'},
                {name: 'lookup', value: keyword},
                {name: 'limit', value: 10},
                {name: 'sort_mode', value: 'score_desc'},
            ], (res) => {
                const items = unserializeTrackerItems(res);
                const resultsContainer = modal.find('.lookup-results');
                resultsContainer.empty();

                if (!items.length) {
                    resultsContainer.append(getEmptyItemHtml());
                } else {
                    resultsContainer.append(items.map(item => getItemHtml(item)).join('\n'));
                    resultsContainer.find('.item').on('click', function () {
                        handleItemClick.call(this, modal, msgUid, listPath, chooseFolder);
                    });
                }

                $(this).prop('disabled', false).html($.fn.getIcon("search").prop("outerHTML"));
            });
        });
    };

    return [markup, handler];
}

