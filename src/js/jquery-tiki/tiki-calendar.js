/**
 * Support JavaScript for EventCalendar Resource Views used by tiki's calendar feature
 */
import { createCalendar, DayGrid, TimeGrid, Interaction, List } from "@event-calendar/core";
import moment from "moment";

$.fn.setupEventCalendar = function (
    eventCalendarParams,
    calendarContainer,
    targetId = "calendar",
    urlEventSource = "tiki-ajax_services.php?controller=calendar&action=list_items",
    returnUrl = "tiki-calendar.php"
) {
    this.each(function () {
        const calendarEl = document.getElementById(targetId);
        $(calendarEl).tikiModal(tr("Loading..."));

        calendarContainer[0] = createCalendar(document.getElementById(targetId), [DayGrid, TimeGrid, Interaction, List], {
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                meridiem: eventCalendarParams.timeFormat,
                hour12: eventCalendarParams.timeFormat,
            },
            //timeZone: eventCalendarParams.display_timezone,
            locale: eventCalendarParams.language,
            headerToolbar: {
                start: "prev,next today",
                center: "title",
                end: "dayGridMonth,timeGridWeek,timeGridDay,listYear",
            },
            editable: true,
            selectable: true,
            eventSources: [{ url: urlEventSource }],
            slotMinTime: eventCalendarParams.minHourOfDay,
            slotMaxTime: eventCalendarParams.maxHourOfDay,
            nowIndicator: true,
            pointer: true,
            buttonText: {
                today: tr("today"),
                dayGridMonth: tr("month"),
                timeGridWeek: tr("week"),
                timeGridDay: tr("day"),
                listYear: tr("list"),
            },
            allDayContent: tr("all-day"),
            firstDay: eventCalendarParams.firstDayofWeek,
            slotDuration: eventCalendarParams.slotDuration,
            view: eventCalendarParams.initialView,
            date: eventCalendarParams.initialDate,
            viewDidMount: function (data) {
                $(calendarEl).tikiModal();
                if (data.type == "dayGridMonth" || data.type == "listMonth") {
                    calendarContainer[0].setOption("duration", { months: 1 });
                    if (!document.getElementById("quarter")) {
                        const ecStart = document.querySelector(".ec-start");
                        const buttonMonthView = document.createElement("div");
                        buttonMonthView.innerHTML =
                            '<button class="ec-button" id="one-month">One-Month</button><button class="ec-button" id="quarter">Quarter</button><button class="ec-button" id="semester">Semester</button>';
                        ecStart.appendChild(buttonMonthView);
                    }

                    const oneMonth = document.querySelector("#one-month");
                    const quarter = document.querySelector("#quarter");
                    const semester = document.querySelector("#semester");

                    oneMonth.addEventListener("click", () => {
                        oneMonth.classList.add("ec-active");
                        quarter.classList.remove("ec-active");
                        semester.classList.remove("ec-active");
                        calendarContainer[0].setOption("duration", { months: 1 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    });
                    quarter.addEventListener("click", () => {
                        oneMonth.classList.remove("ec-active");
                        quarter.classList.add("ec-active");
                        semester.classList.remove("ec-active");
                        calendarContainer[0].setOption("duration", { months: 3 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("M/D");
                        });
                    });
                    semester.addEventListener("click", () => {
                        oneMonth.classList.remove("ec-active");
                        quarter.classList.remove("ec-active");
                        semester.classList.add("ec-active");
                        calendarContainer[0].setOption("duration", { months: 6 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("M/D");
                        });
                    });
                } else {
                    if (document.getElementById("quarter")) {
                        document.getElementById("one-month").remove();
                        document.getElementById("quarter").remove();
                        document.getElementById("semester").remove();
                    }
                    if (data.type == "timeGridWeek" || data.type == "listWeek") {
                        calendarContainer[0].setOption("duration", { days: 7 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }
                    if (data.type == "timeGridDay" || data.type == "listDay") {
                        calendarContainer[0].setOption("duration", { days: 1 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }

                    if (data.type == "listYear") {
                        calendarContainer[0].setOption("duration", { months: 12 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }
                }
            },
            eventDidMount: function (arg) {
                const event = arg.event;
                const element = $(arg.el);
                const dayGrid = $(".ec-event").length;
                if (dayGrid > 0) {
                    let backgroundColor = event.backgroundColor;
                    let textColor = event.textColor;
                    let categoryBackgroundColor = event.extendedProps.categoryBackgroundColor;
                    let defaultBackgroundColor = element.css("background-color");

                    const titleElement = element.find(".ec-event-title");
                    const defaultTextColor = titleElement.css("color");
                    if (backgroundColor === "#") {
                        backgroundColor = defaultBackgroundColor;
                    }
                    if (textColor === "#") {
                        textColor = defaultTextColor;
                    }
                    var currentcalitemId = $("#currentcalitemId").text();
                    if (currentcalitemId !== "" && currentcalitemId == event.id) {
                        backgroundColor = "#FFEA00";
                        categoryBackgroundColor = "#FFEA00";
                        textColor = "#000";
                    }
                    event.backgroundColor = backgroundColor;
                    if (categoryBackgroundColor !== "") {
                        $(element).attr("style", "background-color: " + categoryBackgroundColor);
                    }
                    $(element).find(".ec-event-time").css({
                        color: textColor,
                    });
                    $(element).find(".ec-event-title").css({
                        color: textColor,
                    });
                    const showCopyButton = event.extendedProps.showCopyButton;
                    if (showCopyButton === "y") {
                        const copyButton = $("<i>", {
                            id: "event" + event.id,
                            class: "ec-event-button far fa-clipboard",
                            "data-toggle": "tooltip",
                            "data-placement": "right",
                            title: tr("Copy link to this event"),
                        });
                        $(element).append(copyButton);
                        $(element).find(".ec-event-button").css({
                            color: textColor,
                        });
                        $("#event" + event.id).on("click", function (e) {
                            const timestamp = event.start.getTime() / 1000;
                            const url = event.extendedProps.baseUrl + "calendar?todate=" + timestamp + "&calitemId=" + event.id;
                            navigator.clipboard.writeText(url).then(
                                function () {
                                    alert(tr("Copied to clipboard"));
                                },
                                function () {
                                    alert(tr("Failure to copy. Check permissions for clipboard"));
                                }
                            );
                            return false;
                        });
                    }
                }
                const eventTitle = tooltipEscape(event.title);
                element.attr("title", eventTitle + "|" + event.extendedProps.description);
                element.addClass("tips");
                // surely there's a better way?
                $(element).parent().tiki_popover();
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                if (event.id && event.extendedProps.viewable) {
                    let $this = $(info.el).tikiModal(" ");
                    $.openModal({
                        title: tr("New event"),
                        size: "modal-lg",
                        remote: "tiki-ajax_services.php?controller=calendar&action=view_item&calitemId=" + event.id + "&modal=1",
                        open: function () {
                            $this.tikiModal();
                            $("form:not(.no-ajax)", this)
                                .addClass("no-ajax") // Remove default ajax handling, we replace it
                                .on(
                                    "submit",
                                    ajaxSubmitEventHandler(function (data) {
                                        calendarEditSubmit(data, this);
                                    })
                                );
                        },
                    });
                }
            },
            dateClick: function (info) {
                // Handle date clicks in EventCalendar.
                // If a date number is clicked, switch to Day View for the selected date.
                // If any other part of the date cell is clicked, open a form to create a new event.
                if (info.jsEvent.target.classList.contains("ec-day-head")) {
                    calendarContainer[0].changeView("timeGridDay", info.dateStr);
                } else {
                    let $this = $(info.dayEl).tikiModal(" ");
                    const countCals = $("#filtercal ul li").length;
                    if (countCals >= 1 || targetId != "calendar") {
                        $.openModal({
                            title: tr("New event"),
                            size: "modal-lg",
                            remote: $.service("calendar", "edit_item", { todate: info.date.toUnix(), modal: 1, return_url: returnUrl }),
                            open: function () {
                                $this.tikiModal();
                                $("form:not(.no-ajax)", this).addClass("no-ajax"); // Remove default ajax handling, we replace it
                            },
                        });
                    } else {
                        location.href = "tiki-calendar.php";
                    }
                }
            },
            eventResize: function (info) {
                $.post($.service("calendar", "resize"), {
                    calitemId: info.event.id,
                    delta: info.endDelta,
                });
            },
            eventDrop: function (info) {
                // All the time must be cons
                $.post($.service("calendar", "move"), {
                    calitemId: info.event.id,
                    delta: info.delta.seconds,
                });
            },
            height: "auto",
        });
    });
};

// open modal for edit form
$(document).on("click", ".edit-calendar-item-btn", function (e) {
    const $this = $(this);
    const $modal = $this.parents().hasClass("modal-body");
    if ($modal) {
        e.preventDefault();
        $.closeModal({
            done: function () {
                $.openModal({
                    title: tr("Edit event"),
                    size: "modal-lg",
                    remote: $this.attr("href"),
                    open: function () {
                        $this.tikiModal();

                        $("form:not(.no-ajax)", this)
                            .addClass("no-ajax") // Remove default ajax handling, we replace it
                            .on(
                                "submit",
                                ajaxSubmitEventHandler(function (data) {
                                    calendarEditSubmit(data, this);
                                })
                            );
                    },
                });
            },
        });

        return false;
    }
});

$(function () {
    let editable_rrule_update = function ($ab) {
        let $a = $ab.find(".editable_rrule");
        let href = $a.data("base-href") + "&rrule=" + $a.text() + "&start=" + $ab.find("input[name*=dtstart]").val();
        $a.attr("href", href);
    };

    $(document).on("change", ".availability-block input[name*=dtstart]", function () {
        let $ab = $(this).closest(".availability-block");
        editable_rrule_update($ab);
    });

    $(document).on(
        "submit",
        "form.rrule-form",
        ajaxSubmitEventHandler(function (data) {
            $.closeModal();
            let $ab = $('.availability-block[data-uid="' + data.uid + '"]');
            $ab.find("input[name*=rrule]").val(data.rrule);
            $ab.find(".editable_rrule").text(data.rrule);
            editable_rrule_update($ab);
        })
    );

    $(document).on("click", ".availability-block .availability-remove", function (e) {
        e.preventDefault();
        $(this).closest(".availability-block").remove();
        return false;
    });

    $(document).on("click", ".availability-new", function (e) {
        e.preventDefault();
        let $newbtn = $(this);
        $.ajax({
            url: $newbtn.attr("href"),
            success: function (data) {
                $newbtn.before(data);
            },
        });
        return false;
    });

    $(document).on("click", ".availability-check", function (e) {
        e.preventDefault();
        let participants = [];
        $("select[name*=participant_roles]").each(function (i, el) {
            let m = $(el)
                .attr("name")
                .match(/calitem\[participant_roles\]\[(.*)\]/);
            if (m && m[1]) {
                participants.push(m[1]);
            }
        });
        $.openModal({
            remote: $.service("calendar_availability", "check", $(this).closest("form").serialize()),
            size: "modal-lg",
        });
        return false;
    });

    $(document).on("change", ".appointment-date-selector", function (e) {
        e.preventDefault();
        $(".slot-container").hide();
        $(".slot-container.date" + $(this).val()).show();
        return false;
    });
});
