var tiki_groupmail_content = function(id, folder) {
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_groupmail'},
        {'name': 'folder', 'value': folder},
        {'name': 'imap_server_ids', 'value': id}],
        function(res) {
            var ids = res.imap_server_ids.split(',');
            if (folder) {
                var i;
                for (i=0;i<ids.length;i++) {
                    ids[i] = ids[i]+'_'+Hm_Utils.clean_selector(folder);
                }
            }
            if (res.auto_sent_folder) {
                add_auto_folder(res.auto_sent_folder);
            }
            Hm_Message_List.update(ids, res.formatted_message_list, 'imap');
        },
        [],
        false,
        function() { Hm_Message_List.set_message_list_state('formatted_tiki_groupmail'); }
    );
    return false;
};

var tiki_groupmail_take = function(btn, id) {
    var detail = Hm_Utils.parse_folder_path(id);
    $(btn).text(tr('Taking')+'...');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_take_groupmail'},
        {'name': 'msgid', 'value': id},
        {'name': 'imap_msg_uid', 'value': detail.uid},
        {'name': 'imap_server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder}],
        function(res) {
            if (res.operator) {
                $(btn).text(res.operator);
            } else {
                $(btn).text(tr('TAKE'));
            }
            tiki_groupmail_content(detail.server_id, detail.folder);
        },
        [],
        false
    );
};

var tiki_groupmail_put_back = function(btn, id) {
    var detail = Hm_Utils.parse_folder_path(id);
    $(btn).text(tr('Putting back')+'...');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_put_back_groupmail'},
        {'name': 'msgid', 'value': id},
        {'name': 'imap_msg_uid', 'value': detail.uid},
        {'name': 'imap_server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder}],
        function(res) {
            if (res.item_removed) {
                $(btn).text(tr('TAKE'));
            }
            tiki_groupmail_content(detail.server_id, detail.folder);
        },
        [],
        false
    );
};

// Helper function to detect if we're viewing a tracker email
function isTrackerPath() {
    return (getListPathParam() || '').indexOf('tracker_folder_') === 0;
}

var tiki_event_calendar = function(calendar_id) {
    var uid = getMessageUidParam();
    var detail = Hm_Utils.parse_folder_path(getListPathParam(), 'imap');
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_add_to_calendar'},
        {'name': 'calendar_id', 'value': calendar_id},
        {'name': 'imap_msg_uid', 'value': uid},
        {'name': 'imap_server_id', 'value': detail.server_id},
        {'name': 'folder', 'value': detail.folder},
        {'name': 'list_path', 'value': getListPathParam()}],
        function(res) {
            // noop
        },
        [],
        false
    );
};

var tiki_event_rsvp_actions = function() {
    $(document).on("change", 'select.event_calendar_select', function(e) {
        var $btn = $(this);
        tiki_event_calendar($(this).val());
    });
    $(document).on("click", '.event_calendar_update', function(e) {
        var uid = getMessageUidParam();
        var $btn = $(this);
        var detail = Hm_Utils.parse_folder_path(getListPathParam(), 'imap');
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_update_in_calendar'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder},
            {'name': 'list_path', 'value': getListPathParam()}],
            function(res) {
                // noop
            },
            [],
            false
        );
    });
    $(document).on("click", '.event_update_participant_status', function(e) {
        e.preventDefault();
        var uid = getMessageUidParam();
        var $btn = $(this);
        var detail = Hm_Utils.parse_folder_path(getListPathParam(), 'imap');
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_update_participant_status'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder},
            {'name': 'list_path', 'value': getListPathParam()}],
            function(res) {
                $('.event_update_participant_status').text("Participant status updated");
                $('.event_update_participant_status').toggleClass('event_update_participant_status event_participant_status_updated');
            },
            [],
            false
        );
    });
    $(document).on("click", '.event_remove_from_calendar', function(e) {
        e.preventDefault();
        var uid = getMessageUidParam();
        var $btn = $(this);
        var detail = Hm_Utils.parse_folder_path(getListPathParam(), 'imap');
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_remove_from_calendar'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_server_id', 'value': detail.server_id},
            {'name': 'folder', 'value': detail.folder},
            {'name': 'list_path', 'value': getListPathParam()}],
            function(res) {
                // noop
            },
            [],
            false
        );
    });
};

