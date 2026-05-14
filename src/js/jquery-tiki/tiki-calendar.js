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
    returnUrl = "tiki-calendar.php",
    associatedWikiPage = null
) {
    let isOpeningModal = false;
    this.each(function () {
        const calendarEl = document.getElementById(targetId);
        if (!calendarEl) {
            // check if element exists because an empty array is added to window.moduleCalendar for reasons i don't understand
            return;
        }
        $(calendarEl).tikiModal(tr("Loading..."));
        const toTimezoneStableIso = (dateValue) => moment(dateValue).format("YYYY-MM-DD[T]HH:mm:ssZ");
        const browserTimezone = (() => {
            try {
                return Intl.DateTimeFormat().resolvedOptions().timeZone || null;
            } catch (e) {
                return null;
            }
        })();
        const toPrefillParamValue = (value) => {
            if (value === null || typeof value === "undefined") {
                return null;
            }
            if (typeof value === "string") {
                return value;
            }
            if (value instanceof Date && !isNaN(value.getTime())) {
                return toTimezoneStableIso(value);
            }
            return String(value);
        };

        const openNewEventModal = (startValue, endValue = null) => {
            if (isOpeningModal) return;
            const countCals = $(".filtercal .calcheckbox").length;
            if (countCals >= 1 || targetId != "calendar") {
                isOpeningModal = true;
                $(calendarEl).tikiModal(" "); // Use the container for the loading overlay
                const prefillStart = toPrefillParamValue(startValue);
                if (!prefillStart) {
                    isOpeningModal = false;
                    return;
                }

                const params = {
                    prefill_start: prefillStart,
                    modal: 1,
                    return_url: returnUrl,
                };

                if (browserTimezone) {
                    params.prefill_tz = browserTimezone;
                }

                if (endValue !== null && typeof endValue !== "undefined") {
                    const prefillEnd = toPrefillParamValue(endValue);
                    if (prefillEnd) {
                        params.prefill_end = prefillEnd;
                    }
                }

                $.openModal({
                    title: tr("New event"),
                    size: "modal-lg",
                    remote: $.service("calendar", "edit_item", params),
                    open: function () {
                        $(calendarEl).tikiModal();
                        $("form:not(.no-ajax)", this)
                            .addClass("no-ajax") // Remove default ajax handling, we replace it
                            .on(
                                "submit",
                                ajaxSubmitEventHandler(function (data) {
                                    calendarEditSubmit(data, this);
                                })
                            );
                        isOpeningModal = false;
                    },
                    error: function () {
                        isOpeningModal = false;
                    },
                });
            } else {
                location.href = "tiki-calendar.php";
            }
        };
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
            unselectAuto: true,
            eventSources: [{ url: urlEventSource }],
            select: function (info) {
                // Handle Drag Selection
                openNewEventModal(info.startStr ?? info.start, info.endStr ?? info.end);
            },
            slotMinTime: eventCalendarParams.minHourOfDay,
            slotMaxTime: eventCalendarParams.maxHourOfDay,
            nowIndicator: true,
            pointer: false,
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
                // Preserve selected view across date-picker re-renders (callback can expose `data.type` or `data.view.type`).
                const mountedViewType = data?.type ?? data?.view?.type;
                // Use `data.type` for existing toolbar condition checks
                const legacyToolbarViewType = data?.type;
                if (mountedViewType) {
                    eventCalendarParams.initialView = mountedViewType;
                }
                $(calendarEl).tikiModal();
                if (legacyToolbarViewType == "dayGridMonth" || legacyToolbarViewType == "listMonth") {
                    calendarContainer[0].setOption("duration", { months: 1 });
                    if (!document.getElementById("quarter")) {
                        const ecStart = document.querySelector(".ec-start");
                        const buttonMonthView = document.createElement("div");
                        const quarterText = tr("Quarter");
                        const semesterText = tr("Semester");
                        const oneMonthText = tr("One-Month");
                        buttonMonthView.innerHTML =
                            '<button class="ec-button" id="one-month">' +
                            oneMonthText +
                            "</button>" +
                            '<button class="ec-button" id="quarter">' +
                            quarterText +
                            "</button>" +
                            '<button class="ec-button" id="semester">' +
                            semesterText +
                            "</button>";
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
                    if (legacyToolbarViewType == "timeGridWeek" || legacyToolbarViewType == "listWeek") {
                        calendarContainer[0].setOption("duration", { days: 7 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }
                    if (legacyToolbarViewType == "timeGridDay" || legacyToolbarViewType == "listDay") {
                        calendarContainer[0].setOption("duration", { days: 1 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }

                    if (legacyToolbarViewType == "listYear") {
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
                if (info.jsEvent.target.classList.contains("ec-day-head")) {
                    calendarContainer[0].changeView("timeGridDay", info.dateStr);
                    // Prevent 'select' from firing if we are just switching views
                    calendarContainer[0].unselect();
                } else {
                    // Handle Single Click
                    openNewEventModal(info.dateStr ?? info.date);
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
    eventCalendarParams,
    printingParams,
    divClassContainer,
    moduleCalendarFocusdate,
    linkToFindItemsOfCalendar,
    associatedWikiPage,
    returnUrl
) {
    let paramOfModuleCalendar = eventCalendarParams;

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
    paramOfModuleCalendar["initialDate"] = dateOfChangeCalendar.val();

    displayCalendarAndPrintButton(takeLength);
    dateOfChangeCalendar.on("change", function () {
        const currentView = calendarContainer[0]?.getView?.();
        if (currentView?.type) {
            paramOfModuleCalendar["initialView"] = currentView.type;
        }
        paramOfModuleCalendar["initialDate"] = dateOfChangeCalendar.val();
        let takeIndex = dateOfChangeCalendar.attr("id").split("-")[2];
        newNode.empty();
        displayCalendarAndPrintButton(takeIndex);
    });
    function displayCalendarAndPrintButton(takeIndex) {
        const { pdf_export, pdf_warning, pref_print_pdf_from_url } = printingParams;
        newNode.setupEventCalendar(
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
            newNode.addEventCalendarPrint("#calendar-pdf-btn-" + takeIndex, calendarContainer);
        }
    }
};
