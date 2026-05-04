function unserializeResponseValue(response, key = 'tracker_items') {
    const value = [];
    if (response[key]) {
        for (const [_, item] of Object.entries(response[key])) {
            value.push(item);
        }
    }
    return value;
}

function getItemHtml(item) {
    return `<div class="item p-2 rounded bg-secondary-subtle text-primary" data-item-id="${item.object_id}" data-tracker-id="${item.tracker_id}" data-field-id="${item.field_id}" role="button">
        ${$.fn.getIcon("arrow-right").prop("outerHTML")} ${item.title}
    </div>`;
}

function getEmptyItemHtml() {
    return `<div class="p-2 rounded bg-secondary-subtle text-muted">
        ${$.fn.getIcon("exclamation-triangle").prop("outerHTML")} ${tr("No items found.")}
    </div>`;
}

async function handleItemClick(modal, msgUid, listPath, chooseFolder = false, onSuccess = null) {
    const itemId = $(this).data('item-id');
    const fieldId = $(this).data('field-id');

    let folder = 'sent';
    if (chooseFolder) {
        folder = await showFolderPicker($(this));
        if (!folder) {
            return;
        }
    }

    if (isTrackerPath()) {
        const m = listPath.match(/tracker_folder_(\d+)_(\d+)/);

        if (itemId == m[1] && fieldId == m[2] && trk_item_email_folder == folder) {
            alert(tr("This message is already in the selected tracker item and folder."));
            return;
        }
    }

    const autoMove = await promptFutureReplyAutoMove();

    modal.tikiModal(tr("Loading..."));
    Hm_Ajax.request([
        {name: 'hm_ajax_hook', value: 'ajax_move_to_tracker'},
        {name: 'tracker_item_id', value: itemId},
        {name: 'tracker_field_id', value: fieldId},
        {name: 'imap_msg_ids', value: msgUid},
        {name: 'list_path', value: listPath},
        {name: 'folder', value: folder},
        {name: 'auto_move', value: autoMove},
    ], () => {
        modal.tikiModal();
        $.closeModal();
        if (onSuccess) {
            onSuccess();
        }
    });
}

async function showFolderPicker(itemElement) {
    return new Promise((resolve) => {
        let dropdown = itemElement.next('.dropdown-menu');

        if (!dropdown.length) {
            itemElement.wrap('<div class="dropdown"></div>');
            dropdown = $(`<div class="dropdown-menu"></div>`);
            itemElement.after(dropdown);
        }

        // close any existing dropdowns
        itemElement.closest(".modal").find(".dropdown-menu.show").each(function () {
            if ($(this)[0] !== dropdown[0]) {
                $(this).removeClass("show");
            }
        });

        bootstrap.Dropdown.getOrCreateInstance(itemElement[0], {
            autoClose: 'outside',
            display: 'static',
        }).toggle();

        if (dropdown.find(".dropdown-item").length) {
            return;
        }

        dropdown.html($.IMPORT_LOADER_MARKUP);

        $.getJSON($.service('tracker', 'getItemEmailFolders', {
            itemId: itemElement.data('item-id'),
            fieldId: itemElement.data('field-id'),
        }), (res) => {
            dropdown.empty();
            res.folders.forEach(folder => {
                const folderItem = $(`<a class="dropdown-item" href="#">${$.fn.getIcon("folder").prop("outerHTML")} ${folder.label}</a>`);
                folderItem.on('click', function (e) {
                    e.preventDefault();
                    resolve(folder.name);
                });
                dropdown.append(folderItem);
            });

            if (!res.useFolders) {
                dropdown.append(`
                    <div class="dropdown-divider"></div>
                    <div class="text-warning small py-2 px-4">${$.fn.getIcon("exclamation-triangle").prop("outerHTML")} ${tr("To see more folders, configure them in the field options.")}</div>
                `);
            }
        }).fail(() => {
            dropdown.empty();
            showMessage(tr("An error occurred while fetching folders."), "error");
        });
    });
}