var tiki_event_message_headers_actions = function(){
    $(document).on("click",'#print_pdf', function(e) {
        e.preventDefault();
        var uid = getMessageUidParam();
        var header_subject= $('.js-header_subject').text();
        var header_date= $('.js-header_date').text().replace(/>/g, "").replace(/</g, "");
        var header_from= $('.js-header_from').text().replace(/>/g, "").replace(/</g, "");
        var header_to= $('.js-header_to').text().replace(/>/g, "").replace(/</g, "");
        var msg_text= $('.msg_text_inner').html();

        var params = [
            { name: 'page', value: 'message' },
            { name: 'uid', value: uid },
            { name: 'header_subject', value: header_subject },
            { name: 'header_date', value: header_date },
            { name: 'header_from', value: header_from },
            { name: 'header_to', value: header_to },
            { name: 'msg_text', value: msg_text },
            { name: 'display', value: 'pdf' },
        ];

        if($('.js-header_cc') && $('.js-header_cc').text()){
            var header_cc= $('.js-header_cc').text().replace('<','').replace('>','');
            params.push(
                { name: 'header_cc', value: header_cc });
        }

        non_ajax_submit('tiki-webmail.php?page=message&uid='+uid+'&list_path='+getListPathParam()+'&list_parent='+hm_list_parent(), 'POST', params);
    });
};

var non_ajax_submit = function(action, method, values) {
    var form = $('<form/>', {
        action: action,
        method: method,
        target: '_blank'
    });
    $.each(values, function() {
        form.append($('<input/>', {
            type: 'hidden',
            name: this.name,
            value: this.value
        }));
    });
    form.appendTo('body').trigger("submit");
    form.remove();
};

var tiki_Hm_Ajax_Request = function() {
    var new_request = new Hm_Ajax_Request();
    new_request.fail = function(xhr, not_callable) {
        if (xhr.status && xhr.status == 500) {
            Hm_Notices.show('Internal Server Error - check server log file for details.', 'danger');
        } else if (not_callable === true) {
            Hm_Notices.show('Could not perform action - your session probably expired. Please reload page.', 'danger');
        } else {
            $('.offline').show();
        }
        Hm_Ajax.err_condition = true;
        this.run_on_failure();
    };
    new_request.format_xhr_data = function(data) {
        var res = [];
        for (var i in data) {
            res.push(encodeURIComponent(data[i]['name']) + '=' + encodeURIComponent(data[i]['value']));
        }
        if ($('#hm_session_prefix').length > 0) {
            res.push(encodeURIComponent('hm_session_prefix') + '=' + encodeURIComponent($('#hm_session_prefix').val()));
        }
        return res.join('&');
    };
    return new_request;
};

var tiki_enable_oauth2_over_imap = function (){
    if ($('input.tiki_enable_oauth2_over_imap').is(':checked')){
        $(".oauth").addClass("reveal-if-checked");
        $(".oauth").removeClass("reveal-if-unchecked");
    }else {
        $(".oauth").addClass("reveal-if-unchecked");
        $(".oauth").removeClass("reveal-if-checked");
    }
    $(document).on("click", ".tiki_enable_oauth2_over_imap",function(){
        if( $(this).is(':checked') ){
            $(".oauth").addClass("reveal-if-checked");
            $(".oauth").removeClass("reveal-if-unchecked");
        }else {
            $(".oauth").addClass("reveal-if-unchecked");
            $(".oauth").removeClass("reveal-if-checked");
        }
    });
};

