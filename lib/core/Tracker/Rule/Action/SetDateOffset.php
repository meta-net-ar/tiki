<?php

namespace Tiki\Lib\core\Tracker\Rule\Action;

use Tiki\Lib\core\Tracker\Rule\Type\DateTime;
use Tiki\Lib\core\Tracker\Rule\Type\Text;

/**
 * Tracker Rule Action: set a date field to a calculated offset from another date field.
 *
 * Argument format: "sourceField|amount|unit"
 *   - sourceField : name of the source date field  (e.g. ins_401)
 *   - amount      : integer offset, may be negative (e.g. 2)
 *   - unit        : years | months | days | hours | minutes  (default: years)
 *
 * Examples:
 *   ins_401|2        → Date of Approval + 2 years
 *   ins_401|4|years  → Date of Approval + 4 years
 *   ins_401|6|months → Date of Approval + 6 months
 *   ins_401|1|hours  → Date of Approval + 1 hour
 */
class SetDateOffset extends Action
{
    public function __construct()
    {
        parent::__construct(
            tr('Set date offset from field'),
            Text::class,
            '.length; (function(){
                var parts = "%argument%".split("|");
                var $hidden = $("[name=\"%field%\"]").first();
                if (!$hidden.length) return;
                var $form = $hidden.closest("form");
                var src = $("[name=\""+parts[0]+"\"]", $form).val();
                if (!src) return;
                var amount = parseInt(parts[1]);
                var unit   = parts[2] ? parts[2].trim() : "years";
                var d = new Date(parseInt(src) * 1000);
                if      (unit === "years")   d.setFullYear(d.getFullYear() + amount);
                else if (unit === "months")  d.setMonth(d.getMonth() + amount);
                else if (unit === "days")    d.setDate(d.getDate() + amount);
                else if (unit === "hours")   d.setHours(d.getHours() + amount);
                else if (unit === "minutes") d.setMinutes(d.getMinutes() + amount);
                var ts = Math.floor(d.getTime() / 1000);
                $hidden.val(ts).trigger("change");
                var $txt = $hidden.closest("td,div,tr").find("input[type=text]");
                if ($txt.length) $txt.val(
                    ("0"+d.getDate()).slice(-2)+"/"+("0"+(d.getMonth()+1)).slice(-2)+"/"+d.getFullYear()
                );
            })()',
            [DateTime::class]
        );
    }
}
