import { createStore } from "vuex";
import { toRaw } from "vue";
import { v4 as uuidv4 } from "uuid";
import moment from "moment-timezone";
import strftime from "strftime";

export default createStore({
    strict: import.meta.env.MODE !== "production",
    state: {
        userPrefs: {},
        trackers: [],
        editedItem: {},
    },
    getters: {
        getPref(state) {
            return (key) => state.userPrefs[key];
        },
        getOfflineTrackers(state) {
            return () => state.trackers.filter((t) => t.options.offline);
        },
        getField(state) {
            return (trackerId, fieldId) => {
                return getField(state, trackerId, fieldId);
            };
        },
        getTracker(state) {
            return (trackerId) => {
                return findTracker(state, trackerId);
            };
        },
        getTrackerItem(state) {
            return (trackerId, itemId) => {
                return getTrackerItem(state, trackerId, itemId);
            };
        },
        renderFieldOutput(state) {
            return (item, field) => {
                return renderFieldOutput(state, item, field);
            };
        },
        getRemoteLinkedItems(state) {
            return (item, field) => {
                return getRemoteLinkedItems(state, item, field);
            };
        },
    },
    actions: {},
    mutations: {
        initState(state, data) {
            state.userPrefs = data.userPrefs;
            state.trackers = data.trackerData;
            state.trackers.forEach((tracker) => {
                if (typeof tracker.items === "undefined") {
                    tracker.items = [];
                }
                state.editedItem[tracker.trackerId] = {
                    itemId: null,
                    values: {},
                };
            });
        },
        storeEditedItem(state, data) {
            let tracker = findTracker(state, data.trackerId);
            let values = JSON.parse(JSON.stringify(toRaw(state.editedItem[data.trackerId].values)));
            if (state.editedItem[data.trackerId].itemId === null) {
                values.offlineAutoId = uuidv4();
                tracker.items.push(values);
            } else {
                tracker.items[state.editedItem[data.trackerId].itemId] = values;
            }
            updateItemLinkPossibilities(state, tracker, values);
        },
        changeEditedItem(state, data) {
            if (!data) {
                return;
            }
            // normal initialization
            state.editedItem[data.trackerId].itemId = data.itemId;
            state.editedItem[data.trackerId].values = {};
            if (data.trackerId !== null && data.itemId !== null) {
                // initialize from tracker item
                let tracker = findTracker(state, data.trackerId);
                state.editedItem[data.trackerId].values = JSON.parse(JSON.stringify(toRaw(tracker.items[data.itemId])));
            }
            // defaults, especially empty arrays
            if (data.trackerId !== null) {
                let tracker = findTracker(state, data.trackerId);
                if (tracker.options.show_status) {
                    if (typeof state.editedItem[data.trackerId].values.status === "undefined") {
                        state.editedItem[data.trackerId].values.status = Object.keys(tracker.options.status_types)[0];
                    }
                }
                for (let field of tracker.fields) {
                    if (typeof state.editedItem[data.trackerId].values[field.ins_id] === "undefined") {
                        state.editedItem[data.trackerId].values[field.ins_id] = defaultFieldValue(state, field);
                    }
                }
            }
        },
        deleteItem(state, data) {
            let tracker = findTracker(state, data.trackerId);
            removeItemLinkPossibilities(state, tracker, tracker.items[data.itemId].offlineAutoId);
            delete tracker.items[data.itemId];
            tracker.items = Object.values(toRaw(tracker.items));
        },
    },
});

function defaultFieldValue(state, field) {
    switch (field.type) {
        case "c":
            return field.defaultvalue == "y";
        case "FG":
        case "M":
            return [];
        case "f":
            if (field.options_map.blankdate == "blank") {
                return { day: "", month: "", year: "", hour: "", minute: "", meridian: "" };
            } else {
                let today = new Date();
                return {
                    day: today.getDate(),
                    month: (today.getMonth() + 1).toFixed(0).padStart(2, "0"),
                    year: today.getFullYear(),
                    hour: "00",
                    minute: "00",
                    meridian: "am",
                };
            }
        case "j":
            if (field.options_map.useNow) {
                return { date: moment.unix(), timezone: state.userPrefs["timezone"] };
            } else {
                return { date: "", timezone: state.userPrefs["timezone"] };
            }
        case "r":
        case "w":
            if (field.canHaveMultipleValues) {
                return [];
            } else {
                return "";
            }
        case "u":
            if (field.options_map.autoassign > 0) {
                if (field.canHaveMultipleValues) {
                    return [jqueryTiki.username];
                } else {
                    return jqueryTiki.username;
                }
            } else {
                if (field.canHaveMultipleValues) {
                    return [];
                } else {
                    return "";
                }
            }
        default:
            return "";
    }
}

