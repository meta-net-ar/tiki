$(function () {
    $(document).on("keyup", ".check_character_limit", function () {
        var maxChars = $(this).attr("maxlength");
        if (typeof maxChars !== "undefined" && maxChars !== null) {
            var inputLength = $(this).val().length;
            var $messageElement = $("#" + $(this).attr("id") + "_message");
            if ($messageElement.length === 0) {
                $messageElement = $('<div id="' + $(this).attr("id") + '_message" style="color: red; display: none;padding: 5px;"></div>');
                $(this).parent().append($messageElement);
            }
            if (inputLength > maxChars) {
                $messageElement.text(tr("You have exceeded the number of characters allowed (" + maxChars + ") for this field")).show();
            } else {
                $messageElement.hide();
            }
        }
    });
});
