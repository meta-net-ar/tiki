function RSVPButtonClick() {
    let adding_to_calendar_content = null;
    $(document).on('click', '.rsvp-button', function() {
        const value = $(this).data('value');
        const comment = $(this).data('comment');
        const values = {
            accept: 'ACCEPTED',
            decline: 'DECLINED',
            maybe: 'TENTATIVE',
        };
        const content = `
            <div class="form-group">
                <label for="rsvp-comment" class="form-label">${tr('Add a note')}</label>
                <textarea id="rsvp-comment" class="form-control" rows="5" placeholder="${tr('A note that will be sent to participants')}"></textarea>
                ${comment ? `<small class="form-text text-muted">${comment}</small>` : ''}
            </div>
            <div class="form-group mt-2">
                <label for="rsvp-action" class="form-label">${tr('Attending?')}</label>
                <select id="rsvp-action" class="form-control">
                    <option value="accept" ${value === values.accept ? 'selected': ''}>${tr('Yes')}</option>
                    <option value="decline" ${value === values.decline ? 'selected': ''}>${tr('No')}</option>
                    <option value="maybe" ${value === values.maybe ? 'selected': ''}>${tr('Maybe')}</option>
                </select>
            </div>
        `;
        $.openModal({
            title: tr('Response'),
            content: content,
            buttons: [
                {
                    text: tr('Send'),
                    class: 'btn btn-primary',
                    onClick: function() {
                        const comment = $('#rsvp-comment', this).val();
                        const action = $('#rsvp-action', this).val();
                        if (values[action] === value) {
                            return;
                        }
                        if (action == "accept" && $('.event_calendar_select_rsvp').val()) {
                            tiki_event_calendar($('.event_calendar_select_rsvp').val());
                        }

                        // Use single RSVP endpoint with source detection via list_path
                        const detail = Hm_Utils.parse_folder_path(getListPathParam(), 'imap');
                        $.tikiModal("Please wait...");
                        Hm_Ajax.request(
                            [{'name': 'hm_ajax_hook', 'value': 'ajax_rsvp_action'},
                            {'name': 'rsvp_action', 'value': action},
                            {'name': 'rsvp_comment', 'value': comment},
                            {'name': 'imap_msg_uid', 'value': getMessageUidParam()},
                            {'name': 'imap_server_id', 'value': detail.server_id},
                            {'name': 'folder', 'value': detail.folder},
                            {'name': 'list_path', 'value': getListPathParam()}],
                            function() {
                                Hm_Notices.show(tr("Your response has been sent."), "success");
                                $.closeModal();
                                $.tikiModal();
                                sessionStorage.removeItem(window.location.pathname + getMessageStorageKey(getMessageUidParam()));
                                get_message_content(false, getMessageUidParam(), getListPathParam(), detail);
                                if (action === "accept") {
                                    imap_archive_message();
                                }
                            },
                            [],
                            false,
                            [],
                            function () {
                                Hm_Notices.show(tr("An error occurred while sending your response."), "error");
                                $.closeModal();
                                $.tikiModal();
                            }
                        );
                    }
                }
            ],
        });
        adding_to_calendar_content = $('#event_calendar_to_rsvp');
        $('.calendar_content').append(adding_to_calendar_content);
        $('#event_calendar_to_rsvp').removeAttr("style");
    });
    $(document).on('change', '#rsvp-action', function() {
        if (this.value == "accept") {
            $('.calendar_content').append(adding_to_calendar_content);
        } else {
            $('.event_calendar_select_rsvp').val("");
            $('.calendar_content').html("");
        }
    });
}