function updateItemLinkPossibilities(state, updatedTracker, updatedItem) {
    state.trackers.forEach((tracker) => {
        tracker.fields.forEach((field) => {
            if (field.type == "r" && field.options_map.trackerId == updatedTracker.trackerId) {
                let displayFieldsList = field.displayFieldsList;
                if (!displayFieldsList || displayFieldsList == []) {
                    displayFieldsList = [field.options_map.fieldId];
                }
                let rendered = [];
                displayFieldsList.forEach((fieldId) => {
                    let displayField = findField(updatedTracker, fieldId);
                    rendered[fieldId] = updatedItem[displayField.ins_id];
                });
                let formatted = field.options_map.displayFieldsListFormat;
                if (formatted) {
                    for (let i = 0; i < rendered.length; i++) {
                        formatted = formatted.replace("%" + i.toString(), Object.values(rendered)[i]);
                    }
                } else {
                    formatted = Object.values(rendered).join(" ");
                }
                field.possibilities[updatedItem.offlineAutoId] = formatted;
            }
            if (field.type == "w" && field.options_map.trackerId == updatedTracker.trackerId) {
                let listField = findField(updatedTracker, field.options_map.listFieldIdThere);
                field.possibilities[updatedItem.offlineAutoId] = renderFieldOutput(state, updatedItem, listField);
            }
        });
    });
}

function removeItemLinkPossibilities(state, updatedTracker, offlineAutoId) {
    state.trackers.forEach((tracker) => {
        tracker.fields.forEach((field) => {
            if ((field.type == "r" || field.type == "w") && field.options_map.trackerId == updatedTracker.trackerId) {
                // check if not already used
                if (tracker.items.filter((item) => item[field.ins_id] == offlineAutoId).length > 0) {
                    throw "This item has been linked from another tracker item and cannot be deleted.";
                } else {
                    delete field.possibilities[offlineAutoId];
                }
            }
        });
    });
}

function findTracker(state, trackerId) {
    for (let tracker of state.trackers) {
        if (tracker.trackerId == trackerId) {
            return tracker;
        }
    }
}

function findField(tracker, fieldId) {
    for (let field of tracker.fields) {
        if (field.fieldId == fieldId) {
            return field;
        }
    }
}

function getField(state, trackerId, fieldId) {
    const tracker = findTracker(state, trackerId);
    if (tracker) {
        return findField(tracker, fieldId);
    } else {
        return null;
    }
}

function getTrackerItem(state, trackerId, itemId) {
    const tracker = findTracker(state, trackerId);
    return tracker.existing_items[itemId];
}

function getItemsList(state, trackerId, fieldId, value) {
    const tracker = findTracker(state, trackerId);
    let items = [];
    if (!tracker) {
        return items;
    }
    const field = findField(tracker, fieldId);
    for (let itemId in tracker.existing_items) {
        let item = tracker.existing_items[itemId];
        item.itemId = itemId;
        let remoteValue = item[fieldId];
        if (!remoteValue) {
            continue;
        }
        remoteValue = remoteValue.value;
        if (typeof remoteValue === "object") {
            if (remoteValue.indexOf(value) > -1) {
                items.push(item);
            }
        } else {
            if (remoteValue == value) {
                items.push(item);
            }
        }
    }
    for (let item of tracker.items) {
        let remoteValue = item[field.ins_id];
        if (typeof remoteValue === "object") {
            if (remoteValue.indexOf(value) > -1) {
                items.push(item);
            }
        } else {
            if (remoteValue == value) {
                items.push(item);
            }
        }
    }
    return items;
}

