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
    associatedWikiPage = null,
    defaultCalendarId = null
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
        const maxMonthEventsPerDay = 3;
        const monthDayHeadFontSize = "0.8rem";
        const monthEventTimeFontSize = "0.7rem";
        const monthEventTitleFontSize = "14px";
        let currentViewType = eventCalendarParams.initialView;
        let monthEventLimitFrame = null;

        const isMonthGridView = () => currentViewType === "dayGridMonth";

        const getDayDateString = (dayElement) => dayElement?.querySelector(":scope > .ec-day-head time")?.getAttribute("datetime") || "";

        const getMainMonthEventsContainer = (dayElement) => {
            const eventContainers = Array.from(dayElement.querySelectorAll(":scope > .ec-events:not(.ec-preview)"));
            return eventContainers[eventContainers.length - 1] || null;
        };

        const getMonthDayEvents = (dayElement) => {
            const eventContainer = getMainMonthEventsContainer(dayElement);
            if (!eventContainer) {
                return [];
            }
            return Array.from(eventContainer.children).filter((eventElement) => eventElement.classList.contains("ec-event"));
        };

        const clearMonthEventLimit = () => {
            calendarEl.querySelectorAll(".tiki-calendar-month-event-hidden").forEach((eventElement) => {
                eventElement.classList.remove("tiki-calendar-month-event-hidden");
                eventElement.hidden = false;
                eventElement.style.display = "";
            });
            calendarEl.querySelectorAll(".tiki-calendar-month-has-more").forEach((dayElement) => {
                dayElement.classList.remove("tiki-calendar-month-has-more");
            });
            calendarEl.querySelectorAll(".tiki-calendar-more-events").forEach((moreButton) => moreButton.remove());
            calendarEl.querySelectorAll(".ec-day-grid .ec-day > .ec-events").forEach((eventContainer) => {
                eventContainer.style.paddingBottom = "";
            });
        };

        const applyMonthEventLimit = () => {
            clearMonthEventLimit();
            if (!isMonthGridView()) {
                return;
            }

            calendarEl.querySelectorAll(".ec-day-grid .ec-body .ec-day").forEach((dayElement) => {
                dayElement.querySelector(":scope > .ec-day-head")?.style.setProperty("font-size", monthDayHeadFontSize);
                const events = getMonthDayEvents(dayElement);
                if (events.length <= maxMonthEventsPerDay) {
                    return;
                }

                events.slice(maxMonthEventsPerDay).forEach((eventElement) => {
                    eventElement.classList.add("tiki-calendar-month-event-hidden");
                    eventElement.hidden = true;
                    eventElement.style.display = "none";
                });

                const hiddenEventCount = events.length - maxMonthEventsPerDay;
                const dateString = getDayDateString(dayElement);
                const dayFoot = dayElement.querySelector(":scope > .ec-day-foot");
                const eventContainer = getMainMonthEventsContainer(dayElement);
                if (!dayFoot || !dateString) {
                    return;
                }

                dayElement.classList.add("tiki-calendar-month-has-more");
                if (eventContainer) {
                    eventContainer.style.paddingBottom = "1.45rem";
                }

                const moreButton = document.createElement("button");
                moreButton.type = "button";
                moreButton.className = "tiki-calendar-more-events btn btn-link btn-sm p-0";
                moreButton.dataset.date = dateString;
                moreButton.style.setProperty("font-size", monthDayHeadFontSize);
                moreButton.textContent = tr("+%0 more events").replace("%0", hiddenEventCount);
                moreButton.title = tr("Show all events for this day");
                dayFoot.appendChild(moreButton);
            });
        };

        const scheduleMonthEventLimit = () => {
            if (monthEventLimitFrame !== null) {
                window.cancelAnimationFrame(monthEventLimitFrame);
            }
            monthEventLimitFrame = window.requestAnimationFrame(() => {
                monthEventLimitFrame = window.requestAnimationFrame(() => {
                    monthEventLimitFrame = null;
                    applyMonthEventLimit();
                });
            });
        };

        const switchToDayView = (dateValue) => {
            if (!dateValue || !calendarContainer[0]) {
                return;
            }
            eventCalendarParams.initialView = "timeGridDay";
            eventCalendarParams.initialDate = dateValue;
            currentViewType = "timeGridDay";
            calendarContainer[0].setOption("date", dateValue);
            calendarContainer[0].setOption("view", "timeGridDay");
            calendarContainer[0].unselect();
        };

        const getMoreEventsControlFromEvent = (event) => {
            if (!(event.target instanceof Element) || !isMonthGridView()) {
                return null;
            }
            return event.target.closest(".tiki-calendar-more-events") || event.target.closest(".ec-day-foot a, .ec-day-foot button");
        };

        calendarEl.addEventListener(
            "pointerdown",
            function (event) {
                if (getMoreEventsControlFromEvent(event)) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                }
            },
            true
        );

        calendarEl.addEventListener(
            "click",
            function (event) {
                const moreControl = getMoreEventsControlFromEvent(event);
                if (!moreControl) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();

                const dateString = moreControl.dataset.date || getDayDateString(moreControl.closest(".ec-day"));
                if (dateString) {
                    switchToDayView(dateString);
                }
            },
            true
        );

        calendarEl.addEventListener(
            "keydown",
            function (event) {
                const moreControl = getMoreEventsControlFromEvent(event);
                if ((event.key === "Enter" || event.key === " ") && moreControl) {
                    event.preventDefault();
                    event.stopPropagation();
                    event.stopImmediatePropagation();
                    moreControl.click();
                }
            },
            true
        );

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
                    defaultCalendarId: defaultCalendarId,
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
        const getMonthRangeButtons = () => {
            const monthRangeContainer = calendarEl.querySelector("#month-range-controls");
            if (!monthRangeContainer) {
                return {};
            }

            return {
                oneMonth: monthRangeContainer.querySelector("#one-month"),
                quarter: monthRangeContainer.querySelector("#quarter"),
                semester: monthRangeContainer.querySelector("#semester"),
            };
        };
        const removeMonthRangeControls = () => {
            const monthRangeContainer = calendarEl.querySelector("#month-range-controls");
            if (monthRangeContainer) {
                monthRangeContainer.remove();
            }
        };
        const setMonthRangeButtonState = (activeButtonId = "one-month") => {
            const { oneMonth, quarter, semester } = getMonthRangeButtons();
            if (!oneMonth || !quarter || !semester) {
                return;
            }
            oneMonth.classList.toggle("ec-active", activeButtonId === "one-month");
            quarter.classList.toggle("ec-active", activeButtonId === "quarter");
            semester.classList.toggle("ec-active", activeButtonId === "semester");
        };
        const normalizeMonthRangeSpan = function (monthSpan) {
            const parsedMonthSpan = Number(monthSpan);
            return parsedMonthSpan === 3 || parsedMonthSpan === 6 ? parsedMonthSpan : 1;
        };
        const getMonthRangeButtonId = function (monthSpan) {
            const normalizedMonthSpan = normalizeMonthRangeSpan(monthSpan);
            if (normalizedMonthSpan === 3) {
                return "quarter";
            }
            if (normalizedMonthSpan === 6) {
                return "semester";
            }
            return "one-month";
        };
        let activeMonthRangeSpan = normalizeMonthRangeSpan(eventCalendarParams.initialMonthRangeSpan);
        const listPeriodOptions = {
            week: { label: tr("Week"), duration: { weeks: 1 } },
            month: { label: tr("Month"), duration: { months: 1 }, focusMonths: 1 },
            quarter: { label: tr("Quarter"), duration: { months: 3 }, focusMonths: 3 },
            semester: { label: tr("Semester"), duration: { months: 6 }, focusMonths: 6 },
            year: { label: tr("Year"), duration: { years: 1 }, focusMonths: 12 },
        };
        const listPeriodNames = Object.keys(listPeriodOptions);
        const normalizeListPeriod = (period) => (listPeriodNames.includes(period) ? period : "year");
        let activeListPeriod = normalizeListPeriod(eventCalendarParams.initialListPeriod);
        // Keep the user's focus separate from aligned ranges such as January-December.
        let activeListFocusDate = null;
        let activeListFocusDay = null;
        let pendingListNavigation = null;
        const getCalendarDate = () => moment(calendarContainer[0].getOption("date"));
        const getListFocusDate = () => (activeListFocusDate?.isValid() ? activeListFocusDate.clone() : getCalendarDate());
        const setListFocusDate = (date, preserveFocusDay = false) => {
            const focusDate = moment(date);
            if (!focusDate.isValid()) {
                return;
            }
            activeListFocusDate = focusDate;
            if (!preserveFocusDay) {
                activeListFocusDay = focusDate.date();
            }
        };
        const getListDuration = (periodOptions, focusDate) => {
            if (!eventCalendarParams.calendarListBeginsFocus) {
                return { ...periodOptions.duration };
            }
            if (!periodOptions.focusMonths) {
                return { days: 7 };
            }
            return { days: focusDate.clone().add(periodOptions.focusMonths, "months").diff(focusDate, "days") };
        };
        const getNavigatedListFocusDate = (direction) => {
            const focusDate = getListFocusDate();
            const periodOptions = listPeriodOptions[activeListPeriod];
            if (!periodOptions.focusMonths) {
                return focusDate.add(direction * 7, "days");
            }
            const focusDay = activeListFocusDay || focusDate.date();
            const targetDate = focusDate.date(1).add(direction * periodOptions.focusMonths, "months");
            return targetDate.date(Math.min(focusDay, targetDate.daysInMonth()));
        };
        // Native toolbar navigation changes the date first; datesSet then reapplies the exact List range.
        const bindListNavigation = () => {
            ["prev", "next", "today"].forEach((action) => {
                const button = calendarEl.querySelector(".ec-" + action);
                if (button && !button.dataset.listNavigationBound) {
                    button.addEventListener(
                        "click",
                        () => {
                            if (eventCalendarParams.initialView === "listYear") {
                                pendingListNavigation = action;
                            }
                        },
                        true
                    );
                    button.dataset.listNavigationBound = "1";
                }
            });
        };
        const getListPeriodButtons = () => {
            const listPeriodContainer = calendarEl.querySelector("#list-period-controls");
            if (!listPeriodContainer) {
                return {};
            }

            return listPeriodNames.reduce((buttons, period) => {
                buttons[period] = listPeriodContainer.querySelector('[data-list-period="' + period + '"]');
                return buttons;
            }, {});
        };
        const removeListPeriodControls = () => {
            const listPeriodContainer = calendarEl.querySelector("#list-period-controls");
            if (listPeriodContainer) {
                listPeriodContainer.remove();
            }
        };
        const setListPeriodButtonState = (activePeriod = "year") => {
            const buttons = getListPeriodButtons();
            listPeriodNames.forEach((period) => {
                if (buttons[period]) {
                    buttons[period].classList.toggle("ec-active", period === activePeriod);
                }
            });
        };
        const applyListPeriod = (period) => {
            const normalizedPeriod = normalizeListPeriod(period);
            const selectedPeriod = listPeriodOptions[normalizedPeriod];
            activeListPeriod = normalizedPeriod;
            eventCalendarParams.initialListPeriod = normalizedPeriod;
            const focusDate = getListFocusDate();
            setListFocusDate(focusDate, activeListFocusDate?.isValid());
            const displayDate =
                !eventCalendarParams.calendarListBeginsFocus && normalizedPeriod === "year" ? focusDate.clone().startOf("year") : focusDate;
            calendarContainer[0].setOption("date", displayDate.toDate());
            calendarContainer[0].setOption("duration", getListDuration(selectedPeriod, focusDate));
            calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                return moment(dayCell).format("D");
            });
            setListPeriodButtonState(normalizedPeriod);
        };
        const formatMonthRangeTitle = (startDate, monthSpan = 1) => {
            const rangeStart = moment(startDate).startOf("month");
            if (!rangeStart.isValid()) {
                return "";
            }
            if (monthSpan <= 1) {
                return rangeStart.format("MMMM YYYY");
            }
            const rangeEnd = rangeStart.clone().add(monthSpan - 1, "months");
            if (rangeStart.year() === rangeEnd.year()) {
                return rangeStart.format("MMMM") + " - " + rangeEnd.format("MMMM YYYY");
            }
            return rangeStart.format("MMMM YYYY") + " - " + rangeEnd.format("MMMM YYYY");
        };
        const applyMonthRange = (monthSpan, activeButtonId) => {
            const normalizedMonthSpan = normalizeMonthRangeSpan(monthSpan);
            activeMonthRangeSpan = normalizedMonthSpan;
            eventCalendarParams.initialMonthRangeSpan = normalizedMonthSpan;
            calendarContainer[0].setOption("duration", { months: normalizedMonthSpan });
            calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                return moment(dayCell).format(normalizedMonthSpan === 1 ? "D" : "M/D");
            });
            setMonthRangeButtonState(activeButtonId || getMonthRangeButtonId(normalizedMonthSpan));
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
            views: {
                dayGridMonth: {
                    titleFormat: function (startDate) {
                        return formatMonthRangeTitle(startDate, activeMonthRangeSpan);
                    },
                },
            },
            viewDidMount: function (data) {
                // Normalize view type because callback payload can expose either `data.type` or `data.view.type`.
                const mountedViewType = data?.type ?? data?.view?.type;
                const previousViewType = eventCalendarParams.initialView;
                if (mountedViewType) {
                    eventCalendarParams.initialView = mountedViewType;
                    currentViewType = mountedViewType;
                }
                if (previousViewType === "listYear" && currentViewType !== "listYear" && activeListFocusDate?.isValid()) {
                    calendarContainer[0].setOption("date", activeListFocusDate.toDate());
                    activeListFocusDate = null;
                    activeListFocusDay = null;
                    pendingListNavigation = null;
                } else if (currentViewType === "listYear" && previousViewType !== "listYear") {
                    setListFocusDate(getCalendarDate());
                }
                $(calendarEl).tikiModal();
                bindListNavigation();
                if (currentViewType == "dayGridMonth" || currentViewType == "listMonth") {
                    removeListPeriodControls();
                    if (!calendarEl.querySelector("#quarter")) {
                        const ecEnd = calendarEl.querySelector(".ec-end");
                        const buttonMonthView = document.createElement("div");
                        buttonMonthView.id = "month-range-controls";
                        buttonMonthView.className = "ec-button-group calendar-period-controls";
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
                        ecEnd.appendChild(buttonMonthView);
                    }

                    const { oneMonth, quarter, semester } = getMonthRangeButtons();
                    if (oneMonth && !oneMonth.dataset.rangeHandlerBound) {
                        oneMonth.addEventListener("click", () => applyMonthRange(1, "one-month"));
                        oneMonth.dataset.rangeHandlerBound = "1";
                    }
                    if (quarter && !quarter.dataset.rangeHandlerBound) {
                        quarter.addEventListener("click", () => applyMonthRange(3, "quarter"));
                        quarter.dataset.rangeHandlerBound = "1";
                    }
                    if (semester && !semester.dataset.rangeHandlerBound) {
                        semester.addEventListener("click", () => applyMonthRange(6, "semester"));
                        semester.dataset.rangeHandlerBound = "1";
                    }
                    applyMonthRange(activeMonthRangeSpan, getMonthRangeButtonId(activeMonthRangeSpan));
                } else if (currentViewType == "listYear") {
                    removeMonthRangeControls();
                    if (!calendarEl.querySelector("#list-period-controls")) {
                        const ecEnd = calendarEl.querySelector(".ec-end");
                        const listPeriodView = document.createElement("div");
                        listPeriodView.id = "list-period-controls";
                        listPeriodView.className = "ec-button-group calendar-period-controls";
                        listPeriodView.innerHTML = listPeriodNames
                            .map(function (period) {
                                return '<button class="ec-button" data-list-period="' + period + '">' + listPeriodOptions[period].label + "</button>";
                            })
                            .join("");
                        ecEnd.appendChild(listPeriodView);
                    }

                    const listPeriodButtons = getListPeriodButtons();
                    listPeriodNames.forEach((period) => {
                        if (listPeriodButtons[period] && !listPeriodButtons[period].dataset.periodHandlerBound) {
                            listPeriodButtons[period].addEventListener("click", () => applyListPeriod(period));
                            listPeriodButtons[period].dataset.periodHandlerBound = "1";
                        }
                    });
                    applyListPeriod(activeListPeriod);
                } else {
                    removeMonthRangeControls();
                    removeListPeriodControls();
                    if (currentViewType == "timeGridWeek" || currentViewType == "listWeek") {
                        calendarContainer[0].setOption("duration", { weeks: 1 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }
                    if (currentViewType == "timeGridDay" || currentViewType == "listDay") {
                        calendarContainer[0].setOption("duration", { days: 1 });
                        calendarContainer[0].setOption("dayCellFormat", function (dayCell) {
                            return moment(dayCell).format("D");
                        });
                    }
                }
                scheduleMonthEventLimit();
            },
            datesSet: function (data) {
                if (data?.view?.type !== "listYear" || !pendingListNavigation) {
                    return;
                }
                const navigation = pendingListNavigation;
                pendingListNavigation = null;
                const focusDate = navigation === "today" ? getCalendarDate() : getNavigatedListFocusDate(navigation === "next" ? 1 : -1);
                setListFocusDate(focusDate, navigation !== "today");
                applyListPeriod(activeListPeriod);
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
                    const timeElement = element.find(".ec-event-time");
                    timeElement.css({
                        color: textColor,
                    });
                    titleElement.css({
                        color: textColor,
                    });
                    if (element.closest(".ec-day-grid").length) {
                        timeElement.css({
                            fontSize: monthEventTimeFontSize,
                            lineHeight: "1.2",
                            overflow: "hidden",
                            textOverflow: "ellipsis",
                            whiteSpace: "nowrap",
                        });
                        titleElement.css({
                            display: "-webkit-box",
                            fontSize: monthEventTitleFontSize,
                            WebkitBoxOrient: "vertical",
                            WebkitLineClamp: "2",
                            lineHeight: "1.2",
                            maxHeight: "2.4em",
                            overflow: "hidden",
                            overflowWrap: "anywhere",
                            textOverflow: "ellipsis",
                            whiteSpace: "normal",
                        });
                    }
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
                scheduleMonthEventLimit();
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
                    switchToDayView(info.dateStr);
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
    returnUrl,
    defaultCalendarId = null
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
            associatedWikiPage,
            defaultCalendarId
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