var tiki_setup_move_to_trackers = function(callback_handler = null) {
    const handleMoveSuccess = () => {
        if (getMessageUidParam()) {
            Hm_Message_List.prev_next_links(getMessageUidParam(), getParam('list_parent'), function(links) {
                if (links[1]) {
                    window.location.href = links[1];
                } else {
                    window.location.href = '?page=message_list&list_path=' + getParam('list_parent');
                }
            });
        } else {
            window.location.reload();
        }
    };

    $("#move_to_trackers").on("click", function(e) {
        e.preventDefault();
        showMoveToTrackerModal(handleMoveSuccess);
        return false;
    });

    var $el;

    $('.tiki_folder_trigger').on('click', function(e) {
        e.preventDefault();
        $(this).next().toggle();
    });
    $(document).off('click', '.item_to_trackers a.object_selector_trigger').on('click', '.item_to_trackers a.object_selector_trigger', function(e){
        $el = $(this);
        $.clickModal({ title: '', size: 'modal-lg', success: modalCallbackForSuccess, open: updateEmailTitle }, 'tiki-ajax_services.php?controller=tracker&action=insert_item&trackerId='+$(this).data('tracker'))(e);
    });

    var updateEmailTitle = function() {
        if (getPageNameParam() == 'message_list') {
            var subjects = [];
            var title = '';
            $('input[type=checkbox]').each(function() {
                if (this.checked && this.id.search('imap') != -1) {
                    subjects.push($(this).parent().parent().find('.subject').text());
                }
            });
            if (subjects.length) {
                title = '<ul>';
                title += subjects.map(subject => `<li>${subject}</li>`).join('');
                title += '</ul>';
            }
        } else {
            var title = $('.header_subject:first-child').text();
        }
        var translated_txt = tr('Emails can be copied or moved here');
        $('div[id^="trackerinput_"]:contains('+ translated_txt +')').html(title);
    };

    var selected_ids = function() {
        var ids = [];
        if (getPageNameParam() == 'message') {
            ids.push(getMessageUidParam());
        } else {
            $('input[type=checkbox]').each(function() {
                if (this.checked && this.id.search('imap') != -1) {
                    if (['sent', 'unread', 'combined_inbox', 'flagged', 'search'].includes(getListPathParam() ?? getPageNameParam())) {
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
    };

    var modalCallbackForSuccess = async function(data) {
        var ids = selected_ids();
        if (ids.length == 0) {
            return;
        }

        const autoMove = await promptFutureReplyAutoMove();

        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_move_to_tracker'},
            {'name': 'tracker_field_id', 'value': $el.data('field')},
            {'name': 'tracker_item_id', 'value': data.itemId},
            {'name': 'imap_msg_ids', 'value': ids},
            {'name': 'list_path', 'value': getListPathParam()},
            {'name': 'folder', 'value': $el.data('folder')},
            {'name': 'auto_move', 'value': autoMove}],
            function() {
                $.closeModal();
                handleMoveSuccess();
            },
            [],
            false
        );
    };

    $('.move_to_trackers a.object_selector_trigger').on('click', function(e) {
        e.preventDefault();
        var $el = $(this);
        var $object_selector = $el.parent().find('.object-selector');
        if ($el.next().hasClass('object-selector') && $object_selector.is(':visible')) {
            $object_selector.toggle();
            return;
        }
        $object_selector.remove();
        var url = $.service('search', 'object_selector', {
            params: {
                _name: 'move_to_trackers',
                object_type: 'trackeritem',
                tracker_id: $el.data('tracker')
            }
        });
        $.ajax({
            url: url,
            dataType: 'json',
            success: function(data) {
                $el.after(data.selector);
                var default_callback = async function() {
                    var ids = selected_ids();
                    if (ids.length == 0) {
                        return;
                    }
                    const autoMove = await promptFutureReplyAutoMove();
                    Hm_Ajax.request(
                        [{'name': 'hm_ajax_hook', 'value': 'ajax_move_to_tracker'},
                        {'name': 'tracker_field_id', 'value': $el.data('field')},
                        {'name': 'tracker_item_id', 'value': $(this).val().replace('trackeritem:', '')},
                        {'name': 'imap_msg_ids', 'value': ids},
                        {'name': 'list_path', 'value': getListPathParam()},
                        {'name': 'folder', 'value': $el.data('folder')},
                        {'name': 'auto_move', 'value': autoMove}],
                        function(res) {
                            handleMoveSuccess();
                        },
                        [],
                        false
                    );
                };
                callback_handler = callback_handler ?? default_callback;
                $el.parent()
                    .find('.object-selector input[name=move_to_trackers]')
                    .object_selector()
                    .on('change', {field: $el.data('field'), folder: $el.data('folder')}, callback_handler);
            }
        });
    });
};

var tiki_get_message_content = function(msg_part, uid, images) {
    if (!images) {
        images = 0;
    }
    if (!uid) {
        uid = $('.msg_uid').val();
    }
    if (uid) {
        if (getPageNameParam() == 'message') {
            window.scrollTo(0,0);
        }
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_message_content'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'imap_msg_part', 'value': msg_part},
            {'name': 'imap_allow_images', 'value': images},
            {'name': 'list_path', 'value': getListPathParam()}],
            function(res) {
                $('.msg_text').html('');
                $('.msg_text').append(res.msg_headers);
                $('.msg_text').append(res.msg_text);
                $('.msg_text').append(res.msg_parts);
                document.title = $('.header_subject th').text();
                imap_message_view_finished();
                tiki_message_view_finished(res.show_archive, res.show_restore);
                tiki_prev_next_links(res.msg_prev_link, res.msg_prev_subject, res.msg_next_link, res.msg_next_subject);
                window.dispatchEvent(new CustomEvent('message-loaded'));
            },
            [],
            false
        );
    }
    return false;
};

var tiki_message_view_finished = function(show_archive, show_restore) {
    $('.msg_part_link').off("click").on("click", function() {
        $('.header_subject')[0].scrollIntoView();
        $('.msg_text_inner').css('visibility', 'hidden');
        return tiki_get_message_content($(this).data('messagePart'), false, $(this).data('allowImages'));
    });
    $('#flag_msg').off('click').on("click", function() { return tiki_flag_message(); });
    $('#unflag_msg').off('click').on("click", function() { return tiki_flag_message(); });
    $('#delete_message').off("click").on("click", function() { return tiki_delete_message(); });
    $('#move_message').off("click").on("click", function(e) { return tiki_move_copy(e, 'move', 'message');});
    $('#copy_message').off("click").on("click", function(e) { return tiki_move_copy(e, 'copy', 'message');});

    // Manage Restore button visibility
    if (typeof show_restore !== 'undefined' && show_restore) {
        $('#restore_message').off("click").on("click", function(e) { return tiki_restore_message(e);});
        $('#restore_message').css('display', 'block');
    } else {
        $('#restore_message').remove();
    }

    // Manage Archive button visibility
    if (typeof show_archive !== 'undefined' && show_archive) {
        $('#archive_message').off("click").on("click", function() { return tiki_archive_message(); });
    } else {
        $('#archive_message').remove();
    }

    $('#unread_message').off('click').on("click", function() { return tiki_unread_message();});
    $('#delete_message').parent().contents().filter(function() { return this.nodeType == 3 && this.previousSibling.nodeType == 3; }).remove();
};

var tiki_prev_next_links = function(prev_link, prev_subject, next_link, next_subject) {
    var target = $('.msg_headers tr').last();
    if (prev_link) {
        var plink = '<a class="plink" href="'+prev_link+'"><div class="prevnext prev_img"></div> '+prev_subject+'</a>';
        $('<tr class="prev"><th colspan="2">'+plink+'</th></tr>').insertBefore(target);
    }
    if (next_link) {
        var nlink = '<a class="nlink" href="'+next_link+'"><div class="prevnext next_img"></div> '+next_subject+'</a>';
        $('<tr class="next"><th colspan="2">'+nlink+'</th></tr>').insertBefore(target);
    }
};

var tiki_delete_message = function() {
    if (!hm_delete_prompt()) {
        return false;
    }
    var uid = getMessageUidParam();
    var list_path = getListPathParam();
    if (list_path && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_delete_message'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'list_path', 'value': list_path}],
            function(res) {
                if (!res.delete_error) {
                    if (Hm_Utils.get_from_global('msg_uid', false)) {
                        return;
                    }
                    var nlink = $('.nlink');
                    if (nlink.length) {
                        window.location.href = nlink.attr('href');
                    }
                    else {
                        window.location.href = "?page=message_list&list_path="+getListPathParam();
                    }
                }
            }
        );
    }
    return false;
};