function renderFieldOutput(state, item, field) {
    let value = item[field.ins_id];
    if (typeof value === "undefined") {
        value = item[field.fieldId];
        if (typeof value === "object") {
            value = value.value;
        }
    }
    // TODO: components?
    switch (field.type) {
        case "c":
            return value ? tr("Yes") : tr("No");
        case "d":
        case "R":
            return field.possibilities[value] || "";
        case "D":
            return field.possibilities[value] || value;
        case "l":
            return renderItemsList(state, item, field);
        case "M":
            return value.map((v) => field.possibilities[v]).join(", ");
        case "f":
        case "j":
            let date = {};
            if (field.type == "j") {
                let m = moment.unix(value.date);
                switch (field.options_map.outputTimezone) {
                    case "1":
                        m = m.tz(value.timezone);
                        break;
                    case "2":
                        let date = {
                            year: m.year(),
                            month: m.month() + 1,
                            day: m.date(),
                            hour: m.hour(),
                            minute: m.minute(),
                        };
                        m = m.tz(value.timezone);
                        let date2 = {
                            year: m.year(),
                            month: m.month() + 1,
                            day: m.date(),
                            hour: m.hour(),
                            minute: m.minute(),
                        };
                        return (
                            renderDateTimeOutput(state, date, field) +
                            state.userPrefs["timezone"] +
                            " | " +
                            renderDateTimeOutput(state, date2, field) +
                            value.timezone
                        );
                    case "0":
                    default:
                    // noop
                }
                date = {
                    year: m.year(),
                    month: m.month() + 1,
                    day: m.date(),
                    hour: m.hour(),
                    minute: m.minute(),
                };
            } else {
                date = Object.assign({}, value);
            }
            return renderDateTimeOutput(state, date, field);
        case "FG":
            return value.map((f) => f.name).join(", ");
        case "r":
        case "u":
        case "w":
            if (field.canHaveMultipleValues) {
                return value.map((v) => field.possibilities[v]).join(", ");
            } else {
                return field.possibilities[value] || value;
            }
        default:
            return value;
    }
}

function renderDateTimeOutput(state, date, field) {
    if (!state.userPrefs["use_24hr_clock"] && date.meridian == "pm") {
        date.hour = (parseInt(date.hour) + 12).toFixed(0);
    }
    let shortDate = strftime(jqueryTiki.short_date_format, new Date(Date.parse(date.year + "-" + date.month + "-" + date.day)));
    let shortTime = strftime(
        jqueryTiki.short_time_format,
        new Date(Date.parse(date.year + "-" + date.month + "-" + date.day + " " + date.hour + ":" + date.minute))
    );
    if (jqueryTiki.jquery_timeago && field.options_map.useTimeAgo) {
        return `<time class="timeago" datetime="${date.year}-${date.month}-${date.day}T${date.hour}:${date.minute}:00">${shortDate} ${shortTime}</time>`;
    }
    if (field.options_map.datetime == "d") {
        return shortDate;
    }
    if (field.options_map.datetime == "t") {
        return shortTime;
    }
    let current = strftime(jqueryTiki.short_date_format, new Date());
    if (shortDate == current && jqueryTiki.tiki_same_day_time_only == "y") {
        return shortTime;
    } else {
        return shortDate + " " + shortTime;
    }
}

