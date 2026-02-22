$("#force2FA").on("change", function () {
    $.tikiModal(tr("Saving..."));
    $.post($.service("user", "toggle2FA"), { enable: $(this).is(":checked"), user: $(this).data("user") })
        .done(function () {
            showMessage(tr("Settings saved"), "success");
        })
        .fail(function () {
            showMessage(tr("Failed to save settings"), "error");
        })
        .always(function () {
            $.tikiModal();
        });
});

$("#reset2FA").on("click", function () {
    $.openModal({
        title: tr("Reset 2FA"),
        content: tr("Are you sure you want to reset Two Factor Authentication method for this user? They will need to set it up again."),
        buttons: [
            {
                text: tr("Reset"),
                type: "danger",
                onClick: function () {
                    $.closeModal();
                    $.tikiModal(tr("Resetting..."));
                    $.post($.service("user", "reset2FA"), { user: $("#reset2FA").data("user") })
                        .done(function () {
                            showMessage(tr("2FA has been reset"), "success");
                            $("#reset2FA").prop("disabled", true);
                        })
                        .fail(function () {
                            showMessage(tr("Failed to reset 2FA"), "error");
                        })
                        .always(function () {
                            $.tikiModal();
                        });
                },
            },
        ],
    });
});
