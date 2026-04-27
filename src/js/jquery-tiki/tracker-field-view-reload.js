/**
 * Reload handler for encrypted tracker fields in view context.
 *
 * When a decryption key entry modal is dismissed after key submission,
 * reloads the page so the field value is decrypted and displayed.
 */
(function () {
    "use strict";
    var modalEl = document.querySelector(".footer-modal");
    if (!modalEl) return;

    /** @type {number|null} Key ID captured from the last encryption-key-entry link click. */
    var pendingKeyId = null;

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

    modalEl.addEventListener("hidden.bs.modal", function () {
        if (pendingKeyId !== null) {
            pendingKeyId = null;
            location.reload();
        }
    });
})();
