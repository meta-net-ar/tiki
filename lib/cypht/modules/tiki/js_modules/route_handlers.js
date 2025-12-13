applyImapMessageContentPageHandlers = (function (originalHandlers) {
    return function (...params) {
        const messageLoadedHandler = () => {
            tiki_setup_move_to_trackers();
        };
        window.addEventListener('message-loaded', messageLoadedHandler, { once: true });

        originalHandlers(...params);
        RSVPButtonClick();
        tiki_event_rsvp_actions();
        tiki_event_message_headers_actions();
        getSentMsgTrackerItems();

        const routeParams = params[0];
        if (routeParams.list_path.substr(0, 14) === 'tracker_folder') {
            window.removeEventListener('message-loaded', messageLoadedHandler);
            window.addEventListener('message-loaded', messageLoadedHandler, { once: true });
            tiki_get_message_content();
            Hm_Ajax.add_callback_hook('ajax_imap_folder_expand', expand_tiki_move_to_mailbox);
        }

        return () => {
            window.removeEventListener('message-loaded', messageLoadedHandler);
        };
    };
})(applyImapMessageContentPageHandlers);

applyImapMessageListPageHandlers = (function (originalHandlers) {
    return function (...params) {
        const handlerOutput = originalHandlers(...params);
        tiki_setup_move_to_trackers();

        return handlerOutput;
    };
})(applyImapMessageListPageHandlers);

applySieveFiltersPageHandler = (function (originalHandlers) {
    return function (...params) {
        const handlerOutput = originalHandlers(...params);
        tiki_setup_move_to_trackers(function(e) {
            $(e.target)
                .closest('td')
                .find("[name^=sieve_selected_action_value]")
                .val(JSON.stringify({
                    itemId: parseInt($(this).val().replace('trackeritem:', '')),
                    fieldId: e.data.field,
                    folder:  e.data.folder,
                })
                .replaceAll('"', "'"))
                .trigger('change');
            $('.move_to_trackers').hide();
        });

        return handlerOutput;
    };
})(applySieveFiltersPageHandler);

applySettingsPageHandlers = (function (originalHandlers) {
    return function (...params) {
        const handlerOutput = originalHandlers(...params);
        tiki_enable_oauth2_over_imap();

        return handlerOutput;
    };
})(applySettingsPageHandlers);

applySearchPageHandlers = (function (originalHandlers) {
    return function (...params) {
        const handlerOutput = originalHandlers(...params);
        tiki_setup_move_to_trackers();

        return handlerOutput;
    };
})(applySearchPageHandlers);

applyComposePageHandlers = (function (originalHandlers) {
    return function (...params) {
        const handlerOutput = originalHandlers(...params);
        getSentMsgTrackerItems();

        const routeParams = params[0];
        if (routeParams.list_path?.substr(0, 14) === 'tracker_folder') {
            if (!routeParams.uid) {
                $('.smtp_send_archive').remove();
            } else {
                $('.smtp_send_archive').off('click').on('click', function() { tiki_send_archive(); });
            }
        }

        return handlerOutput;
    };
})(applyComposePageHandlers);
