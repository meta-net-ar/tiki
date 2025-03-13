$("a:not(.inline-cypht a)").each(function () {
    $(this).data("external", true);
});

$(document).on("click", ".inline-cypht .menu_contacts a", function (e) {
    e.preventDefault();
    window.location.href = "tiki-contacts.php";
});

if (hm_mobile()) {
    if (!$("body").hasClass("mobile")) $("body").addClass("mobile");
}
