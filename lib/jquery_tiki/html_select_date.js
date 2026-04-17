// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/** {html_select_date}: sync day options; refresh el-select via native change (applySelect.js). */
(function ($) {
    function pad(n, w) {
        w = parseInt(w, 10);
        return String(n).padStart(w > 0 ? w : 2, "0");
    }

    function sync($c) {
        var $m = $c.find("select[data-date-part=month]");
        var $d = $c.find("select[data-date-part=day]");
        if (!$m.length || !$d.length) {
            return;
        }

        var month = parseInt($m.val(), 10);
        if (!month) {
            return;
        }

        var year = parseInt($c.find("select[data-date-part=year]").val(), 10);
        if (isNaN(year)) {
            year = new Date().getFullYear();
        }

        var max = new Date(year, month, 0).getDate();
        var prior = $d.val();
        var priorN = parseInt(prior, 10);
        var vw = $c.attr("data-day-value-width");
        var lw = $c.attr("data-day-label-width");
        var html;
        var i;

        if ($d.find('option[value!=""]').length !== max) {
            var empty = $d.find('option[value=""]').first();
            html = empty.length ? '<option value="">' + empty.text() + "</option>" : "";
            for (i = 1; i <= max; i++) {
                html += '<option value="' + pad(i, vw) + '">' + pad(i, lw) + "</option>";
            }
            $d.html(html);
            if (priorN > 0 && !isNaN(priorN)) {
                $d.val(pad(Math.min(priorN, max), vw));
            } else if (!prior) {
                $d.val("");
            }
        } else if (priorN > max && !isNaN(priorN)) {
            $d.val(pad(max, vw));
        }

        $d.trigger("change");
    }

    function init(root) {
        $(".html-select-date", root || document).each(function () {
            sync($(this));
        });
    }

    $(function () {
        init(document);
    });
    $(document)
        .on("tiki.modal.redraw", ".modal.fade", function () {
            var modal = this;
            init(modal);
            setTimeout(function () {
                init(modal);
            }, 0);
        })
        .on("change.htmlSelectDate", ".html-select-date select[data-date-part=month], .html-select-date select[data-date-part=year]", function () {
            sync($(this).closest(".html-select-date"));
        });
})(jQuery);
