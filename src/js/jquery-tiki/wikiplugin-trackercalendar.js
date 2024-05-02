/**
 * Support JavaScript for FullCalendar Resource Views used by wikiplugin_trackercalendar
 */

import { Calendar } from "@fullcalendar/core";
import resourceTimelinePlugin from "@fullcalendar/resource-timeline";
import resourceTimeGridPlugin from "@fullcalendar/resource-timegrid";
import moment from "moment";

$.fn.setupFullCalendar = function (tcPluginParams) {
    this.each(function () {
        let cal = this;
        $(cal).tikiModal(tr("Loading..."));

        let storeEvent = function (eventInfo) {
            let event = eventInfo.event,
                end = event.end,
                start = event.start,
                request = {
                    itemId: event.id,
                    trackerId: tcPluginParams.trackerId,
                    ajax: true,
                };

            if (!end) {
                end = start;
            }

            request["fields~" + tcPluginParams.begin] = moment(start).unix() + start.getTimezoneOffset() * 60;
            request["fields~" + tcPluginParams.end] = moment(end).unix() + end.getTimezoneOffset() * 60;

            let resource = event.getResources();
            if (resource.length) {
                resource = resource[0];
            }
            request["fields~" + tcPluginParams.resource] = resource.title;

            $.post($.service("tracker", "update_item"), request, null, "json");
        };

        let slotLabelTimeFormat = {
            hour: "numeric",
            minute: "2-digit",
            meridiem: tcPluginParams.timeFormat,
            hour12: tcPluginParams.timeFormat,
        };
        var calendar = new Calendar(cal, {
            plugins: [resourceTimelinePlugin, resourceTimeGridPlugin],
            themeSystem: "bootstrap",
            schedulerLicenseKey: tcPluginParams.premiumLicense,
            initialDate: tcPluginParams.initialDate,
            eventTimeFormat: {
                hour: "numeric",
                minute: "2-digit",
                meridiem: tcPluginParams.timeFormat,
                hour12: tcPluginParams.timeFormat,
            },
            views: {
                timeGrid: {
                    // options apply to timeGridWeek and timeGridDay views
                    slotLabelFormat: slotLabelTimeFormat,
                },
                resourceTimelineDay: {
                    // options apply to timeGridWeek and timeGridDay views
                    slotLabelFormat: slotLabelTimeFormat,
                },
                resourceTimelineWeek: {
                    slotLabelInterval: "24:00:00",
                    slotDuration: "06:00:00",
                    slotLabelFormat: [
                        // top level of text
                        {
                            month: "long",
                            year: "numeric",
                        },
                        // lower level of text
                        {
                            day: "numeric",
                            weekday: "short",
                        },
                    ],
                },
            },
            viewClassNames: function (currentView) {
                $(cal).tikiModal();
                console.debug(currentView.view.type); // useful for debugging
            },
            timeZone: tcPluginParams.display_timezone,
            headerToolbar: {
                left: "prevYear,prev,next,nextYear today",
                center: "title",
                right: tcPluginParams.views,
            },
            editable: true,
            events: $.service(
                "tracker_calendar",
                "list",
                $.extend(tcPluginParams.filterValues, {
                    trackerId: tcPluginParams.trackerId,
                    colormap: tcPluginParams.colormap,
                    beginField: tcPluginParams.begin,
                    endField: tcPluginParams.end,
                    resourceField: tcPluginParams.resource,
                    coloringField: tcPluginParams.coloring,
                    filters: tcPluginParams.body,
                    maxRecords: tcPluginParams.maxEvents,
                })
            ),
            buttonText: {
                resourceTimelineDay: tcPluginParams.labelResDay,
                resourceTimelineWeek: tcPluginParams.labelResWeek,
                resourceTimelineMonth: tcPluginParams.labelResMonth,
                resourceTimelineYear: tcPluginParams.labelResYear,
                listDay: tcPluginParams.labelListDay,
                listWeek: tcPluginParams.labelListWeek,
                listMonth: tcPluginParams.llabelListMonth,
                listYear: tcPluginParams.labelListYear,
                today: tcPluginParams.labelToday,
                resourceTimeGridWeek: tcPluginParams.labelAgendaWeek,
                resourceTimeGridDay: tcPluginParams.labelAgendaDay,
            },
            resources: tcPluginParams.resourceList,
            allDayText: tcPluginParams.labelAllDay,
            firstDay: tcPluginParams.firstDayofWeek,
            slotDuration: tcPluginParams.slotDuration,
            slotMinTime: tcPluginParams.minHourOfDay,
            slotMaxTime: tcPluginParams.maxHourOfDay,
            initialView: tcPluginParams.dView,
            eventClick: function (eventData) {
                eventData.jsEvent.preventDefault();
                if (tcPluginParams.url) {
                    const event = eventData.event;
                    let actualURL = tcPluginParams.url;
                    actualURL += actualURL.indexOf("?") === -1 ? "?" : "&";

                    if (tcPluginParams.trkitemid === "y" && tcPluginParams.addAllFields === "n") {
                        // "simple" mode
                        actualURL += "itemId=" + event.id;
                    } else {
                        let lOp = "";
                        let html = $.parseHTML(event.description) || [];

                        // Store useful data values to the URL for Wiki Argument Variable
                        // use and to javascript session storage for JQuery use
                        actualURL += "trackerid=" + event.trackerId;
                        if (event.trkitemid === "y") {
                            actualURL = actualURL + "&itemId=" + event.id;
                        } else {
                            actualURL = actualURL + "&itemid=" + event.id;
                        }
                        actualURL = actualURL + "&title=" + event.title;
                        actualURL = actualURL + "&end=" + event.end;
                        actualURL = actualURL + "&start=" + event.start;
                        if (tcPluginParams.useSessionStorage) {
                            sessionStorage.setItem("trackerid", event.trackerId);
                            sessionStorage.setItem("title", event.title);
                            sessionStorage.setItem("start", event.start);
                            sessionStorage.setItem("itemid", event.id);
                            sessionStorage.setItem("end", event.end);
                            sessionStorage.setItem("eventColor", event.color);
                        }

                        // Capture the description HTML as variables
                        // with the label being the variable name
                        $.each(html, function (i, el) {
                            if (isEven(i) == true) {
                                lOp = el.textContent.replace(" ", "_");
                            } else {
                                actualURL = actualURL + "&" + lOp + "=" + el.textContent;
                                if (tcPluginParams.useSessionStorage) {
                                    sessionStorage.setItem(lOp, el.textContent);
                                }
                            }
                        });
                    }

                    location.href = actualURL;
                    return false;
                } else {
                    // standard tracker item view/edit
                    let e = eventData.event;

                    if (e.startEditable && e.extendedProps.trackerId) {
                        var info = {
                            trackerId: e.extendedProps.trackerId,
                            itemId: e.id,
                        };
                        $.openModal({
                            remote: $.service("tracker", "update_item", info),
                            size: "modal-lg",
                            title: e.title,
                            open: function () {
                                $("form:not(.no-ajax)", this)
                                    .addClass("no-ajax") // Remove default ajax handling, we replace it
                                    .on(
                                        "submit",
                                        ajaxSubmitEventHandler(function (data) {
                                            $(this).parents(".modal").modal("hide");
                                            calendar.refetchEvents();
                                        })
                                    );
                            },
                        });
                        return false;
                    } else {
                        return true;
                    }
                }
            },
            eventDidMount: function (arg) {
                let event = arg.event;
                let element = $(arg.el);
                element.attr("title", event.title);
                element.popover({
                    trigger: "hover",
                    html: true,
                    content: event.extendedProps.description,
                    container: "body",
                    delay: { show: 250, hide: 500 },
                    customClass: "popover-sm",
                });
            },
            dateClick: function (date, jsEvent, view) {
                if (tcPluginParams.canInsert) {
                    var info = {
                        trackerId: tcPluginParams.trackerId,
                    };
                    let momentDate = moment(date.date);
                    info[tcPluginParams.beginFieldName] = momentDate.unix();
                    info[tcPluginParams.endFieldName] = momentDate.add(1, "h").unix();
                    if (tcPluginParams.url) {
                        $('<a href="#"/>').attr("href", tcPluginParams.url);
                    } else {
                        $.openModal({
                            remote: $.service("tracker", "insert_item", info),
                            size: "modal-lg",
                            title: tcPluginParams.addTitle,
                            open: function () {
                                $("form:not(.no-ajax)", this)
                                    .addClass("no-ajax") // Remove default ajax handling, we replace it
                                    .on(
                                        "submit",
                                        ajaxSubmitEventHandler(function (data) {
                                            $(this).parents(".modal").modal("hide");
                                            calendar.refetchEvents();
                                        })
                                    );
                            },
                        });
                    }
                }

                return false;
            },
            eventResize: storeEvent,
            eventDrop: storeEvent,
            height: "auto",
            dayMinWidth: 150, // will cause horizontal scrollbars
        });
        calendar.render();

        if (jqueryTiki.print_pdf_from_url !== "none") {
            $(function () {
                $("#" + tcPluginParams.id).addFullCalendarPrint("#calendar-pdf-btn", calendar);
            });
        }
    });
};