var tiki_archive_message = function() {
    var uid = getMessageUidParam();
    var list_path = getListPathParam();
    if (list_path && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_archive_message'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'list_path', 'value': list_path}],
            function(res) {
                if (!res.archive_error) {
                    if (Hm_Utils.get_from_global('msg_uid', false)) {
                        return;
                    }
                    var nlink = $('.nlink');
                    if (nlink.length) {
                        window.location.href = nlink.attr('href');
                    }
                    else {
                        window.location.href = "?page=message_list&list_path="+getListPathParam();
                    }
                }
            }
        );
    }
    return false;
};

var tiki_flag_message = function() {
    var uid = getMessageUidParam();
    var list_path = getListPathParam();
    if (list_path && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_flag_message'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'list_path', 'value': list_path}],
            function(res) {
                if (res.flag_state == 'flagged') {
                    $('#flag_msg').hide();
                    $('#unflag_msg').show();
                }
                else {
                    $('#flag_msg').show();
                    $('#unflag_msg').hide();
                }
                tiki_message_view_finished(res.show_archive, res.show_restore);
            }
        );
    }
    return false;
};

var tiki_unread_message = function() {
    var uid = getMessageUidParam();
    var list_path = getListPathParam();
    if (list_path && uid) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_message_action'},
            {'name': 'action_type', 'value': 'unread'},
            {'name': 'imap_msg_uid', 'value': uid},
            {'name': 'list_path', 'value': list_path}],
            function() {
                window.location.href = "?page=message_list&list_path="+getListPathParam();
            }
        );
    }
    return false;
};

