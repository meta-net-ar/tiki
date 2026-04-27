/**
 * Unlock handler for encrypted tracker fields.
 *
 * Listens for encryption key entry modal close events and re-enables any
 * disabled encrypted field wrapper associated with the entered key. For
 * existing items, fetches the decrypted field value via AJAX to verify the
 * key and populate the field. For new items, calls verify_key to confirm the
 * entered key is correct before allowing data entry.
 */
(function () {
    "use strict";
    var modalEl = document.querySelector(".footer-modal");
    if (!modalEl) return;

    /** @type {number|null} Key ID captured from the last encryption-key-entry link click. */
    var pendingKeyId = null;

    /**
     * Captures the encryption key ID from clicks on key-entry links.
     *
     * @param {MouseEvent} e
     */
    document.addEventListener(
        "click",
        function (e) {
            var link = e.target.closest("a.encryption-key-entry");
            if (link) {
                try {
                    pendingKeyId = parseInt(new URL(link.href, location.href).searchParams.get("keyId"), 10) || null;
                } catch (ex) {}
            }
        },
        true
    );

    /**
     * Re-disables all inputs in the wrapper and restores the info div with an
     * error message and the key-entry link so the user can retry.
     *
     * @param {Element} wrap
     * @param {Element|null} info
     * @param {string} [errorMsg]
     */
    function reDisable(wrap, info, errorMsg) {
        wrap.querySelectorAll("input, textarea, select").forEach(function (el) {
            el.setAttribute("disabled", "disabled");
        });
        if (info) {
            var link = info.querySelector("a.encryption-key-entry");
            info.textContent = "";
            info.appendChild(document.createTextNode((errorMsg || "") + " "));
            if (link) info.appendChild(link);
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
    modalEl.addEventListener("hidden.bs.modal", function () {
        if (!pendingKeyId) return;
        var keyId = pendingKeyId;
        pendingKeyId = null;

        var wrap = document.querySelector('.encrypted-field-wrapper[data-encryption-key-id="' + keyId + '"]');
        if (!wrap) return;

        var info = wrap.querySelector(".encryption-key-required-info");
        var itemId = parseInt(wrap.dataset.itemId, 10);

        wrap.querySelectorAll("input, textarea, select").forEach(function (el) {
            el.removeAttribute("disabled");
        });

        var fd = new FormData();
        if (itemId > 0) {
            fd.append("keyId", keyId);
            fd.append("fieldId", wrap.dataset.fieldId || "");
            fd.append("itemId", itemId);
            fetch("tiki-ajax_services.php?controller=encryption&action=get_decrypted_value", {
                method: "POST",
                body: fd,
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data || data.error || data.value == null || data.value === "") {
                        reDisable(wrap, info, data && data.error);
                        return;
                    }
                    if (info) info.remove();
                    if (data && data.value !== undefined && data.value !== null) {
                        wrap.querySelectorAll("input[type=text], textarea").forEach(function (el) {
                            el.value = data.value;
                        });
                    }
                })
                .catch(function () {
                    reDisable(wrap, info, wrap.dataset.networkError);
                });
        } else {
            // New item: no ciphertext exists yet — verify the session key using
            // the verificationCanary stored on the key record.
            fd.append("keyId", keyId);
            fetch("tiki-ajax_services.php?controller=encryption&action=verify_key", {
                method: "POST",
                body: fd,
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    if (!data || data.error) {
                        reDisable(wrap, info, data && data.error);
                        return;
                    }
                    if (info) info.remove();
                })
                .catch(function () {
                    reDisable(wrap, info, wrap.dataset.networkError);
                });
        }
    });
})();
