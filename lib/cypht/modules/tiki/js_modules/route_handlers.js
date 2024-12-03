applyImapMessageContentPageHandlers = (function (originalHandlers) {
    return function (routeParams) {
        originalHandlers(routeParams);
        RSVPButtonClick();
        tiki_event_rsvp_actions();
        tiki_event_message_headers_actions();
        tiki_setup_move_to_trackers();

        if (routeParams.list_path.substr(0, 14) === 'tracker_folder') {
            tiki_get_message_content();
            Hm_Ajax.add_callback_hook('ajax_imap_folder_expand', expand_tiki_move_to_mailbox);
        }
    };
})(applyImapMessageContentPageHandlers);

applyImapMessageListPageHandlers = (function (originalHandlers) {
    return function (routeParams) {
        originalHandlers(routeParams);
        tiki_setup_move_to_trackers();
    };
})(applyImapMessageListPageHandlers);

applySieveFiltersPageHandler = (function (originalHandlers) {
    return function (routeParams) {
        originalHandlers(routeParams);
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

        if (jqueryTiki.select2) {
            $('select[name="test_type"]').next().remove();
            $('select[name="test_type"]').tiki("select2");
        }
    };
})(applySieveFiltersPageHandler);

applySettingsPageHandlers = (function (originalHandlers) {
    return function (routeParams) {
        originalHandlers(routeParams);
        tiki_enable_oauth2_over_imap();
    };
})(applySettingsPageHandlers);

applyComposePageHandlers = (function (originalHandlers) {
    return function (routeParams) {
        originalHandlers(routeParams);
        if (routeParams.list_path.substr(0, 14) === 'tracker_folder') {
            if (!routeParams.uid) {
                $('.smtp_send_archive').remove();
            } else {
                $('.smtp_send_archive').off('click').on('click', function() { tiki_send_archive(); });
            }
        }
    };
})(applyComposePageHandlers);
