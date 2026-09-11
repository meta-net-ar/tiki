/**
 * Unlock handler for encrypted tracker fields.
 *
 * Listens for encryption key entry modal close events and re-enables any
 * disabled encrypted field wrapper associated with the entered key. For
 * existing items, fetches the decrypted field value via AJAX to verify the
 * key and populate the field. For new items, calls verify_key to confirm the
 * entered key is correct before allowing data entry.
 */
$(function () {
    var $modal = $(".footer-modal");
    if (!$modal.length) return;

    /** @type {number|null} Key ID captured from the last encryption-key-entry link click. */
    var pendingKeyId = null;

    $(document).on("click", "a.encryption-key-entry", function () {
        var match = this.href.match(/[?&]keyId=(\d+)/);
        pendingKeyId = match ? parseInt(match[1], 10) : null;
    });

    /**
     * Re-disables all inputs in the wrapper and restores the info div with an
     * error message and the key-entry link so the user can retry.
     *
     * @param {jQuery} $wrap
     * @param {jQuery} $info
     * @param {string} [errorMsg]
     */
    function reDisable($wrap, $info, errorMsg) {
        $wrap.find("input, textarea, select").prop("disabled", true);
        if ($info && $info.length) {
            var $link = $info.find("a.encryption-key-entry");
            $info.empty().text((errorMsg || "") + " ");
            if ($link.length) $info.append($link);
        }
    }

    /**
     * On Bootstrap modal close: re-enables the encrypted field inputs then
     * verifies the entered key server-side.
     *
     * - Existing items (data-item-id > 0): calls get_decrypted_value, which
     *   decrypts the stored ciphertext and returns the plaintext — proving the
     *   key is correct via AEAD authentication. On failure the field is
     *   re-disabled and an error shown.
     *
     * - New items (data-item-id == 0): calls verify_key, which checks the
     *   entered key against the verificationCanary stored on the key record.
     *   On failure the field is re-disabled. For legacy keys without a canary
     *   the server returns verified:true (cannot check; enter_key is the only
     *   gate).
     */
    $modal.on("hidden.bs.modal", function () {
        if (!pendingKeyId) return;
        var keyId = pendingKeyId;
        pendingKeyId = null;

        var $wrap = $(".encrypted-field-wrapper[data-encryption-key-id='" + keyId + "']");
        if (!$wrap.length) return;

        var $info = $wrap.find(".encryption-key-required-info");
        var itemId = parseInt($wrap.data("itemId"), 10);

        $wrap.find("input, textarea, select").prop("disabled", false);

        if (itemId > 0) {
            $.ajax({
                type: "POST",
                url: $.service("encryption", "get_decrypted_value"),
                data: { keyId: keyId, fieldId: $wrap.data("fieldId") || "", itemId: itemId },
                dataType: "json",
            })
                .done(function (data) {
                    if (!data || data.error || data.value == null || data.value === "") {
                        reDisable($wrap, $info, data && data.error);
                        return;
                    }
                    $info.remove();
                    $wrap.find("input[type=text], textarea").val(data.value);
                    // Notify field-specific JS handlers (e.g. SEC field toggle) about unlock.
                    document.dispatchEvent(
                        new CustomEvent("tiki:unlocked", {
                            detail: { fieldId: parseInt($wrap.data("fieldId"), 10), value: data.value },
                        })
                    );
                })
                .fail(function () {
                    reDisable($wrap, $info, $wrap.data("networkError"));
                });
        } else {
            // New item: no ciphertext exists yet — verify the session key using
            // the verificationCanary stored on the key record.
            $.ajax({
                type: "POST",
                url: $.service("encryption", "verify_key"),
                data: { keyId: keyId },
                dataType: "json",
            })
                .done(function (data) {
                    if (!data || data.error) {
                        reDisable($wrap, $info, data && data.error);
                        return;
                    }
                    $info.remove();
                })
                .fail(function () {
                    reDisable($wrap, $info, $wrap.data("networkError"));
                });
        }
    });
});
