/**
 * Support utils for tracker rules
 */

(function($) {

    $.fn.actionEditable = function(editable) {
        const disabled = ! editable;
        $(this).find("input:not(.chosen-search-input),textarea,select").each(function () {
            const $this = $(this);
            if ($this.is("input[type=hidden]")) {    // multiselect with chosen or object selector?
                const $select = $this.next("select");
                $select.prop("disabled", disabled).trigger("change.select2");
            } else {
                if (disabled) {
                    let $shadow = $("<input type=hidden>").attr("name", $this.attr("name"));
                    if ($this.is("input[type=checkbox]") && $this.is(":checked")) {
                        $shadow.val("on");
                    } else {
                        $shadow.val($this.val());
                    }
                    $this.before($shadow);
                } else {
                    $this.prev("input[type=hidden][name='" + $this.attr("name") + "']").remove();
                }
                $this.prop("disabled", disabled).trigger("change.select2");
            }
        });
    };

    $.fn.setValue = function(value) {
        $(this).find("input:not(.chosen-search-input),textarea,select").each(function () {
            const $this = $(this);
            if ($this.is("input[type=hidden]")) {    // multiselect with chosen or object selector?
                const $select = $this.next("select");
                $select.val(value).trigger("change.select2");
            } else if ($this.is("input[type=checkbox]")) {
                if (! isNaN(value)) {
                    value = parseInt(value);
                } else if (typeof value === "string") {
                    value = value.toLowerCase();
                    value = (value === "y" || value === "yes" || value === "on");
                }
                $this.prop("checked", value);
            } else if ($this.is("input[type=radio]")) {
                $("input[type=radio][name='" + $this.attr("name") + "'][value=" + value + "]", $this.form())
                    .prop("checked", true);
            } else {
                $this.val(value).trigger("change.select2");
            }
        });
    };

    $.fn.collectionContains = function(value) {
        const $this = $(this),
            currentValue = $this.val();

        if ($this.is("[type=checkbox]")) {
            // for checkboxes we have to process inputs with the same "name"
            let contains = false;
            $("input[name='" + $this.attr("name") + "']").each(function () {
                if ($(this).is(":checked") && $(this).val() === value) {
                    contains = true;
                }
            });
            return contains;
        } else if (currentValue) {
            return (currentValue.indexOf(value) > -1);
        } else {
            return false;
        }
    };

    $.fn.collectionNotContains = function(value) {
        return ! $(this).collectionContains(value);
    };

    $.fn.collectionEmpty = function() {
        const $this = $(this);

        if ($this.is("[type=checkbox]")) {
            return $("input[name='" + $this.attr("name") + "']:checked").length === 0;
        } else {
            return  $this.val().length===0;
        }
    };

    $.fn.collectionNotEmpty = function() {
        return ! $(this).collectionEmpty();
    };

})(jQuery);
