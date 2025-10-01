/**
 * JS helpers for Tiki Connect - used on admin/connect so far
 *
 *
 */
$("#connect_list_btn").on("click", function(e) {
    e.preventDefault();
    const spinner = $(this).tikiModal(tr(" "));

    $.getJSON($.service('connect', 'list'), (data, status) => {
        $.openModal({
            title: tr("Tiki Connect Data Preview"),
            dialogVariants: ["centered", "scrollable"],
            open: function () {
                spinner.tikiModal();
                const modalBody = $(this).find('.modal-body');
                if (data) {
                    modalBody.html("<strong>" + tr("Tiki Version") + "</strong>").append($("<p>" + data.version + "</p>"));

                    const formatList = function(inArray) {
                        const $dl = $("<dl />");
                        for (const key in inArray) {
                            if (inArray.hasOwnProperty(key)) {
                                $dl.append($("<dt>" + key + "</dt><dd>" + inArray[key] + "</dd>"));
                            }
                        }
                        return $dl;
                    };

                    const $din = $("<div />");
                    const $tabs = $("<ul class='nav nav-tabs' role='tabList' />").appendTo($din);        // list for tabs
                    const $tabContent = $("<div class='tab-content' />").appendTo($din);

                    if (data.prefs) {
                        $tabs.append("<li class='nav-item' role='presentation'><a class='nav-link active' data-bs-toggle='tab' href='#' data-bs-target='#ctab-m'>" + tr("Prefs") + "</a></li>");
                        $("<div id='ctab-m' class='tab-pane active' />").append(formatList(data.prefs)).appendTo($tabContent);
                    }
                    if (data.site) {
                        $tabs.append("<li class='nav-item' role='presentation'><a class='nav-link' data-bs-toggle='tab' href='#' data-bs-target='#ctab-p'>" + tr("Site Info") + "</a></li>");
                        $("<div id='ctab-p' class='tab-pane' />").append(formatList(data.site)).appendTo($tabContent);
                    }
                    if (data.server) {
                        $tabs.append("<li class='nav-item' role='presentation'><a class='nav-link' data-bs-toggle='tab' href='#' data-bs-target='#ctab-s'>" + tr("Server") + "</a></li>");
                        $("<div id='ctab-s' class='tab-pane' />").append(formatList(data.server)).appendTo($tabContent);
                    }
                    if (data.tables) {
                        $tabs.append("<li class='nav-item' role='presentation'><a class='nav-link' data-bs-toggle='tab' href='#' data-bs-target='#ctab-d'>" + tr("Database") + "</a></li>");
                        $("<div id='ctab-d' class='tab-pane' />").append(formatList(data.tables)).appendTo($tabContent);
                    }
                    if (data.votes) {
                        $tabs.append("<li class='nav-item' role='presentation'><a class='nav-link' data-bs-toggle='tab' href='#' data-bs-target='#ctab-v'>" + tr("Votes") + "</a></li>");
                        $("<div id='ctab-v' class='tab-pane' />").append(formatList(data.votes)).appendTo($tabContent);
                    }

                    $din.appendTo(modalBody);
                }
            },
        });
    }).fail(function () {
        spinner.tikiModal();
        $('html, body').animate({scrollTop: $("#tikifeedback").offset().top}, 500);
    });
});

$("#connect_send_btn").on("click", function() {

    var spinner = $(this).tikiModal(" ");

    $.getJSON($.service('connect', 'send'), function (data, status) {
        if (data && data.message) {
            if (data.status === 'pending') {
                var cap = prompt(data.message, "");
                if (cap) {
                    $.getJSON($.service('connect', 'send'), {
                        guid: data.guid,
                        captcha: cap
                    }, function (data, status) {
                        alert(data.message);
                        if (data.status === "confirmed") {
                            $("input[name=connect_guid]").val(data.guid);    // already set server-side but update form to match
                        }
                    }).fail(function (xhr) {
                        $('html, body').animate({scrollTop: $("#tikifeedback").offset().top}, 500);
                    });
                } else {
                    $.getJSON($.service('connect', 'cancel'), {
                        guid: data.guid
                    });
                }
            } else {
                alert(data.message);
            }
        } else {
            alert(tr("The server did not reply"));
        }
    }).fail(function (xhr) {
        $('html, body').animate({ scrollTop: $("#tikifeedback").offset().top }, 500);
    }).always(function () {
        spinner.tikiModal();
        return false;
    });
    return false;
});

$("#connect_feedback_cbx").on("click", function(){
    var spinner = $(this).parent().tikiModal(" ");
    if ($("#connect_feedback_cbx:checked").length > 0) {
        $(".adminoptionbox .tikihelp, .adminoptionbox .icon:not(.connectVoter)").eachAsync({
            bulk: 0,    // needs bulk:0 to smooth out the animation it seems
            loop: function() { $(this).hide(); }
        });
        $(".connectVoter").eachAsync({
            bulk: 0,
            loop: function() { $(this).show(); },
            end: function() { spinner.tikiModal(); }
        });
        setCookie("show_tiki_connect", 1, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
    } else {
        $(".adminoptionbox .tikihelp, .adminoptionbox .icon:not(.connectVoter)").eachAsync({
            bulk: 0,
            loop: function() { $(this).show(); }
        });
        $(".connectVoter").eachAsync({
            bulk: 0,
            loop: function() {  $(this).hide(); },
            end: function() { spinner.tikiModal(); }
        });
        deleteCookie("show_tiki_connect");
    }
});

if (getCookie("show_tiki_connect")) {
    $("#connect_feedback_cbx").trigger("click");
}

var connectVote = function(pref, vote, el) {
    var spinner = $(el).parent().tikiModal(" ");
    if ($(el).data("newVote")) {
        vote = $(el).data("newVote");
    }
    $.getJSON($.service("connect", "vote", {"pref": pref, "vote": vote }), function (json) {
        if (json && json.newVote ) {
            $(el).data("newVote", json.newVote).find("span").setIcon(json.newVote);
        }
        spinner.tikiModal();
    }).fail(function (){
        alert(tr("Tiki Connect is not set up properly. Please visit admin/connect/settings to configure the feature."));
    });
};
