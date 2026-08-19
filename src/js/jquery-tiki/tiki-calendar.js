/**
 * Support JavaScript for FullCalendar Resource Views used by tiki's calendar feature
 */
import { Calendar } from "@fullcalendar/core";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";
import moment from "moment";

$.fn.setupFullCalendar = function (
    fullCalendarParams,
    calendarContainer,
    targetId = "calendar",
    urlEventSource = "tiki-ajax_services.php?controller=calendar&action=list_items",
    returnUrl = "tiki-calendar.php",
    associatedWikiPage = null
) {
    this.each(function () {
        const calendarEl = document.getElementById(targetId);
        $(calendarEl).tikiModal(tr("Loading..."));

        calendarContainer[0] = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, interactionPlugin, timeGridPlugin],
            themeSystem: "bootstrap5",
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                meridiem: fullCalendarParams.timeFormat,
                hour12: fullCalendarParams.timeFormat,
            },
            timeZone: fullCalendarParams.display_timezone,
            locale: fullCalendarParams.language,
            headerToolbar: {
                left: "prev,next today",
                center: "title",
                right: "year,semester,quarter,dayGridMonth,timeGridWeek,timeGridDay",
            },
            editable: true,
            selectable: true,
            events: urlEventSource,
            slotMinTime: fullCalendarParams.minHourOfDay,
            slotMaxTime: fullCalendarParams.maxHourOfDay,
            buttonText: {
                today: tr("today"),
                year: tr("year"),
                semester: tr("semester"),
                quarter: tr("quarter"),
                month: tr("month"),
                week: tr("week"),
                day: tr("day"),
            },
            allDayText: tr("all-day"),
            firstDay: fullCalendarParams.firstDayofWeek,
            slotDuration: fullCalendarParams.slotDuration,
            initialView: fullCalendarParams.initialView,
            initialDate: fullCalendarParams.initialDate,
            views: {
                quarter: {
                    type: "dayGrid",
                    duration: { months: 3 },
                    buttonText: "quarter",
                    dayCellContent: function (dayCell) {
                        return moment(dayCell.date).format("M/D");
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf("month").toDate(),
                            end: moment(currentDate).add("2", "months").endOf("month").toDate(),
                        };
                    },
                },
                semester: {
                    type: "dayGrid",
                    duration: { months: 6 },
                    buttonText: "semester",
                    dayCellContent: function (dayCell) {
                        return moment(dayCell.date).format("M/D");
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf("month").toDate(),
                            end: moment(currentDate).add("5", "months").endOf("month").toDate(),
                        };
                    },
                },
                year: {
                    type: "dayGrid",
                    duration: { months: 12 },
                    buttonText: tr("year"),
                    dayCellContent: function ($x) {
                        return moment($x.date).format("M/D");
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf("year").toDate(),
                            end: moment(currentDate).startOf("year").add("11", "months").endOf("month").toDate(),
                        };
                    },
                },
            },
            viewDidMount: function (data) {
                $(calendarEl).tikiModal();
            },
            eventDidMount: function (arg) {
                $(calendarEl).tikiModal();
                const event = arg.event;
                const element = $(arg.el);
                const dayGrid = $(".fc-daygrid-event").length;
                if (dayGrid > 0) {
                    let backgroundColor = event._def.ui.backgroundColor;
                    let textColor = event._def.ui.textColor;
                    let categoryBackgroundColor = event._def.extendedProps.categoryBackgroundColor;
                    let eventDotElement = element.find(".fc-daygrid-event-dot"),
                        defaultBackgroundColor;
                    if (eventDotElement.length === 0) {
                        eventDotElement = element;
                    }
                    const styleDot = getComputedStyle(eventDotElement[0]);
                    const borderCol = styleDot.border || styleDot.borderColor || styleDot.borderTopColor || styleDot.borderTopColor;
                    const matches = String(borderCol).match(/(rgb\(\d+,\s*\d+,\s*\d+\))/i) || ["rgb(55, 136, 216)"];
                    defaultBackgroundColor = matches[0];
                    if (eventDotElement !== element) {
                        $(eventDotElement[0]).remove();
                    }
                    const titleElement = element.find(".fc-event-title");

                    const styleElement = getComputedStyle(titleElement[0]);
                    const defaultTextColor = styleElement.color;
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

                    $(element).attr("style", "background-color: " + backgroundColor);
                    if (categoryBackgroundColor !== "") {
                        $(element).attr("style", "background-color: " + categoryBackgroundColor);
                    }
                    $(element).find(".fc-event-time").css({
                        color: textColor,
                    });
                    $(element).find(".fc-event-title").css({
                        color: textColor,
                    });
                    const showCopyButton = event._def.extendedProps.showCopyButton;
                    if (showCopyButton === "y") {
                        const copyButton = $("<i>", {
                            id: "event" + event.id,
                            class: "fc-event-button far fa-clipboard",
                            "data-toggle": "tooltip",
                            "data-placement": "right",
                            title: tr("Copy link to this event"),
                        });
                        $(element).append(copyButton);

                        $(element).find(".fc-event-button").css({
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
                let eventDescription = event.extendedProps.viewable === true ? event.extendedProps.description : "";
                eventDescription = associatedWikiPage?.[event.id] ? associatedWikiPage[event.id] : eventDescription;
                element.attr("title", eventTitle + "|" + eventDescription);
                element.addClass("tips");
                // surely there's a better way?
                $(element).parent().tiki_popover();
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                if (event.url && (event.viewable || event.extendedProps.viewable)) {
                    let $this = $(info.el).tikiModal(" ");
                    $.openModal({
                        title: tr("New event"),
                        size: "modal-lg",
                        remote: event.url + "&modal=1",
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
                // Handle date clicks in FullCalendar.
                // If a date number is clicked, switch to Day View for the selected date.
                // If any other part of the date cell is clicked, open a form to create a new event.
                if (info.jsEvent.target.classList.contains("fc-daygrid-day-number")) {
                    calendarContainer[0].changeView("timeGridDay", info.dateStr);
                } else {
                    let $this = $(info.dayEl).tikiModal(" ");
                    const countCals = $(".filtercal ul li").length;
                    if (countCals >= 1 || targetId != "calendar") {
                        $.openModal({
                            title: tr("New event"),
                            size: "modal-lg",
                            remote: $.service("calendar", "edit_item", { todate: info.date.toUnix(), modal: 1, return_url: returnUrl }),
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
                $.post($.service("calendar", "move"), {
                    calitemId: info.event.id,
                    delta: info.delta,
                });
            },
            height: "auto",
        });

        calendarContainer[0].render();
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

    $(document).on("submit", ".filtercal", function (e) {
        e.preventDefault();
        const form = this;
        const url = new URL($(form).attr("action"), window.location.origin);

        const checkboxes = $("input[name='calIds[]']", form);
        const checked = checkboxes.filter(":checked");

        const pageName = url.searchParams.get("page");
        // remove existing parameters
        url.search = "";

        // set page parameter
        if (pageName) {
            url.searchParams.set("page", pageName);
        }

        // show all calendars
        if (checked.length === checkboxes.length) {
            url.searchParams.set("allCals", "y");
            window.location.href = url.toString();
            return;
        }

        checked.each(function () {
            url.searchParams.append("calIds[]", this.value);
        });

        const todate = $("input[name='todate']", form).val();
        if (todate) {
            url.searchParams.set("todate", todate);
        }

        url.searchParams.set("refresh", "Refresh");

        window.location.href = url.toString();
    });
});

$.fn.defineParameterOfMultipleCalendar = function (
    fullCalendarParams,
    printingParams,
    divClassContainer,
    moduleCalendarFocusdate,
    linkToFindItemsOfCalendar,
    associatedWikiPage,
    returnUrl
) {
    let paramOfModuleCalendar = fullCalendarParams;

    /**
     *Since window.moduleCalendar is a global variable, It’s the one we use to contain all the calendars that are currently being displayed,
     *I first push an empty array into it as preparation
     *for the container where the calendar will be placed. I do this initially because I also need to know
     *the index of the calendar I will be using.

     *After that, I create the node where the calendar will be displayed and set the parameters around the
     *calendar currently being created.
     */

    window.moduleCalendar.push([]);
    let takeLength = window.moduleCalendar.length;
    const calendarContainer = window.moduleCalendar[takeLength - 1];

    var newNode = $('<div class="calendar"></div>').attr("id", "calendar-" + takeLength);
    const dateOfChangeCalendar = $('<input type="date" class="form-control date-calendar mt-2 mb-2 w-50" >')
        .attr("id", "date-calendar-" + takeLength)
        .attr("value", moduleCalendarFocusdate);

    $(divClassContainer)
        .eq(takeLength - 1)
        .append(dateOfChangeCalendar);
    $(divClassContainer)
        .eq(takeLength - 1)
        .append(newNode);
    paramOfModuleCalendar["initialDate"] = $("#date-calendar" + takeLength).val();

    displayCalendarAndPrintButton(takeLength);
    dateOfChangeCalendar.on("change", function () {
        paramOfModuleCalendar["initialDate"] = dateOfChangeCalendar.val();
        let takeIndex = dateOfChangeCalendar.attr("id").split("-")[2];
        newNode.empty();
        displayCalendarAndPrintButton(takeIndex);
    });
    function displayCalendarAndPrintButton(takeIndex) {
        const { pdf_export, pdf_warning, pref_print_pdf_from_url } = printingParams;
        newNode.setupFullCalendar(
            paramOfModuleCalendar,
            calendarContainer,
            "calendar-" + takeIndex,
            linkToFindItemsOfCalendar,
            returnUrl,
            associatedWikiPage
        );
        if (pdf_export == "y" && pdf_warning == "n") {
            const printButton = $('<a href="#" class="text-end d-none" role="button"> Export as PDF</a>').attr("id", "calendar-pdf-btn-" + takeIndex);
            $(divClassContainer)
                .eq(takeIndex - 1)
                .append(printButton);
        }
        if (pref_print_pdf_from_url != "none") {
            newNode.addFullCalendarPrint("#calendar-pdf-btn-" + takeIndex, calendarContainer);
        }
    }
};
