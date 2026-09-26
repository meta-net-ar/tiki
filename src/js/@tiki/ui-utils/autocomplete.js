import { applyAutocomplete } from "@vue-widgets/el-autocomplete";

export default function autocomplete(element, resourceType, options = {}) {
    const { url, sourceList, valueKey, transformResultCb, customRemoteQueryKey, minChars } = getAutocompleteResources(resourceType, options);
    const autocompleteOptions = {
        remoteSourceUrl: url,
        sourceList,
        valueKey,
        transformResultCb,
        remoteQueryKey: customRemoteQueryKey,
        minChars: options.minChars ?? minChars,
    };

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
        autocompleteOptions.selectCb = selectCb;
    } else if (options.select) {
        autocompleteOptions.selectCb = options.select;
    }

    const component = applyAutocomplete(element, autocompleteOptions);

    if (options.onEnter) {
        handlePressEnter(component, options.onEnter);
    }
}

const handlePressEnter = (component, callback) => {
    $(component).on("pressEnter", (event) => {
        callback(event.detail[0]);
    });
};

export function getAutocompleteResources(type, options = {}) {
    let remoteSourceUrl = "";
    let sourceList = [];
    let valueKey = null;
    let transformResultCb = null;
    let customRemoteQueryKey = null;
    // Minimum characters before a remote search
    let minChars = 1;

    const urlParams = new URLSearchParams(window.location.search);
    const excludepage = urlParams.get("page");

    switch (type) {
        case "pagename":
            minChars = 2;
            valueKey = "label";
            remoteSourceUrl =
                "tiki-listpages.php?listonly&initial=" + (options.initial ? options.initial + "&nonamespace" : "") + "&exclude_page=" + excludepage;
            break;
        case "groupname":
            remoteSourceUrl = "tiki-ajax_services.php?listonly=groups";
            break;
        case "username":
            minChars = 2;
            remoteSourceUrl = "tiki-ajax_services.php?listonly=users";
            break;
        case "usersandcontacts":
            minChars = 2;
            remoteSourceUrl = "tiki-ajax_services.php?listonly=usersandcontacts";
            break;
        case "userrealname":
            minChars = 2;
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
        case "search_module":
            minChars = jqueryTiki.autocomplete_search_min_chars || 3;
            remoteSourceUrl = $.service("search", "lookup", {
                "filter~type": options.types,
                "filter~not_exact": options.excludeIdentifiers,
                autocomplete: 1, // server enforces min length and result limit
                format: jqueryTiki.feature_search_show_object_type ? "{title} ({object_type})" : "{title}",
            });
            transformResultCb = (res) => {
                return res.resultset.result.map((item) => ({
                    value: $("<div/>").append(item.title).text(), // strip HTML tags from value
                    html: item.title,
                    object_id: item.title,
                    url: $("<div/>").append(item.link).find("a").attr("href"),
                }));
            };
            customRemoteQueryKey = "filter~title";
            break;
        default:
            remoteSourceUrl = null;
            sourceList = options.source.map((value) => ({ value }));
            break;
    }

    const url = remoteSourceUrl ? window.location.origin + (window.tikiroot || "/") + remoteSourceUrl : null;

    return { url, sourceList, valueKey, transformResultCb, customRemoteQueryKey, minChars };
}
