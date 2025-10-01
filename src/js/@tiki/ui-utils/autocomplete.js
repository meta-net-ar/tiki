import { applyAutocomplete } from "@vue-widgets/el-autocomplete";

export default function autocomplete(element, resourceType, options = {}) {
    let remoteSourceUrl = "";
    let sourceList = [];
    let valueKey = null;

    const urlParams = new URLSearchParams(window.location.search);
    const excludepage = urlParams.get("page");

    switch (resourceType) {
        case "pagename":
            valueKey = "label";
            remoteSourceUrl =
                "tiki-listpages.php?listonly&initial=" + (options.initial ? options.initial + "&nonamespace" : "") + "&exclude_page=" + excludepage;
            break;
        case "groupname":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=groups";
            break;
        case "username":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=users";
            break;
        case "usersandcontacts":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=usersandcontacts";
            break;
        case "userrealname":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=userrealnames";
            break;
        case "tag":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=tags&separator=+";
            break;
        case "icon":
            remoteSourceUrl = null;
            sourceList = Object.keys(jqueryTiki.iconset.icons)
                .concat(jqueryTiki.iconset.defaults)
                .map((value) => ({ value }));
            break;
        case "trackername":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=trackername";
            break;
        case "calendarname":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=calendarname";
            break;
        case "trackervalue":
            remoteSourceUrl = "list-tracker_field_values_ajax.php";
            if (options.trackerId) {
                remoteSourceUrl += "?trackerId=" + options.trackerId;
            }
            if (options.fieldId) {
                const separator = remoteSourceUrl.includes("?") ? "&" : "?";
                remoteSourceUrl += separator + "fieldId=" + options.fieldId;
            }
            break;
        case "reference":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=references";
            break;
        default:
            remoteSourceUrl = null;
            sourceList = options.source.map((value) => ({ value }));
            break;
    }

    const url = remoteSourceUrl ? window.location.origin + (window.tikiroot || "/") + remoteSourceUrl : null;
    const autoCompleteArgs = [element, url, sourceList, valueKey];

    if (resourceType == "pagename" && ($(element).attr("name") == "highlight" || /^search_mod_input_\d|highlight$/.test($(element).attr("id")))) {
        const selectCb = (event) => {
            const page = event.detail[0];
            let slug = page.label;
            const scheme = jqueryTiki.wiki_url_scheme;

            if (scheme === "dash") {
                slug = page.label.replace(/ /g, "-");
            } else if (scheme === "underscore") {
                slug = page.label.replace(/ /g, "_");
            } else if (scheme === "urlencode") {
                slug = page.label.replace(/ /g, "+");
            }

            if (jqueryTiki.sefurl) {
                window.location.href = slug;
            } else {
                window.location.href = "tiki-index.php?page=" + slug;
            }
        };
        autoCompleteArgs.push(selectCb);
    } else if (options.select) {
        autoCompleteArgs.push(options.select);
    }

    const component = applyAutocomplete(...autoCompleteArgs);

    if (options.onEnter) {
        handlePressEnter(component, options.onEnter);
    }
}

const handlePressEnter = (component, callback) => {
    $(component).on("pressEnter", (event) => {
        callback(event.detail[0]);
    });
};