var tiki_move_copy = function(e, action, context) {
    imap_move_copy(e, action, context);
    var move_to = $('.msg_text .move_to_location');
    $('a', move_to).not('.imap_move_folder_link').not('.close_move_to').off("click").on("click", function(e) {
        e.preventDefault();
        tiki_perform_move_copy($(this).data('id'), move_to);
        return false;
    });
    return false;
};

var expand_tiki_move_to_mailbox = function() {
    var move_to = $('.move_to_location');
    $('a', move_to).not('.imap_move_folder_link').not('.close_move_to').off('click').on("click", function(e) {
        e.preventDefault();
        tiki_perform_move_copy($(this).data('id'), move_to);
        return false;
    });
};

var tiki_perform_move_copy = function(dest_id, move_to) {
    var action = $('.move_to_type').val();
    var ids = [getListPathParam()+'#'+getMessageUidParam()];
    move_to.html('').hide();
    if (ids.length > 0 && dest_id) {
        Hm_Ajax.request(
            [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_move_copy_action'},
            {'name': 'imap_move_ids', 'value': ids.join(',')},
            {'name': 'imap_move_to', 'value': dest_id},
            {'name': 'imap_move_action', 'value': action}],
            function(res) {
                if (action == 'move') {
                    var nlink = $('.nlink');
                    if (nlink.length) {
                        window.location.href = nlink.attr('href');
                    }
                    else {
                        window.location.href = "?page=message_list&list_path="+getListPathParam();
                    }
                }
            }
        );
    }
};

var tiki_restore_message = function(e) {
    e.preventDefault();
    var uid = getMessageUidParam();
    var list_path = getListPathParam();
    if (list_path && uid) {
        Hm_Ajax.request(
            [
                {'name': 'hm_ajax_hook', 'value': 'ajax_tiki_restore_message'},
                {'name': 'imap_msg_uid', 'value': uid},
                {'name': 'list_path', 'value': list_path}
            ],
            function(res) {
                if (!res.restore_error) {
                    if (Hm_Utils.get_from_global('msg_uid', false)) {
                        return;
                    }
                    var nlink = $('.nlink');
                    if (nlink.length) {
                        window.location.href = nlink.attr('href');
                    } else {
                        window.location.href = "?page=message_list&list_path="+getListPathParam();
                    }
                }
            }
        );
    }
    return false;
};


var tiki_send_archive = function() {
    $('.compose_post_archive').val(0).before('<input type="hidden" name="tiki_archive_replied" value="1">');
    $('.smtp_send').trigger("click");
};

