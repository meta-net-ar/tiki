/**
 * Reload handler for encrypted tracker fields in view context.
 *
 * When a decryption key entry modal is dismissed after key submission,
 * reloads the page so the field value is decrypted and displayed.
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

    $modal.on("hidden.bs.modal", function () {
        if (pendingKeyId !== null) {
            pendingKeyId = null;
            location.reload();
        }
    });
});
