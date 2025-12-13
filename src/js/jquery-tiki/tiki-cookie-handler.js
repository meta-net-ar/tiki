window.CookieHandler = (() => {
    const BUILTIN_COOKIE_CATEGORY_ESSENTIAL = JSON.parse(jqueryTiki.BUILTIN_COOKIE_CATEGORY_ESSENTIAL);

    function setConsentCookies(actionType = "customized") {
        const COOKIE_CONSENT_NAME = jqueryTiki.cookie_consent_name;
        const COOKIE_CATEGORIES = JSON.parse(jqueryTiki.cookie_consent_categories);
        const getcookie_consent_value = {
            action: "customized",
            categories: {},
            consentGiven: false,
        };

        const ACTION_TYPES = { CUSTOMIZED: "customized", ACCEPT_ALL: "acceptAll", DECLINE_UNNECESSARY: "declineUnnecessary" };
        const COOKIE_CONSENT_VALUE = getcookie_consent_value;
        const exp = new Date();
        exp.setTime(exp.getTime() + 24 * 60 * 60 * 1000 * jqueryTiki.cookie_consent_expires);
        jqueryTiki.no_cookie = false;

        // Store category consent values properly as key-value pairs
        COOKIE_CONSENT_VALUE.categories = {}; // Reset categories to an object

        COOKIE_CATEGORIES.forEach((category) => {
            const isChecked = $(`#toggle${capitalize(category)}`).is(":checked");
            COOKIE_CONSENT_VALUE.categories[category] = isChecked; // Store with category name as key
        });

        // Determine if the state is customized (some but not all checkboxes selected)
        let allChecked = true;
        let noneChecked = true;
        $("#customConsentSection input[type='checkbox']").each(function () {
            if (!$(this).prop("disabled")) {
                if ($(this).is(":checked")) {
                    noneChecked = false;
                } else {
                    allChecked = false;
                }
            }
        });

        $("#customConsentSectionBanner input[type='checkbox']").each(function () {
            if (!$(this).prop("disabled")) {
                if ($(this).is(":checked")) {
                    noneChecked = false;
                } else {
                    allChecked = false;
                }
            }
        });

        if (actionType === ACTION_TYPES.ACCEPT_ALL) {
            COOKIE_CONSENT_VALUE.action = ACTION_TYPES.ACCEPT_ALL;
            COOKIE_CONSENT_VALUE.categories = {};
            COOKIE_CATEGORIES.forEach((category) => {
                COOKIE_CONSENT_VALUE.categories[category] = true;
            });
        } else if (actionType === ACTION_TYPES.CUSTOMIZED) {
            COOKIE_CONSENT_VALUE.action = ACTION_TYPES.CUSTOMIZED;
        } else if (actionType === ACTION_TYPES.DECLINE_UNNECESSARY) {
            COOKIE_CONSENT_VALUE.action = ACTION_TYPES.DECLINE_UNNECESSARY;
            COOKIE_CONSENT_VALUE.categories = {};
            COOKIE_CATEGORIES.forEach((category) => {
                COOKIE_CONSENT_VALUE.categories[category] = category === BUILTIN_COOKIE_CATEGORY_ESSENTIAL; // Only essential category is true
            });
        }

        COOKIE_CONSENT_VALUE.consentGiven = true;
        // Store the entire consent object (including action and categories) in a single cookie
        setCookieBrowser(COOKIE_CONSENT_NAME, encodeURIComponent(JSON.stringify(COOKIE_CONSENT_VALUE)), "", exp);
        $(document).trigger("cookies.consent.agree");
    }

    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function hideConsentElement() {
        if (jqueryTiki.cookie_consent_mode === "dialog") {
            $(`#${jqueryTiki.cookie_consent_dom_id}`).modal("hide");
        } else {
            $(`#${jqueryTiki.cookie_consent_dom_id}`).fadeOut("fast");
        }
    }

    function updateTopLevelAction() {
        let allNonEssentialChecked = true;
        let allNonEssentialUnchecked = true;

        $("#customConsentSection input[type='checkbox']").each(function () {
            if ($(this).prop("disabled") || $(this).attr("name").includes(BUILTIN_COOKIE_CATEGORY_ESSENTIAL)) {
                return;
            }
            if ($(this).is(":checked")) {
                allNonEssentialUnchecked = false;
            } else {
                allNonEssentialChecked = false;
            }
        });
        $("#customConsentSectionBanner input[type='checkbox']").each(function () {
            if ($(this).prop("disabled") || $(this).attr("name").includes(BUILTIN_COOKIE_CATEGORY_ESSENTIAL)) {
                return;
            }
            if ($(this).is(":checked")) {
                allNonEssentialUnchecked = false;
            } else {
                allNonEssentialChecked = false;
            }
        });

        if (allNonEssentialChecked) {
            $("#acceptAllCheck").prop("checked", true);
            $("#declineUnnecessaryCheck").prop("checked", false);
            $("#customizedCheck").prop("checked", false).parent().hide(); // Hide customized checkbox
        } else if (allNonEssentialUnchecked) {
            $("#declineUnnecessaryCheck").prop("checked", true);
            $("#acceptAllCheck").prop("checked", false);
            $("#customizedCheck").prop("checked", false).parent().hide(); // Hide customized checkbox
        } else {
            $("#acceptAllCheck").prop("checked", false);
            $("#declineUnnecessaryCheck").prop("checked", false);
            $("#customizedCheck").prop("checked", true).parent().show(); // Show customized checkbox
        }
    }

    return {
        setConsentCookies,
        hideConsentElement,
        updateTopLevelAction,
    };
})();

