// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* Include for tiki-admin_toolbars.php
 *
 * Selector vars set up in tiki-admin_toolbars.php:
 *
 */


$(document).ready(function () {

    $(".flex-row", ".rows").sortable({
        connectWith: 'ul.full, .flex-row',
        forcePlaceholderSize: true,
        forceHelperSize: true,
        placeholder: 'toolbars-placeholder',
        stop: function (event, ui) {
        },
        start: function (event, ui) {
        },
        receive: function(event, ui) {
            $(ui.item).addClass("navbar-text");
        }
    }).disableSelection();

    $("ul.full").sortable({
        connectWith: '.flex-row, #full-list-c',
        forcePlaceholderSize: true,
        forceHelperSize: true,
        placeholder: 'toolbars-placeholder',
        remove: function (event, ui) {    // special handling for separator to allow duplicates
            if ($(ui.item).text() === '-' || $(ui.item).text() === '|') {
                $(this).prepend($(ui.item).clone());    // leave a copy at the top of the full list
            }
        },
        receive: function (event, ui) {
            const $item = $(ui.item).css('float', '');

            if ($item.text() === '-') {
                $(this).children().remove('.qt--');                // remove all seps
                $(this).prepend($item.clone());            // put one back at the top

            } else if ($(this).attr('id') === 'full-list-c') {    // dropped in custom list
                $item.dblclick(function () { showToolEditForm(ui.item); });
                $item.trigger('dblclick');
            }
            sortList(this);
        },
        stop: function (event, ui) {
            sortList(this);
        }
    }).disableSelection();

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
            $('.qt-custom').dblclick(function () { showToolEditForm(this); });
        }
    };
    $('.qt-custom').dblclick(function () { showToolEditForm(this); });

    // show edit form dialogue
    var showToolEditForm = function (item) {

        $(".footer-modal:not(.show):first")
            .modal("show")
            .on("shown.bs.modal", function () {
                const $this = $(this);
                $this.find("select").removeClass("noselect2").applySelect2();

                const $toolType = $("#tool_type", $this);
                const $toolPlugin = $("#tool_plugin", $this);
                const $toolName = $("#tool_name", $this);
                const $toolLabel = $("#tool_label", $this);
                const $toolIcon = $("#tool_icon", $this);
                const $toolToken = $("#tool_token", $this);
                const $toolSyntax = $("#tool_syntax", $this);

                $toolIcon.tiki("autocomplete", "icon");
                $toolToken.tiki("autocomplete", "other", {
                    source:
                        function ( request, response) {
                            let commands = [];
                            for (let commandsKey in CKEDITOR.instances.cked.commands) {
                                const search = request.term.toLowerCase();
                                if (CKEDITOR.instances.cked.commands.hasOwnProperty(commandsKey) && commandsKey.toLowerCase().indexOf(search) > -1) {
                                    commands.push(commandsKey);
                                }
                            }
                            response(commands);
                        }
                });

                if (item) {
                    const $item = $(item);
                    $toolName.val($.trim($item.text()));
                    $toolLabel.val($.trim($item.find("input[name=label]").val()));
                    if ($item.children("img").length && $item.children("img").attr("src") !== "img/icons/shading.png") {
                        $toolIcon.val($item.children("img").attr("src"));
                    } else {
                        const iconname = $("span.icon", item).attr("class").match(/icon-(\w*)/);
                        if (iconname) {
                            $toolIcon.val(iconname[1]);
                        }
                    }
                    $toolToken.val($item.find("input[name=token]").val());
                    // TODO use CKEDITOR.instances.editwiki.commands as the autocomplete on this field
                    $toolSyntax.val($item.find("input[name=syntax]").val());
                    $toolType.val($item.find("input[name=type]").val());
                    if ($item.find("input[name=type]").val() === "Wikiplugin") {
                        $toolPlugin.val($item.find("input[name=plugin]").val());
                    }
                }

                // handle plugin select on edit dialogue
                $toolType.change( function () {
                    $toolSyntax.parents("div:first").hide();
                    $toolPlugin.parents("div:first").hide();

                    if ($toolType.val() === "Wikiplugin") {
                        $toolPlugin.parents("div:first").show();
                    } else {
                        if (["Inline", "Block", "LineBased"].includes($toolType.val())) {
                            $toolSyntax.parents("div:first").show();
                        }
                    }
                    $toolPlugin.trigger("change.select2");
                }).change();

                $(".btn.save").click(function () {
                    $("#save_tool", $this).val("Save");
                    $("form", $this).submit();
                    $(this).modal("hide");
                });

                $(".btn.delete").click(function () {
                    if (confirm(tr("Are you sure you want to delete this custom tool?"))) {
                        $("#delete_tool", $this).val("Delete");
                        $("form", $this).submit();
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

    $viewMode.change(function setViewMode() {
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
    }).change();

    $('#toolbar_add_custom').click(function () {
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
            text += $.trim($(this).text());
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



