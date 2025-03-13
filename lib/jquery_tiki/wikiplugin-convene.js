/**
 * Support JavaScript for wikiplugin_convene
 */

$.fn.setupConvene = function (pluginParams) {
    this.each(function () {
        let $this = $(this);

        $(document).ready(function() {
            $(document).on('focus', '.inputAddDate', function() {
                $('.divAddDate > :first-child').addClass('absolute');
            });

            $(document).on('blur', '.inputAddDate', function() {
                $('.divAddDate > :first-child').removeClass('absolute');
            });
        });

        let convene = $.extend({
            updateUsersVotes: function () {
                let data = {}, dateFromData, dataComments = {}, comment;

                $('.conveneUserVotes', $this).each(function () {
                    $('.conveneUserVote', this).each(function () {
                        dateFromData = $(this).data("date");
                        comment = $(this).data("comment");
                        if (dateFromData) {
                            if (data[dateFromData] === undefined) {
                                data[dateFromData] = {};
                                dataComments[dateFromData] ={};
                            }
                            data[dateFromData][$(this).data("voter")] = $(this).val();
                            dataComments[dateFromData][$(this).data("voter")] = comment;
                        } else {
                            data.push($(this).attr('name') + ' : ' + $(this).val());
                            dataComments.push($(this).attr('name') + ' : ' + comment);
                        }
                    });
                });

                this.voteData = [data,dataComments];

            },
            addUser: function (user) {
                lockPage(function (user) {
                    if (! user) {
                        return;
                    }

                    let users = $(".conveneUserVotes", $this).map(function() { return $(this).data("voter"); }).get();

                    if ($.inArray(user, users) > -1) {
                        return;
                    }

                    this.updateUsersVotes();

                    for (const date in this.voteData[0]) {
                        this.voteData[0][date][user] = 0;
                        this.voteData[1][date][user] = '';
                    }

                    this.save();
                }, this, [user]);
            },
            deleteUser: function (user) {
                lockPage(function (user) {
                    if (!user) {
                        return;
                    }

                    this.updateUsersVotes();

                    for (const date in this.voteData[0]) {
                        delete this.voteData[0][date][user];
                        delete this.voteData[1][date][user];
                    }

                    this.save();
                }, this, [user]);
            },
            addDate: function (date) {
                // should already be locked by the click event
                if (!date) {
                    return;
                }

                if (getCurrentDateTime()>date) {
                    $("#tikifeedback").showError(
                        tr("You cannot select a date and time that has already passed."));
                    $("#page-data").tikiModal();
                } else {

                    this.updateUsersVotes();

                    if (typeof this.voteData[0][date] !== "undefined") {    // don't reset an existing date?
                        return;
                    }

                    this.voteData[0][date] = {};
                    this.voteData[1][date] = {};

                    $('.conveneUserVotes', $this).each(function () {
                        convene.voteData[0][date][$(this).data("voter")] = 0;
                        convene.voteData[1][date][$(this).data("voter")] = '';
                    });

                    this.save();
                }
            },
            deleteDate: function (date) {
                lockPage(function (date) {
                    if (! date) {
                        return;
                    }
                    this.updateUsersVotes();

                    delete this.voteData[0][date];
                    delete this.voteData[1][date];

                    this.save();
                }, this, [date]);
            },
            save: function (reload) {
                $("#page-data").tikiModal(tr("Loading..."));
                let content = JSON.stringify(this.voteData);

                if (content === undefined || content === "undefined" || typeof content === "undefined") {
                    alert(tr("Sorry, no content to save, try reloading the page"));
                    unlockPage();
                    return;
                }

                let needReload = reload !== undefined, page;

                if (jqueryTiki.current_object.type === "wiki page") {
                    page = jqueryTiki.current_object.object;
                } else {
                    alert(tr("Sorry, only wiki pages supported currently"));
                    return;
                }

                let serialisedParams = {};

                $.each(pluginParams, function (key, value) {
                    if (typeof value === "object") {
                        serialisedParams[key] = value.join(', ');
                    } else {
                        serialisedParams[key] = value;
                    }
                });

                let params = {
                    page: page,
                    content: content,
                    index: pluginParams.index,
                    type: "convene",
                    ticket: $("input[name=ticket]", $this).val(),
                    params: serialisedParams,
                };

                $.post($.service("plugin", "replace"), params, function () {
                    $.get($.service("wiki", "get_page", {page: page}), function (data) {
                        unlockPage();

                        if (needReload) {
                            history.go(0);
                        } else {
                            if (data) {
                                let formId = "#" + $this.attr("id");
                                let $newForm = $(formId, data);
                                $(formId, "#page-data").empty().append($newForm.children());
                            }
                        }
                    }).always(function () {
                        initConvene();
                    });

                })
                    .fail(function (jqXHR) {
                        $("#tikifeedback").showError(jqXHR);
                    })
                    .always(function () {
                        unlockPage();
                        $("#page-data").tikiModal();
                    });
            }
        }, pluginParams);

        $(window).on('beforeunload', function () {
            unlockPage();
        });

        window.pageLocked = false;

        // set semaphore
        let lockPage = function (callback, context, args) {
            let theArgs = args || [];
            if (!window.pageLocked) {
                $.getJSON($.service("semaphore", "is_set_by_other"), {
                        object_type: jqueryTiki.current_object.type,
                        object_id: jqueryTiki.current_object.object
                    },
                    function (data) {
                        if (data) {
                            $("#tikifeedback").showError(
                                tr("This page is being edited by another user. Please reload the page and try again later."));
                            $("#page-data").tikiModal();
                        } else {
                            // no one else using it, so carry on...
                            $.getJSON($.service("semaphore", "set"), {
                                object_type: jqueryTiki.current_object.type,
                                object_id: jqueryTiki.current_object.object
                            }, function () {
                                window.pageLocked = true;
                                callback.apply(context, theArgs);
                            });

                        }
                    }
                );
            } else {
                return callback.apply(context, theArgs);
            }
        };

        // unset semaphore
        let unlockPage = function () {
            if (window.pageLocked) {
                // needs to be synchronous to prevent page unload while executing
                $.ajax($.service("semaphore", "unset"), {
                    async: false,
                    dataType: "json",
                    data: {
                        object_type: jqueryTiki.current_object.type,
                        object_id: jqueryTiki.current_object.object
                    },
                    success: function () {
                        window.pageLocked = false;
                    }
                });
            }
        };

        let initConvene = function () {
            $('.conveneAddDate', $this).on('click', function () {
                const fieldName = $(this).data('field');
                const dateValue = $(`input[name="${fieldName}"]`).val();
                if (dateValue && !isNaN(dateValue)) {
                    convene.addDate(dateValue);
                } else {
                    alert(tr("Please select a valid date."));
                }
            });

            $('.conveneDeleteDate', $this)
                .on("click", function () {
                    if (confirm(tr("Delete this date?"))) {
                        convene.deleteDate($(this).data("date"));
                    }
                    return false;
                });

            $('.conveneDeleteUser', $this)
                .on("click", function () {
                    if (confirm(tr("Are you sure you want to remove this user's votes?") + "\n" +
                        tr("There is no undo"))) {
                        convene.deleteUser($(this).data("user"));
                    }
                    return false;
                });

            $('.conveneUpdateUser', $this).on("click", function () {
                let $thisButton = $(this),
                    $row = $thisButton.parents("tr").first();

                if ($('.conveneDeleteUser.btn-danger', $row).length) {
                    lockPage(function () {

                        $thisButton.find(".icon").popover("hide");
                        $('.conveneUpdateUser', $row).not($thisButton).hide();
                        // change the delete button into cancel
                        $('.conveneDeleteUser', $row)
                            .removeClass("btn-danger").addClass("btn-link")
                            .attr("title", tr("Cancel"))
                            .off("click").on("click", function () {
                                history.go(0);
                            })
                            .find('.icon').setIcon("ban");

                        $('.conveneDeleteDate', $row).hide();
                        $('.conveneMain', $row).hide();
                        $row.addClass('convene-highlight')
                            .find('td').filter(function (index) {
                                return index !== 0;
                            })
                            .addClass('conveneTd');

                        $thisButton.find('.icon').setIcon("save");
                        $row.find('.vote').hide();
                        $row.find('input').each(function (){

                            let select_disable = "";

                            let inputDataVote = $(this);
                            let convene_date_time = $(this).data("date");
                            let current_time = getCurrentDateTime();
                            const editLockedErrorMessage = tr("You cannot vote on a date and time that has already passed.");

                            let select_option = "";
                            pluginParams.voteoptions.forEach(row => {
                                let option = row.split('=');
                                select_option += '<option value="'+ option[0] +'">' + tr(option[1]) + '</option>';
                            });

                            if ( convene_date_time < current_time ) {
                                $(this).prevAll(".icon").first().replaceWith(
                                    $().getIcon("lock")
                                        .attr("title", editLockedErrorMessage)
                                        .addClass("tips")
                                        .css("font-size", "200%")
                                );
                                select_disable = "disabled";
                            } else {
                                const tooltip = $(this).data("comment") ?  tr("Edit comment") : tr("Add a comment");
                                $(this).prevAll(".icon").first().after(
                                    $().getIcon("comment")
                                        .attr("title", tooltip)
                                        .addClass("tips text-info")
                                        .css({
                                            position: "absolute",
                                            top: ".2rem",
                                            right: ".2rem"
                                        })
                                        .on("click", function () {

                                            //Add Comment dialogue
                                            let $commentInput = $('<input type="text" class="form-control" value="'+inputDataVote.data("comment")+'" />');

                                            $.openModal({
                                                size: "modal-sm",
                                                title: tr("Add Comment"),
                                                open: function () {
                                                    const $this = $(this);

                                                    $this.find(".modal-body").append($commentInput);

                                                    $this.find(".btn-primary").off("click").on("click", function () {
                                                        let comment = $commentInput.val();
                                                        inputDataVote.data("comment", comment);
                                                        convene.updateUsers = true;

                                                        let theModal = bootstrap.Modal.getInstance($this.get(0));
                                                        theModal.hide();

                                                    });

                                                    $this.find('input').first().trigger("focus").keypress(function (event) {
                                                        if (event.which === 13) {
                                                            event.preventDefault();
                                                            $this.find(".btn-primary").trigger("click");
                                                        }
                                                    });
                                                }
                                            });


                                        })
                                );
                            }

                            $(`<select class="form-control" required ${select_disable}>` +
                                select_option +
                                '</select>')
                                .val($(this).val())
                                .insertAfter($(this))
                                .on("change", function () {
                                    let tdClass = '', iconClass = '', icon = '';

                                    if ( convene_date_time < current_time ) {
                                        $("#tikifeedback").showError(editLockedErrorMessage);
                                        $("#page-data").tikiModal();

                                        convene.updateUsers = false;

                                    } else {
                                        const optionValue = $(this).val() * 1;
                                        switch (true) {
                                            case (optionValue > 0):
                                                tdClass = 'convene-ok';
                                                iconClass = 'text-success';
                                                icon = 'ok';
                                                break;
                                            case (optionValue < 0):
                                                tdClass = 'convene-no';
                                                iconClass = 'text-danger';
                                                icon = 'remove';
                                                break;
                                            default:
                                                tdClass = 'convene-unconfirmed';
                                                iconClass = 'text-secondary';
                                                icon = 'help';
                                        }

                                        $(this)
                                            .parent()
                                            .removeClass('convene-no convene-ok convene-unconfirmed')
                                            .addClass(tdClass)
                                            .find(".icon").first()
                                            .setIcon(icon);
                                        $(this)
                                            .parent()
                                            .find(".icon").first()
                                            .removeClass('text-success text-danger text-light')
                                            .addClass(iconClass)
                                            .css("font-size", "200%");

                                        convene.updateUsers = true;
                                    }
                                })
                                .parent().css({position: "relative"})
                                .applySelect2();

                        });

                        $row.tiki_popover();

                    }, this);
                } else {
                    $('.conveneUpdateUser', $row).show();
                    $('.conveneDeleteUser', $row).show();
                    $('.conveneDeleteDate', $row).show();
                    $row.removeClass('convene-highlight')
                        .find('.conveneTd')
                        .removeClass('convene-highlight');

                    $('.conveneMain').show();
                    $(this).find('span.icon-pencil');
                    parent = $(this).parent().parent().parent().parent();
                    parent.find('select').each(function (i) {
                        parent.find('input.conveneUserVote').eq(i).val($(this).val());

                        $(this).remove();
                    });

                    if (convene.updateUsers) {
                        convene.updateUsersVotes();
                        convene.save();
                    }
                }
                return false;
            });

            let addUsers = $('.conveneAddUser')
                .on("click", function () {
                    if (!$(this).data('clicked')) {
                        $(this)
                            .data('initval', $(this).val())
                            .val('')
                            .data('clicked', true);
                    }
                })
                .on("blur", function () {
                    if (!$(this).val()) {
                        $(this)
                            .val($(this).data('initval'))
                            .data('clicked', '');

                    }
                })
                .on("keydown",function (e) {
                    let user = $(this).val();

                    if (e.which == 13) {//enter
                        convene.addUser(user);
                        return false;
                    }
                });

            //ensure autocomplete works, it may not be available in mobile mode
            if (elementPlus && elementPlus.autocomplete) {
                autocomplete(addUsers[0], "username");
            } else if (addUsers.autocomplete) {
                addUsers.tiki("autocomplete", "username");
            }

            $('.conveneAddUserButton', $this).on("click", function () {
                if ($('.conveneAddUser', $this).val()) {
                    convene.addUser($('.conveneAddUser', $this).val());
                } else {
                    $('.conveneAddUser', $this).val(jqueryTiki.username).trigger("focus");
                }
                return false;
            });

            if (jQuery.timeago) {
                $("time.timeago").timeago();
            }
            if (jqueryTiki.tooltips) {
                $this.tiki_popover();
            }
            convene.updateUsersVotes();
        };

        let getCurrentDateTime = function () {
            let date_time = new Date();
            let local_date = date_time.toDateString();
            let local_time = date_time.toTimeString().split(":");
            return Date.parseUnix(`${local_date} ${local_time[0]}:${local_time[1]}`);
        };

        initConvene();

    });
};