var upload_file = function(file) {
    var res = '';
    var form = new FormData();
    var xhr = new XMLHttpRequest;
    Hm_Ajax.show_loading_icon();
    form.append('upload_file', file);
    form.append('hm_ajax_hook', 'ajax_smtp_attach_file');
    form.append('hm_page_key', $('#hm_page_key').val());
    form.append('draft_id', $('.compose_draft_id').val());
    form.append('draft_smtp', $('.compose_server').val());
    form.append('draft_subject', $('.compose_subject').val());
    form.append('draft_body', $('#compose_body').val());
    form.append('draft_to', $('.compose_to').val());
    form.append('draft_cc', $('.compose_cc').val());
    form.append('draft_bcc', $('.compose_bcc').val());
    if ($('#hm_session_prefix').length > 0) {
        form.append('hm_session_prefix', $('#hm_session_prefix').val());
    }
    xhr.open('POST', '', true);
    xhr.setRequestHeader('X-Requested-With', 'xmlhttprequest');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4){
            if (hm_encrypt_ajax_requests()) {
                res = Hm_Utils.json_decode(xhr.responseText);
                res = Hm_Utils.json_decode(Hm_Crypt.decrypt(res.payload));
            }
            else {
                res = Hm_Utils.json_decode(xhr.responseText);
            }
            if (res.file_details) {
                $('.uploaded_files').append(res.file_details);
                $('.delete_attachment').on("click", function() { return delete_attachment($(this).data('id'), this); });
            }
            Hm_Ajax.stop_loading_icon();
            if (res.router_user_msgs && !$.isEmptyObject(res.router_user_msgs)) {
                Hm_Notices.show(res.router_user_msgs);
            }
        }
    };
    xhr.send(form);
};

if (typeof hm_sieve_condition_fields === 'function') {
    var default_fields = hm_sieve_condition_fields();
    default_fields.Message.push(
        {
            name: 'bounce',
            description: 'Is Bounce',
            type: 'none',
            options: ['Soft', 'Hard']
        },
        {
            name: 'replytotrackermessage',
            description: 'Is reply to tracker message',
            type: 'none',
            options: []
        },
    );
    hm_sieve_condition_fields = function() {
        return default_fields;
    };
    var default_actions = hm_sieve_possible_actions();
    default_actions.push(
        {
            name: 'bounce',
            description: 'Add to bounce list',
            type: 'none',
            extra_field: false
        },
        {
            name: 'movetotracker',
            description: 'Move to tracker folder',
            type: 'tracker',
            extra_field: false,
            values: []
        },
        {
            name: 'copytotracker',
            description: 'Copy to tracker folder',
            type: 'tracker',
            extra_field: false,
            values: []
        },
        {
            name: 'movetooriginatingtrackerinbox',
            description: 'Move to originating tracker inbox',
            type: 'none',
            extra_field: false
        }
    );
    hm_sieve_possible_actions = function() {
        return default_actions;
    };
}

var get_tracker_info = function (item_id, field_id, folder, selector) {
    var target = selector.find('.trackers_toggle');
    target.html(hm_spinner());
    Hm_Ajax.request(
        [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_tracker_info'},
        {'name': 'tracker_item_id', 'value': item_id},
        {'name': 'tracker_field_id', 'value': field_id},
        {'name': 'folder', 'value': folder},],
        function(res) {
            target.text(res.tracker_data);
        }
    );
};

var current_account;

$('.add_filter, .edit_filter').on('click', function () {
    current_account = $(this).attr('account');
});

$('.edit_filter').on('click', function () {
    current_account = $(this).attr('imap_account');
});

/**
 * Action change on tiki events
 */
 $(document).on('change', '.sieve_actions_select', function (event) {
    let tr_elem = $(this).parent().parent();
    let elem = $(this).parent().next().next();
    let action_name = $(this).val();
    let selected_action;
    hm_sieve_possible_actions().forEach(function (action) {
       if (action_name === action.name) {
            selected_action = action;
       }
    });
    if (selected_action) {
        if (selected_action.type === 'tracker') {
            elem.html(hm_spinner());
            var setup_elem_value = function() {
                elem.html('<input name="sieve_selected_action_value[]" type="hidden" class="selected_tracker" /><a href="#" class="trackers_toggle">'+tr('Show trackers')+'</a>');
            };
            if (!$('#trackers_dropdown').length) {
                Hm_Ajax.request(
                    [{'name': 'hm_ajax_hook', 'value': 'ajax_tiki_get_trackers'}],
                    function(res) {
                        if (res.trackers) {
                            $('body').append(res.trackers);
                            setup_elem_value();
                        }
                    }
                );
            } else {
                setup_elem_value();
            }

            var default_value = elem.parent().attr('default_value');
            if (default_value) {
                default_value = get_parsed_tracker(default_value);
                get_tracker_info(default_value.itemId, default_value.fieldId, default_value.folder, elem);
                elem.parent().find("[name^=sieve_selected_action_value]").val(elem.parent().attr('default_value'));
            }
        }
        if (selected_action.type === 'mailbox') {
            let mailboxes = null;
            tr_elem.children().eq(2).html(hm_spinner());
            Hm_Ajax.request(
                [   {'name': 'hm_ajax_hook', 'value': 'ajax_tiki_sieve_get_mailboxes'},
                {'name': 'imap_account', 'value': current_account} ],
                function(res) {
                    mailboxes = JSON.parse(res.mailboxes);
                    options = '';
                    let mailbox_names = Object.keys(mailboxes);
                    mailbox_names.forEach(function(mailbox) {
                        options += '<optgroup label="'+mailbox+'">';
                        mailboxes[mailbox].forEach(function(val) {
                            let clean_val = val.replace(/^imap_.+_/g, '');
                            if (tr_elem.attr('default_value') === val) {
                                options = options + '<option value="' + val + '" selected>'+ clean_val +'</option>';
                            } else {
                                options = options + '<option value="' + val + '">'+ clean_val +'</option>';
                            }
                            options += '</optgroup>';
                        });
                    });
                    elem.html('<select name="sieve_selected_action_value[]">'+ options +'</select>');
                    $("[name^=sieve_selected_action_value]").last().val(elem.parent().attr('default_value'));
                }
            );
            event.stopImmediatePropagation();
        }
    }
});

