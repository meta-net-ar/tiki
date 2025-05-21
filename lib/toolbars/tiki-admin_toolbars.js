// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* Include for tiki-admin_toolbars.php
 *
 * Selector vars set up in tiki-admin_toolbars.php:
 *
 */


$(function () {

    $(".rows .flex-row").each(function () {
        Sortable.create(this, {
            group: "flex-row",
            ghostClass: "toolbars-placeholder",
            onAdd: function (event) {
                $(event.item).addClass("navbar-text");
            },
        });
    });

    $("ul.full").each(function () {
        Sortable.create(this, {
            group: "flex-row",
            ghostClass: "toolbars-placeholder",
            sort: false,
            filter: '.qt-noaccess',
            onAdd: (event) => {
                const $item = $(event.item).css('float', '');

                if ($item.text() === '-') {
                    $(this).children().remove('.qt--');                // remove all seps
                    $(this).prepend($item.clone());            // put one back at the top

                } else if ($(this).attr('id') === 'full-list-c') {    // dropped in custom list
                    $item.on("dblclick", function () { showToolEditForm(event.item); });
                    $item.trigger('dblclick');
                }
                sortList(this);
            },
            onRemove: (event) => {
                const $item = $(event.item);
                if ($item.text() === '-' || $item.text() === '|') {
                    $(this).prepend($item.clone());    // leave a copy at the top of the full list
                }
            },
        });
    });

    const sortList = function (list) {
        var arr = $(list).children().get(), item, labelA, labelB;
        arr.sort(function(a, b) {
            labelA = $(a).text().toUpperCase();
            labelB = $(b).text().toUpperCase();
            if (labelA < labelB) { return -1; }
            if (labelA > labelB) { return 1; }
            return 0;
        });
        $(list).empty();
        for (item = 0; item < arr.length; item++) {
            $(list).append(arr[item]);
        }
        if ($(list).attr("id") === "full-list-c") {
            $('.qt-custom').on("dblclick", function () { showToolEditForm(this); });
        }
    };
    $('.qt-custom').on("dblclick", function () { showToolEditForm(this); });

    // show edit form dialogue
    var showToolEditForm = function (item) {

        $(".footer-modal:not(.show)").first()
            .modal("show")
            .on("shown.bs.modal", function () {
                const $this = $(this);

                const $toolType = $("#tool_type", $this);
                const $toolPlugin = $("#tool_plugin", $this);
                const $toolName = $("#tool_name", $this);
                const $toolLabel = $("#tool_label", $this);
                const $toolIcon = $("#tool_icon", $this);
                const $toolToken = $("#tool_token", $this);
                const $toolSyntax = $("#tool_syntax", $this);

                if (item) {
                    const $item = $(item);
                    $toolName.val($item.text().trim());
                    $toolLabel.val($item.find("input[name=label]").val().trim());
                    if ($item.children("img").length && $item.children("img").attr("src") !== "img/icons/shading.png") {
                        $toolIcon.val($item.children("img").attr("src"));
                    } else {
                        const iconname = $("span.icon", item).attr("class").match(/icon-(\w*)/);
                        if (iconname) {
                            $toolIcon.val(iconname[1]);
                        }
                    }
                    $toolToken.val($item.find("input[name=token]").val());
                    $toolSyntax.val($item.find("input[name=syntax]").val());
                    $toolType.val($item.find("input[name=type]").val());
                    if ($item.find("input[name=type]").val() === "Wikiplugin") {
                        $toolPlugin.val($item.find("input[name=plugin]").val());
                    }
                }

                if (window.elementPlus?.autocomplete) {
                    autocomplete($toolIcon[0], "icon");
                }

                // handle plugin select on edit dialogue
                $toolType.on("change", function () {
                    $toolSyntax.parents("div").first().hide();
                    $toolPlugin.parents("div").first().hide();

                    if ($toolType.val() === "Wikiplugin") {
                        $toolPlugin.parents("div").first().show();
                    } else {
                        if (["Inline", "Block", "LineBased"].includes($toolType.val())) {
                            $toolSyntax.parents("div").first().show();
                        }
                    }
                }).trigger("change");

                $(".btn.save").on("click", function () {
                    $("#save_tool", $this).val("Save");
                    $("form", $this).trigger("submit");
                    $(this).modal("hide");
                });

                $(".btn.delete").on("click", function () {
                    if (confirm(tr("Are you sure you want to delete this custom tool?"))) {
                        $("#delete_tool", $this).val("Delete");
                        $("form", $this).trigger("submit");
                    }
                    $(this).modal("hide");
                });

            })
            .find(".modal-content")
            .html($("#toolbar_edit_div").html())
        ;

    };

    var checkLength = function (o, n, min, max) {
        if (o.val().length > max || o.val().length < min) {
            o.addClass('ui-state-error');
            o.prev("label").find(".dialog_tips").text(" Length must be between " + min + " and " + max).addClass('ui-state-highlight');
            setTimeout(function () {
                o.prev("label").find(".dialog_tips").removeClass('ui-state-highlight', 1500);
            }, 500);
            return false;
        } else {
            return true;
        }
    };

    // view mode filter (still doc.ready)

    var $viewMode = $('#view_mode');
    if ($("#section").val() === "sheet") {
        $viewMode.val("sheet");
    }

    $viewMode.on("change", function setViewMode() {
        if ($viewMode.val() === 'both') {
            $('.qt-wyswik').addClass("d-none").removeClass("d-flex");
            $('.qt-wiki').removeClass("d-none").addClass("d-flex");
            $('.qt-wys').removeClass("d-none").addClass("d-flex");
            $('.qt-sheet').addClass("d-none").removeClass("d-flex");
        } else if ($viewMode.val() === 'wiki') {
            $('.qt-wyswik').addClass("d-none").addClass("d-flex");
            $('.qt-wys').addClass("d-none").removeClass("d-flex");
            $('.qt-wiki').removeClass("d-none").addClass("d-flex");
            $('.qt-sheet').addClass("d-none").removeClass("d-flex");
        } else if ($viewMode.val() === 'wysiwyg') {
            $('.qt-wyswik').addClass("d-none").removeClass("d-flex");
            $('.qt-wiki').addClass("d-none").removeClass("d-flex");
            $('.qt-wys').removeClass("d-none").addClass("d-flex");
            $('.qt-sheet').addClass("d-none").removeClass("d-flex");
        } else if ($viewMode.val() === 'wysiwyg_wiki') {
            $('.qt-wiki').addClass("d-none").removeClass("d-flex");
            $('.qt-wys').addClass("d-none").removeClass("d-flex");
            $('.qt-sheet').addClass("d-none").removeClass("d-flex");
            $('.qt-wyswik').removeClass("d-none").addClass("d-flex");
            $('.qt--').removeClass("d-none").addClass("d-flex");
        } else if ($viewMode.val() === 'sheet') {
            $('.qt-wyswik').addClass("d-none").removeClass("d-flex");
            $('.qt-wys').addClass("d-none").removeClass("d-flex");
            $('.qt-wiki').removeClass("d-none").addClass("d-flex");
            $('.qt-sheet').removeClass("d-none").addClass("d-flex");
        }
    }).trigger("change");

    $('#toolbar_add_custom').on("click", function () {
        showToolEditForm();
        return false;
    });

});    // end doc ready

// save toolbars
function saveRows() {
    var ser, text;
    ser = $('.toolbars-admin ul.flex-row').map(function (){    /* do this on everything of class 'row' inside toolbars-admin div */
        return $(this).children().map(function (){    /* do this on each child node */
            text = "";
            if ($(this).hasClass('qt-plugin')) { text += 'wikiplugin_'; }
            text += $(this).text().trim();
            return text;
        }).get().join(",").replace(",|", "|").replace("|,", "|");            /* put commas inbetween */
    });
    if (typeof(ser) === 'object' && ser.length > 1) {
        ser = $.makeArray(ser).join('/');            // row separators
    } else {
        ser = ser[0];
    }
    $('#qt-form-field').val(ser.replace(',,', ','));
}