// this code is largely similar to ItemsList/DynamicList field handler getItemIds method
function getRemoteLinkedItems(state, currentItem, field) {
    let linkedItems = [];
    let fieldIdHere = null;
    let fieldIdThere = null;
    if (field.type == "l") {
        fieldIdHere = field.options_map.fieldIdHere;
        fieldIdThere = field.options_map.fieldIdThere;
    } else if (field.type == "w") {
        fieldIdHere = field.options_map.filterFieldIdHere;
        fieldIdThere = field.options_map.filterFieldIdThere;
    } else {
        throw "Can't get remote linked items for field that is not ItemsList or DynamicList.";
    }
    const fieldHere = getField(state, field.trackerId, fieldIdHere);
    const fieldThere = getField(state, field.options_map.trackerId, fieldIdThere);
    if (fieldThere && (fieldThere.type == "r" || fieldThere.type == "w") && (!fieldHere || fieldHere.type != "r")) {
        // remotely linked items can"t have any value as the only items displayed to the user are newly created offline items that can"t be linked remotely
        return linkedItems;
    }
    if (fieldHere && fieldHere.type == "q" && fieldHere.options_map.itemId == "itemId") {
        // auto-increment remotely linked items can"t have any value for offline items
        return linkedItems;
    }
    if (!fieldHere) {
        // if no field is configured to match, we can"t display a value
        return linkedItems;
    }
    let localValue = currentItem[fieldHere.ins_id];
    if (typeof localValue === "undefined") {
        localValue = currentItem[fieldHere.fieldId];
        if (typeof localValue === "object") {
            localValue = localValue.value;
        }
    }
    if (!fieldThere && (fieldHere.type == "r" || fieldHere.type == "w" || fieldHere.type === "math") && localValue) {
        // itemlink/dynamic item list field in this tracker pointing directly to an item in the other tracker
        const item = getTrackerItem(state, field.trackerId, localValue);
        item.itemId = localValue;
        if (item) {
            linkedItems.push(item);
        }
    }
    // TODO: r = item link
    // TODO: w = dynamic item list - localvalue is the itemid of the target item. so rewrite.
    // u = user selector, might be mulitple users so need to find multiple values
    else if (fieldHere.type == "u" && fieldHere.options_map.multiple && localValue) {
        if (typeof localValue != "object") {
            const users = localValue.split(",");
        } else {
            const users = localValue;
        }
        for (user of users) {
            linkedItems = linkedItems.concat(getItemsList(state, field.options_map.trackerId, fieldThere.fieldId, user));
        }
    }
    // e = category, might be mulitple categories so need to find multiple values
    else if (fieldHere.type == "e" && localValue) {
        if (typeof localValue != "object") {
            categories = localValue.split(",");
        } else {
            categories = localValue;
        }
        for (category of categories) {
            linkedItems = linkedItems.concat(getItemsList(state, field.options_map.trackerId, fieldThere.fieldId, category));
        }
    }
    // REL = relation field can contain items from the target tracker which we can use to feed our ItemsList field
    else if (fieldHere.type == "REL" && localValue) {
        const relations = localValue.split("\n");
        for (relation of relations) {
            const typeAndId = relation.split(":");
            if (typeAndId[0] == "trackeritem") {
                const item = getTrackerItem(state, field.options_map.trackerId, typeAndId[1]);
                item.itemId = typeAndId[1];
                if (item) {
                    linkedItems.push(item);
                }
            }
        }
    }
    // Skip nulls
    else if (localValue) {
        linkedItems = linkedItems.concat(getItemsList(state, field.options_map.trackerId, fieldThere.fieldId, localValue));
    }
    return linkedItems;
}

// similar to ItemsList::getItemLabels
function renderItemsList(state, currentItem, field) {
    return getRemoteLinkedItems(state, currentItem, field)
        .map((item) => {
            let result = "";
            const displayFields = field.options_map.displayFieldIdThere;
            const format = field.options_map.displayFieldIdThereFormat;
            if (displayFields && displayFields[0]) {
                let values = [];
                for (let displayFieldId of displayFields) {
                    values.push(renderFieldOutput(state, item, getField(state, field.options_map.trackerId, displayFieldId)));
                }
                if (format) {
                    result = format;
                    for (let i = values.length - 1; i >= 0; i--) {
                        result = result.replace("%" + i, values[i]);
                    }
                } else {
                    result = values.join(" ");
                }
            } else {
                const tracker = getTracker(state, field.options_map.trackerId);
                let mainValues = [];
                let firstField = null;
                for (let remoteField of tracker.fields) {
                    if (!firstField) {
                        firstField = remoteField;
                    }
                    if (field.isMain == "y") {
                        mainValues.push(renderFieldOutput(state, item, remoteField));
                    }
                }
                if (mainValues) {
                    result = mainValues.join(" ");
                } else if (firstField) {
                    result = renderFieldOutput(state, item, firstField);
                } else {
                    result = "";
                }
            }
            return result;
        })
        .join("<br/>");
}
