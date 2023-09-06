// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of
// authors. Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See
// license.txt for details. $Id: $

$(document).on("ready tiki.modal.redraw", function () {

    const $start = $("#start"),
        $startPicker = $start.nextAll("input[type=text]"),
        $end = $("#end"),
        $endPicker = $end.nextAll("input[type=text]"),
        $frm_edit_calendar = $('form[id="editcalitem"]'),
        $copy_to_new_event_button = $('a[id="copy_to_new_event"]');

    //add browser timezone to hidden input
    $("input[name=tzoffset]").val((new Date()).getTimezoneOffset());

    const $allDayCheckbox = $("#allday");
    $startPicker.data("enableTimepicker", true);
    $allDayCheckbox.change(function () {
        if ($(this).prop("checked")) {
            $(".time").css("visibility", "hidden");
            if ($startPicker.data("enableTimepicker")) {
                $startPicker.datepicker("disableTimepicker").datepicker("refresh").change();
                $endPicker.datepicker("disableTimepicker").datepicker("refresh").change();
                $startPicker.data("enableTimepicker", false);
            }
        } else {
            $(".time").css("visibility", "visible");
            if (! $startPicker.data("enableTimepicker")) {
                $startPicker.datepicker("enableTimepicker").datepicker("refresh").change();
                $endPicker.datepicker("enableTimepicker").datepicker("refresh").change();
                $startPicker.data("enableTimepicker", true);
            }
        }
    });

    setTimeout(function () {
        $allDayCheckbox.change();
    }, 1);

    $("#durationBtn").off("click").click(function () {
        if ($(".duration.time:visible").length) {
            $(".duration.time").hide();
            $(".end").show();
            $(this).text(tr("Show duration"));
            $("#end_or_duration").val("end");
        } else {
            $(".duration.time").show();
            $(".end").hide();
            $(this).text(tr("Show end time"));
            $("#end_or_duration").val("duration");
        }
        return false;
    });

    var getEventTimes = function () {
        var out = {},
            start = parseInt($start.val()),
            end = parseInt($end.val());
        if (start) {
            out.start = new Date(start * 1000);
        } else {
            out.start = null;
        }
        if (end) {
            out.end = new Date(end * 1000);
        } else {
            out.end = null;
        }
        if (start && end) {
            out.duration = ($("select[name=duration_Hour]").val() * 3600) +
                ($("select[name=duration_Minute]").val() * 60);    // in seconds
        }

        return out;
    };

    var fNum = function (num) {
        var str = "0" + num;
        return str.substring(str.length - 2);
    };

    $(".duration.time select, #start").change(function () {
        const $this = $(this);
        // filter change event noise (from jquery-ui?)
        if ($this.data("prev-value") === undefined) {
            $this.data("prev-value", $this.val());
        } else if ($this.data("prev-value") === $this.val()) {
            return;
        }
        let times = getEventTimes();

        if (times.duration) {
            times.end = new Date(
                times.start.getTime() + (times.duration * 1000)
            );

            $end.data("ignoreevent", true);

            $endPicker.datepicker("setDate", times.end).datepicker("refresh").change();
        }
    });

    $end.change(function (event) {
        const $this = $(this);
        // filter change event noise (from jquery-ui?)
        if ($this.data("prev-value") === undefined) {
            $this.data("prev-value", $this.val());
        } else if ($this.data("prev-value") === $this.val()) {
            return;
        }

        let times = getEventTimes(),
            s = times.start ? times.start.getTime() : null,
            e = times.end ? times.end.getTime() : null;

        if ($end.data("ignoreevent")) {
            $end.removeData("ignoreevent");
            return;
        }
        if (e && e <= s) {
            $startPicker.datepicker("setDate", times.end);
            s = e;
        }
        if (e) {
            times.duration = (e - s) / 1000;
            $("select[name=duration_Hour]").val(fNum(Math.floor(times.duration / 3600))).trigger("change.select2");
            $("select[name=duration_Minute]").val(fNum(Math.floor((times.duration % 3600) / 60))).trigger("change.select2");
        } else {
            $("select[name=duration_Hour]").val(1).trigger("change.select2");
            $("select[name=duration_Minute]").val(0).trigger("change.select2");
        }
    }).change();    // set duration on load

    // recurring events
    var $recurrentCheckbox = $("#id_recurrent");

    $recurrentCheckbox.change(function () {
        if ($(this).prop("checked")) {
            $("#recurrenceRules").show();
            $("#timezonePicker").show();
        } else {
            $("#recurrenceRules").hide();
            $("#timezonePicker").hide();
        }
    }).change();

    if (typeof $.validator !== "undefined") {
        $.validator.classRuleSettings.date = false;
    }

    if (typeof CKEDITOR === "object") {
        CKEDITOR.on("instanceReady", function (event) {
            // not sure why but the text area doesn't get its display:none applied when using full calendar
            event.editor.element.$.hidden = true;
        });
    }

    const addParticipant = function (participant) {
        const participantFormattedValue = participant.replace(/[^\w.-]+|\./g, '__');
        if ($('#participant_roles tr.'+participantFormattedValue).length === 0) {
            let $newRow = $("#participant-template-row").clone(true, true);

            $("#participant_roles").append($newRow).trigger("change.select2");

            $newRow
                .removeClass("d-none noselect2")
                .removeAttr("id")
                .data("user", participant)
                .addClass(participantFormattedValue)
                .find("select[name='calitem[participant_roles]'").attr("name", `calitem[participant_roles][${participant}]`);

            $newRow
                .find("select[name='calitem[participant_partstat]'").attr("name", `calitem[participant_partstat][${participant}]`);

            $newRow
                .find("td.username").text(participant);

            $newRow.applySelect2();

        }
    };

    $('#participant_roles').on('click', '.delete-participant', function(e) {
        e.preventDefault();
        var $tr = $(this).closest('tr');
        $('select[name="save[participants][]"]').find('option[value="'+$tr.data('user')+'"]').prop("selected", false);
        $tr.remove();
        return false;
    });

    $('select[name="participants[]"]').change(function() {
        var users = $(this).val();
        for (var i = 0, l = users.length; i < l; i++) {
            addParticipant(users[i]);
        }
        var $sel = $(this);
        $('#participant_roles tr').each(function(idx, tr) {
            var user = $(tr).data('user');
            if (! user) {
                return;
            }
            if ($sel.find("option[value='"+user+"']").length > 0 && users.indexOf(user) === -1) {
                $(tr).remove();
            }
        });
    });

    $('input[name="participants"]').on("autocompleteselect", function( event, ui ) {
        addParticipant(ui.item.value);
    });

    $('#invite_emails').on('click', function() {
        var email = $('#add_participant_email').val();
        if (email) {
            addParticipant(email);
        }
        $('#add_participant_email').val('');
    });

    // process form submits etc
    $(document).on("click", ".edit-event-form input[type=submit]", function (event) {
        // add a hidden input version of this form as jQuery.fn.serialize doesn't include the clicked button
        const $this = $(this);
        if ($this.data("lastclicked") === event.timeStamp) {
            return false;
        }
        $this.data("lastclicked", event.timeStamp);

        const $form = $this.parents("form");
        const $modal = $form.parents(".modal");
        const btnName = $this.attr("name");
        $form.find("#act").val(btnName);

        const calendarchanged = $form.find("input[name=calendarchanged]").val();

        // tell ckeditor to update the hidden textarea
        const $textarea = $form.find("textarea.wikiedit");
        if (typeof CKEDITOR !== "undefined" && CKEDITOR.instances[$textarea.attr("id")]) {
            const editor = CKEDITOR.instances[$textarea.attr("id")];
            if (editor.updateElement) { // sometimes fn not there?
                editor.updateElement();
            }
        }

        if (btnName === "preview") {
            // also used when changing calendars to reload the form
            $.post(
                $.service("calendar", "view_item"),
                $form.serialize(),
                function (html) {
                    const $html = $("<div>").append(html);
                    $form.tikiModal()
                        .find(".preview")
                        .removeClass("d-none")
                        .find(".alert")
                        .removeClass("d-none")
                        .find(".rboxcontent")
                        .html($html.find(".modal-body"));

                    $modal.animate({
                        scrollTop: $modal.find(".preview").offset().top
                    }, 1000);
                },
                "html"
            );
            event.preventDefault();
            return false;
        } else if (calendarchanged && btnName === "saveitem") {
            // used when changing calendars to reload the form - TODO better?
            $.post(
                $form.attr("action"),
                $form.serialize(),
                function (html) {
                    $("body").append(
                        $("<div class='d-none' id='editCalTemp'>").html(html)
                    );
                    const $editCalTemp = $("#editCalTemp");
                    $form.tikiModal().find(".form-contents").html($editCalTemp.find(".form-contents").html());
                    $editCalTemp.remove();
                    $modal.trigger("tiki.modal.redraw");
                },
                "html"
            );
            event.preventDefault();
            return false;

        } else {
            $form.submit();
            return true;
        }
    });

    window.calendarEditSubmit = function (data, form) {
        $.closeModal();
        // for some reason closeModal doesn't work here...
        const $modal = $(form).parents(".modal:first");
        if (data.url) {
            location.href = data.url;
        } else {
            setTimeout(function () {
                // TODO better
                $modal.find(".modal-header").find(".btn-close").click();
                calendar.refetchEvents();
            }, 500);
        }
    };

    const previewExecutor = delayedExecutor(500, function ($form) {
        $.post(
            $.service("calendar", "view_item"),
            $form.serialize(),
            function (html) {
                const $html = $("<div>").append(html);
                $form
                    .find(".preview").tikiModal()
                    .find(".alert .rboxcontent")
                    .html($html.find(".modal-body"));
            },
            "html"
        );
    });

    $(".edit-event-form").on("change", "input, select, textarea", function () {
        const $form = $(this).form();
        const $preview = $form.find(".preview").tikiModal(tr("Loading..."));

        if ($preview.is(":visible")) {
            previewExecutor($form);
        }
    });

    // avoid the "leave page" warning when copying to a new event
    $copy_to_new_event_button.click(function(){
        window.needToConfirm = false;
    });

    // reset confirm
    window.needToConfirm = false;
    $("input, select, textarea", "#editcalitem").change(function () {
        window.needToConfirm = true;
    });
});


/**
 * Checks recurring dates are valid
 *
 * @param day
 * @param month
 */
function checkDateOfYear(day, month)
{
    var mName = [
        "-",
        tr("January"),
        tr("February"),
        tr("March"),
        tr("April"),
        tr("May"),
        tr("June"),
        tr("July"),
        tr("August"),
        tr("September"),
        tr("October"),
        tr("November"),
        tr("December")
    ];
    var error = false;

    month = parseInt(month);
    day = parseInt(day);

    if (month === 4 || month === 6 || month === 9 || month === 11) {
        if (day === 31) {
            error = true;
        }
    }
    if (month === 2) {
        if (day > 29) {
            error = true;
        }
    }
    if (error) {
        $("#errorDateOfYear").text(
            tr("There's no such date as") + " " + day + " " + tr('of') + " "
            + mName[month]).show();
    } else {
        $("#errorDateOfYear").text("").hide();
    }
}

function changeItemTimezone(timezone)
{
    $('.jscal.datetime .description').html(tr("Time zone") + ": " + timezone);
}