$(document).ready(() => {
    CookieHandler.updateTopLevelAction();
    $("#customConsentSection input[type='checkbox']").on("change", function () {
        CookieHandler.updateTopLevelAction();
    });
    $("#customConsentSectionBanner input[type='checkbox']").on("change", function () {
        CookieHandler.updateTopLevelAction();
    });

    $("#cookie_consent_preference").on("click", () => {
        CookieHandler.setConsentCookies("acceptAll");
        CookieHandler.hideConsentElement();
        return false;
    });

    $("#cookie_decline_unnecessary_button").on("click", () => {
        CookieHandler.setConsentCookies("declineUnnecessary");
        CookieHandler.hideConsentElement();
        return false;
    });

    $("#cookie_save_button").on("click", () => {
        CookieHandler.setConsentCookies("customized");
        CookieHandler.hideConsentElement();
        return false;
    });

    if (jqueryTiki.cookie_consent_mode === "banner") {
        setTimeout(() => {
            $(`#${jqueryTiki.cookie_consent_dom_id}`).slideDown("slow");
        }, 500);
    } else if (jqueryTiki.cookie_consent_mode === "dialog") {
        setTimeout(() => {
            const cookieDialogElement = $(`#${jqueryTiki.cookie_consent_dom_id}`);
            cookieDialogElement.modal({
                backdrop: jqueryTiki.cookie_consent_disable === "y" ? "static" : true,
                keyboard: jqueryTiki.cookie_consent_disable === "y" ? false : true,
            });
            cookieDialogElement.modal("show").css({
                backgroundColor: "transparent",
                color: "black",
                border: "transparent",
            });
        }, 500);
    }

    $("#acceptAllCheck").on("change", function () {
        if ($(this).is(":checked")) {
            $("#declineUnnecessaryCheck").prop("checked", false);
            $("#customConsentSection input[type='checkbox']").each(function () {
                if (!$(this).prop("disabled")) {
                    $(this).prop("checked", true);
                }
            });
            $('#customConsentSectionBanner input[type="checkbox"]').each(function () {
                if (!$(this).prop("disabled")) {
                    $(this).prop("checked", true);
                }
            });
        }
        CookieHandler.updateTopLevelAction();
    });

    $("#declineUnnecessaryCheck").on("change", function () {
        if ($(this).is(":checked")) {
            $("#acceptAllCheck").prop("checked", false);
            $("#customConsentSection input[type='checkbox']").each(function () {
                if (!$(this).prop("disabled")) {
                    $(this).prop("checked", false);
                }
            });
            $('#customConsentSectionBanner input[type="checkbox"]').each(function () {
                if (!$(this).prop("disabled")) {
                    $(this).prop("checked", false);
                }
            });
        }
        CookieHandler.updateTopLevelAction();
    });
});
