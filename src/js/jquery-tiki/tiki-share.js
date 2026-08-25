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

    function shareScrollToMessage() {
        var el = document.getElementById("ajaxmsg");
        if (el) {
            el.scrollIntoView({ behavior: "smooth", block: "center" });
        }
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

    $form.on("submit", function (e) {
        e.preventDefault();
        var doEmail = $('input[name="do_email"]:checked').val() === "1";
        var emailDetailsVisible = $(".share-email-details").length && !$(".share-email-details").hasClass("d-none");
        var authToken = $form.data("auth-token-access") === 1 || $form.data("auth-token-access") === "1";
        var report = $('input[name="report"]').val() === "y";
        if (doEmail && emailDetailsVisible && !report) {
            var raw = shareGetAddressesRaw();
            var parts = shareSplitAddresses(raw);
            if (parts.length === 0 && !authToken) {
                showMessage(tr("Enter at least one recipient email address."), "error");
                return false;
            }
            var sender = ($('input[name="email"]').val() || "").trim();
            if (parts.length > 0 && !sender) {
                showMessage(tr("Your email is mandatory"), "error");
                return false;
            }
        }
        $form.tikiModal(tr("Please wait...."));
        var postData = $form.serializeArray().filter(function (e) {
            return e.name !== "addresses";
        });
        postData.push({ name: "addresses", value: shareGetAddressesRaw() });
        var formURL = "tiki-share.php?send=share";
        $.ajax({
            url: formURL,
            type: "POST",
            data: postData,
            success: function (data) {
                var parsed = $($.parseHTML(data));
                var shrsuccess = parsed.find("#success").html();
                var shrerror = parsed.find("#shareerror").html();
                var out = "";
                if (shrsuccess) {
                    out += "<div class='alert alert-success'>" + shrsuccess + "</div>";
                }
                if (shrerror && $.trim(shrerror)) {
                    out += "<div class='alert alert-warning'>" + shrerror + "</div>";
                }
                if (out) {
                    $("#ajaxmsg").html(out);
                    if (shrsuccess) {
                        $("#addresses").val("");
                    }
                } else {
                    $("#ajaxmsg").html(
                        "<div class='alert alert-warning'>" +
                            tr("The server response could not be interpreted. If the problem persists, check the site logs.") +
                            "</div>"
                    );
                }
                shareScrollToMessage();
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
                shareScrollToMessage();
                $form.tikiModal("");
            },
        });
        return false;
    });

    function shareBindSection(showSel, hideSel, detailsSel) {
        var $show = $(showSel);
        if (!$show.length) {
            return; // report mode: do_email is a hidden input, no radios to sync
        }
        function sync() {
            if ($show.is(":checked")) {
                $(detailsSel).removeClass("d-none");
            } else {
                $(detailsSel).addClass("d-none");
            }
        }
        $(showSel + ", " + hideSel).on("change", sync);
        sync();
    }
    shareBindSection(".share-email-show", ".share-email-hide", ".share-email-details");
    shareBindSection(".share-message-show", ".share-message-hide", ".share-message-details");
    shareBindSection(".share-forum-show", ".share-forum-hide", ".share-forum-details");

    function shareToggleForumPassword() {
        var use = $("#forumId option:selected").attr("data-forum-use-password");
        if (use && use !== "n") {
            $("#forum-password-row").removeClass("d-none");
        } else {
            $("#forum-password-row").addClass("d-none");
        }
    }
    $("#forumId").on("change", shareToggleForumPassword);
    shareToggleForumPassword();
});