var get_parsed_tracker = function (val) {
    return JSON.parse(val.replaceAll("'", '"'));
};

$(document).on('change', '.selected_tracker', function () {
    var value = get_parsed_tracker($(this).val());
    get_tracker_info(value.itemId, value.fieldId, value.folder, $(this).parent());
});

$(document).on('click', '.trackers_toggle', function (e) {
    e.preventDefault();
    $('#trackers_dropdown').appendTo($(this).parent());
    $('#move_to_trackers').trigger('click').hide();
});

/* executes on onload, has access to other module code */
$(function() {
    autoMoveReplyToTrackerItem();

    if (getPageNameParam() == 'groupmail') {
        Hm_Message_List.select_combined_view();
        $('.content_cell').swipeDown(function(e) { e.preventDefault(); Hm_Message_List.load_sources(); });
        $('.source_link').on("click", function() { $('.list_sources').toggle(); return false; });
    }

    if (! $('body').hasClass('tiki-cypht')) $('body').addClass('tiki-cypht');

    $('.mobile .folder_toggle').on("click", function(){
        $('.mobile .folder_cell').toggleClass('slide-in');
        if ($(this).attr('style') == '') $('.mobile .folder_list').hide();
    });

    if ($('.navbar.fixed-top').length) {
        $('.inline-cypht').css({'padding-top': '0'});
        $('body').css({'padding-top': '30px'});
    }

    $('.folder_list').on('click', '.clear_cache', function(e) {
        e.preventDefault();
        sessionStorage.clear();
        var url = window.location.href.replace(/#.*/, '');
        window.location.href = url;
        return false;
    });

    const clearCacheButton = `<a href="#" class="clear_cache" title="${tr('Clear cache')}">
        <i class="bi bi-trash menu-icon"></i>
        <span class="nav-label">${tr('Clear cache')}</span>
    </a>`;
    const cleanUpFooter = () => {
        if (!$('.folder_list .sidebar-footer .clear_cache').length) {
            $('.folder_list .sidebar-footer').append(clearCacheButton);
        }
        $('.folder_list .sidebar-footer .logout_link').remove();
    };

    cleanUpFooter();
    Hm_Ajax.add_callback_hook('ajax_hm_folders', cleanUpFooter);
    Hm_Ajax.add_callback_hook('*', function(res, xhr) {
        $(document).trigger('ajaxComplete', xhr);
    });

    $('.folder_list .search_terms').off('search');
    $('.folder_list .search_terms').on('search', function(e) {
        if (!$(this).val()) {
            Hm_Ajax.request([{'name': 'hm_ajax_hook', 'value': 'ajax_reset_search'}]);
        }
    });

    if (document.cookie.indexOf('hm_first_load=1') > -1) {
        document.cookie = 'hm_reload_folders=1; max-age=0';
        document.cookie = 'hm_first_load=1; max-age=0';
    }
    var bgTheme = document.body.getAttribute('data-bg-theme');
    if(bgTheme && bgTheme === 'dark') {
        $('.app-logo').attr('src', 'vendor_bundled/vendor/jason-munro/cypht/modules/core/assets/images/logo.svg');
    }
});

