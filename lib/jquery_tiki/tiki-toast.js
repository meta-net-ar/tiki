/**
 * TikiToast — floating toast helpers for {toast} / templates/toast.tpl
 *
 * Public API (window.TikiToast):
 *   - dismiss(root)
 *   - activate(root)
 *   - showFromTemplate(templateId, hostSelector)
 */
(function (window, $) {
    "use strict";

    if (window.TikiToast) {
        return;
    }

    var dismissBound = false;

    function dismiss(root) {
        root = $(root);
        if (!root.length) {
            return;
        }

        var timer = root.data("tki-toast-timer");
        if (timer) {
            window.clearInterval(timer);
        }

        root.addClass("tki-toast--leaving");
        window.setTimeout(function () {
            root.remove();
        }, 220);
    }

    function activate(root) {
        root = $(root);
        if (!root.length || root.data("tki-toast-armed")) {
            return root;
        }

        root.data("tki-toast-armed", true);

        var duration = parseInt(root.attr("data-toast-duration"), 10) || 0;
        // if (duration > 0) {
        var remaining = duration;
        var label = root.find("[data-tki-toast-seconds]");
        var timer = window.setInterval(function () {
            remaining -= 1;
            if (remaining <= 0) {
                window.clearInterval(timer);
                dismiss(root);
                return;
            }
            label.text(String(remaining));
        }, 1000);
        root.data("tki-toast-timer", timer);
        // }

        return root;
    }

    function showFromTemplate(templateId, hostSelector) {
        var host = $(hostSelector || "#tki-toast-host");
        var tpl = document.getElementById(templateId);
        if (!host.length || !tpl) {
            return null;
        }

        var frag = tpl.content.cloneNode(true);
        var wrap = $("<div>").append(frag);
        var node = wrap.find(".tki-toast").first();
        if (!node.length) {
            return null;
        }

        node.attr("id", (node.attr("id") || "tki_toast") + "_" + Date.now());
        host.append(node);
        return activate(node);
    }

    if (!dismissBound) {
        $(document).on("click", "[data-tki-toast-dismiss]", function (e) {
            e.preventDefault();
            dismiss($(this).closest(".tki-toast"));
        });
        dismissBound = true;
    }

    window.TikiToast = {
        dismiss: dismiss,
        activate: activate,
        showFromTemplate: showFromTemplate,
    };
})(window, jQuery);
