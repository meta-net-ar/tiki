/* (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

$(function () {
    $(document).tiki("copy")(
        function () {
            return $("#share-generated-link-input").val() || "";
        },
        function () {
            var text = $("#share-generated-link-input").val() || "";
            if (!$.trim(text)) {
                showMessage(tr("No link to copy."), "warning");
                return;
            }
            showMessage(tr("Link copied to clipboard."), "success");
        },
        function () {
            showMessage(tr("Copy failed. Please copy manually."), "error");
        },
        "#copy-share-link-btn"
    );

    var $form = $("#share-form");
    if (!$form.length) {
        return;
    }

    function shareGetAddressesRaw() {
        var $a = $("#addresses");
        var v;
        if ($a.length) {
            v = $a.val();
        } else {
            v = $('input[name="addresses"]').val();
        }
        if ($.isArray(v)) {
            return v.join(", ");
        }
        return v || "";
    }

    function shareSplitAddresses(s) {
        if (!s || !$.trim(s)) {
            return [];
        }
        return s
            .split(/[,;]/)
            .map(function (x) {
                return $.trim(x);
            })
            .filter(function (x) {
                return x.length > 0;
            });
    }

    function shareIsValidEmailLocal(part) {
        if (!part || part.length > 254) {
            return false;
        }
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(part);
    }

    $form.on("submit", function (e) {
        e.preventDefault();
        var doEmail = $('input[name="do_email"]:checked').val() === "1";
        var emailDetailsVisible = $(".share-email-details").length && !$(".share-email-details").hasClass("hidden");
        var authToken = $form.data("auth-token-access") === 1 || $form.data("auth-token-access") === "1";
        var report = $('input[name="report"]').val() === "y";
        if (doEmail && emailDetailsVisible && !report) {
            var raw = shareGetAddressesRaw();
            var parts = shareSplitAddresses(raw);
            if (parts.length === 0 && !authToken) {
                showMessage(tr("Enter at least one recipient email address."), "error");
                return false;
            }
            var bad = [];
            for (var i = 0; i < parts.length; i++) {
                if (!shareIsValidEmailLocal(parts[i])) {
                    bad.push(parts[i]);
                }
            }
            if (bad.length) {
                showMessage(tr("Invalid email(s):") + " " + bad.join(", "), "error");
                return false;
            }
            var sender = ($('input[name="email"]').val() || "").trim();
            if (parts.length > 0 && !sender) {
                showMessage(tr("Your email is mandatory"), "error");
                return false;
            }
        }
        $form.tikiModal(tr("Please wait...."));
        var postData = $form.serializeArray();
        var formURL = "tiki-share.php?send=share";
        $.ajax({
            url: formURL,
            type: "POST",
            data: postData,
            success: function (data) {
                var shrsuccess = $($.parseHTML(data)).find("#success").html();
                var shrerror = $($.parseHTML(data)).find("#shareerror").html();
                if (shrsuccess) {
                    $("#ajaxmsg").html("<div class='alert alert-success'>" + shrsuccess + "</div>");
                    $("#addresses").val("");
                } else if (shrerror && $.trim(shrerror)) {
                    $("#ajaxmsg").html("<div class='alert alert-warning'>" + shrerror + "</div>");
                } else {
                    $("#ajaxmsg").html(
                        "<div class='alert alert-warning'>" +
                            tr("The server response could not be interpreted. If the problem persists, check the site logs.") +
                            "</div>"
                    );
                }
                $form.tikiModal("");
            },
            error: function (jqXHR, textStatus, errorThrown) {
                var msg = tr("Request failed.");
                if (jqXHR.status) {
                    msg += " (" + jqXHR.status + ")";
                }
                if (errorThrown) {
                    msg += " " + errorThrown;
                }
                $("#ajaxmsg").html("<div class='alert alert-danger'>" + $("<div/>").text(msg).html() + "</div>");
                $form.tikiModal("");
            },
        });
        return false;
    });

    $(".share-email-hide").on("click", function () {
        $(".share-email-details").addClass("hidden");
    });
    $(".share-email-show").on("click", function () {
        $(".share-email-details").removeClass("hidden");
    });
    $(".share-message-hide").on("click", function () {
        $(".share-message-details").addClass("hidden");
    });
    $(".share-message-show").on("click", function () {
        $(".share-message-details").removeClass("hidden");
    });
    $(".share-forum-hide").on("click", function () {
        $(".share-forum-details").addClass("hidden");
    });
    $(".share-forum-show").on("click", function () {
        $(".share-forum-details").removeClass("hidden");
    });
});
