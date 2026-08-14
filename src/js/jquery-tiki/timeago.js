(function ($) {
    "use strict";

    if (typeof $.timeago === "function") {
        $.timeago.settings.allowFuture = true;
        $.timeago.settings.refreshMillis = 60000;
        $.timeago.settings.cutoff = 0;

        $.fn.tikiTimeago = function () {
            return this.each(function () {
                var $time = $(this);
                var datetime = $time.attr("datetime");

                if (datetime) {
                    // Set title for tooltip if not already set
                    if (!$time.attr("title")) {
                        var date = new Date(datetime);
                        $time.attr("title", date.toLocaleString());
                    }
                    $time.timeago();

                    // Use tooltip instead of popover
                    if (typeof bootstrap !== "undefined" && typeof bootstrap.Tooltip === "function") {
                        var date = new Date(datetime);
                        var formattedDate = date.toLocaleString();

                        new bootstrap.Tooltip($time[0], {
                            title: formattedDate,
                            placement: "top",
                            trigger: "hover focus",
                            container: "body",
                        });
                    }
                }
            });
        };

        $(document).ready(function () {
            $("time.timeago").tikiTimeago();
        });

        $(document).ajaxComplete(function () {
            $("time.timeago").tikiTimeago();
        });
    }
})(jQuery);
