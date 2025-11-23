/**
 * Support JavaScript for FullCalendar Resource Views used by wikiplugin_trackercalendar
 */

$.fn.setupFullCalendar = function (
	fullCalendarParams,
    calendarContainer,
    targetId = "calendar",
    urlEventSource = "tiki-ajax_services.php?controller=calendar&action=list_items",
    returnUrl = "tiki-calendar.php"
) {
    this.each(function () {

        const calendarEl = document.getElementById(targetId);

        $(calendarEl).tikiModal(tr("Loading..."));

        calendarContainer[0] = new FullCalendar.Calendar(calendarEl, {
            themeSystem: 'bootstrap5',
            eventTimeFormat: {
                hour: 'numeric',
                minute: '2-digit',
                meridiem: fullCalendarParams.timeFormat,
                hour12: fullCalendarParams.timeFormat
            },
            timeZone: fullCalendarParams.display_timezone,
            locale: fullCalendarParams.language,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'year,semester,quarter,dayGridMonth,timeGridWeek,timeGridDay'
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
                day: tr("day")
            },
            allDayText: tr("all-day"),
            firstDay: fullCalendarParams.firstDayofWeek,
            slotDuration: fullCalendarParams.slotDuration,
            initialView: fullCalendarParams.initialView,
            initialDate: fullCalendarParams.initialDate,
            views: {
                quarter: {
                    type: 'dayGrid',
                    duration: {months: 3},
                    buttonText: 'quarter',
                    dayCellContent: function (dayCell) {
                        return moment(dayCell.date).format('M/D');
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf('month').toDate(),
                            end: moment(currentDate).add('2', 'months').endOf('month').toDate()
                        };
                    }
                },
                semester: {
                    type: 'dayGrid',
                    duration: {months: 6},
                    buttonText: 'semester',
                    dayCellContent: function (dayCell) {
                        return moment(dayCell.date).format('M/D');
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf('month').toDate(),
                            end: moment(currentDate).add('5', 'months').endOf('month').toDate()
                        };
                    }
                },
                year: {
                    type: 'dayGrid',
                    buttonText: tr("year"),
                    dayCellContent: function ($x) {
                        return moment($x.date).format('M/D');
                    },
                    visibleRange: function (currentDate) {
                        return {
                            start: moment(currentDate).startOf('year').toDate(),
                            end: moment(currentDate).startOf('year').add('11', 'months').endOf('month').toDate()
                        };
                    }
                }
            },
            viewDidMount: function (data) {
                $(calendarEl).tikiModal();
            },
            eventDataTransform: function (event) {
                if (event.allDay) {
                    // show all day events as including the end date day
                    event.end = moment(event.end).add(1, 'days').format('YYYY-MM-DD HH:mm:SSZ');
                }
                return event;
            },
            eventDidMount: function (arg) {
                $(calendarEl).tikiModal();
                const event = arg.event;
                const element = $(arg.el);
                const dayGrid = $('.fc-daygrid-event').length;
                if (dayGrid > 0) {
                    let backgroundColor = event._def.ui.backgroundColor;
                    let textColor = event._def.ui.textColor;
                    let categoryBackgroundColor = event._def.extendedProps.categoryBackgroundColor;
                    let eventDotElement = element.find('.fc-daygrid-event-dot'), defaultBackgroundColor;
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
                    const titleElement = element.find('.fc-event-title');

                    const styleElement = getComputedStyle(titleElement[0]);
                    const defaultTextColor = styleElement.color;
                    if (backgroundColor === '#') {
                        backgroundColor = defaultBackgroundColor;
                    }
                    if (textColor === '#') {
                        textColor = defaultTextColor;
                    }

                    var currentcalitemId = $("#currentcalitemId").text();
                    if (currentcalitemId !== "" && currentcalitemId == event.id) {
                        backgroundColor = "#FFEA00";
                        categoryBackgroundColor = "#FFEA00";
                        textColor = "#000";
                    }

                    $(element).attr('style', 'background-color: ' + backgroundColor + '; border: 1px solid ' + textColor);
                    if (categoryBackgroundColor !== '') {
                        $(element).attr('style', 'background-color: ' + categoryBackgroundColor);
                    }
                    $(element).find('.fc-event-time').attr('style', 'color: ' + textColor);
                    $(element).find('.fc-event-title').attr('style', 'color: ' + textColor);
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
                element.attr('title', event.title + "|" + event.extendedProps.description);
                element.addClass("tips");
                }
                element.attr('title', event.title + "|" + event.extendedProps.description);
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
                        remote: event.url + '&modal=1',
                        open: function () {
                            $this.tikiModal();

                            $("form:not(.no-ajax)", this)
                                .addClass('no-ajax') // Remove default ajax handling, we replace it
                                .submit(ajaxSubmitEventHandler(function (data) {
                                    calendarEditSubmit(data, this);
                                }));
                        }
                    });
                }
            },
            dateClick: function (info) {
                let $this = $(info.dayEl).tikiModal(" ");
                const countCals = $("#filtercal ul li").length;
                if (countCals >= 1 || targetId != "calendar") {
                    $.openModal({
                        title: tr("New event"),
                        size: "modal-lg",
                        remote: $.service("calendar", "edit_item", {todate: info.date.toUnix(), modal: 1, return_url: returnUrl}),
                        open: function () {
                            $this.tikiModal();

                            $("form:not(.no-ajax)", this).addClass('no-ajax');
                        }
                    });
                } else {
                    location.href = "tiki-calendar.php";
                }
            },
            eventResize: function (info) {
                $.post($.service('calendar', 'resize'), {
                    calitemId: info.event.id,
                    delta: info.endDelta
                });
            },
            eventDrop: function (info) {
                $.post($.service('calendar', 'move'), {
                    calitemId: info.event.id,
                    delta: info.delta
                });
            },
            height: 'auto'
        });
        calendarContainer[0].render();
    });

};

// open modal for edit form
$(document).on("click", ".edit-calendar-item-btn", function (e) {
    const $this = $(this);
    const $modal = $this.parents().hasClass('modal-body');
    if ($modal) {
        if ($.isFunction(e.preventDefault)) {
            e.preventDefault();
        }
        $.closeModal({
            done: function () {
                $.openModal({
                    title: tr("Edit event"),
                    size: "modal-lg",
                    remote: $this.attr("href"),
                    open: function () {
                        $this.tikiModal();

                        $("form:not(.no-ajax)", this)
                            .addClass('no-ajax') // Remove default ajax handling, we replace it
                            .submit(ajaxSubmitEventHandler(function (data) {
                                calendarEditSubmit(data, this);
                            }));
                    }
                });
            }
        });

        return false;
    }
});
