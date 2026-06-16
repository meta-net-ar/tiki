// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$(function() {    // wrapping

    if ($("body.tiki-admin_modules").length) {
        // drag & drop ones first
        const dragZonesSelector = ".modules";
        $(dragZonesSelector).addClass("ui-droppable");

        let modAdminDirty = false;

        $(function() {
            $(window).on("beforeunload", function() {
                if (modAdminDirty) {
                    return tr("You have unsaved changes to your modules, are you sure you want to leave the page without saving?");
                }
            });
        });

        $('.modules').each(function() {
            Sortable.create(this, {
                group: 'modules',
                animation: 150,
                ghostClass: 'module-placeholder',
                onEnd: function(event) {
                    if ($("#save_modules:visible").length === 0) {
                        $("#save_modules").show("fast").attr("sortable", $(event.from).attr("id"))
                            .parent().show("fast");
                        modAdminDirty = true;
                    }
                },
                onAdd: function(event) {
                    const dropped = $("> li", event.to);
                    if (dropped.length) {
                        const zone = $(event.to);    //dropped.parents(".modules").first();    // odd? more than one?
                        if (zone && zone.attr("id") && zone.attr("id").match(/modules/)) {
                            const ord = $.inArray(dropped[0], zone.children());
                            const zoneStr = zone.attr("id").substring(0, zone.attr("id").indexOf("_"));
                            const name = $("input", dropped).first().val().trim();
                            const options = {
                                modName: name,
                                modPos: zoneStr,
                                modOrd: ord,
                                dropped: dropped
                            };
                            if (zoneStr.indexOf("top") > -1 || zoneStr.indexOf("bottom") > -1 || zone.parent().parent().hasClass("box-zone")) {
                                options.nobox = true;
                            }
                            dropped.addClass("module-placeholder");
                            window.showModuleEditForm(false, options);
                        }
                    }
                }
            });
        });

        // disable all links in modules apart from app menu
        $(".module:not(.box-Application_Menu, .box-quickadmin)").find("a, input").on("click", function (event) {
            if (!$(this).parent().hasClass("moduleflip")) {
                event.stopImmediatePropagation();
                return false;
            } else {
                return true;
            }
        });

       // set dbl click form action and hover text
        $(".module:not(.box-zone), #assigned_modules tr").on("mouseenter", function() {
            $(this).attr('title', tr("Double click to edit"));
        }).on("mouseleave", function() {
            $(this).removeAttr('title');
        }).on("dblclick", function () {
            window.showModuleEditForm(this);
        });

        // source list of all modules
        Sortable.create(document.getElementById("module_list"), {
            group: { name: "modules", put: false, pull: "clone" },
            sort: false,
            animation: 150,
        });

        $("#save_modules").on("click", function(evt) {
            if ($(this).attr("sortable")) {
                // save module order
                modAdminDirty = false;
                let ser = {};
                $(".modules").each(function() { /* do this on everything of class "modules" */
                    let $modules = $(this).find("div.module");
                    if ($modules.length === 0) {
                        // feature_layoutshadows adds an extra div here
                        $modules = $(this).find("> div.box-shadow > div.module");
                    }
                    ser[$(this).attr("id")] = $modules.map(function() { /* do this on each child module */
                        return $(this).attr("id").match(/\d+$/)[0];    // dare to do it in one go
                    }).get();
                });
                $("#module-order").val(JSON.stringify(ser)).parents("form")[0].submit();
            } else if ($(this).attr("dragged")) {
                $("#" + $(this).attr("dragged")).trigger("dblclick");
                $(this).attr("dragged", "");
            }
            return false;
        }).hide();

        // module select action when in main page
        $("#assign_name", "#tiki-center").on("change", function () {
            needToConfirm=false;
            //this.form.trigger("submit");
            $("input[name=preview]", this.form).trigger("click");
        });

    }

// show edit form dialogue
window.showModuleEditForm = function(item, options) {
    var modId = 0, modName, modPos = "", modOrd = 0, modStyle = "", dropped = null;
    if (item) {
        if ($(item).is("tr")) {        // assigned_modules row dblclicked
            modName = $("td", item).first().text();
            modId = $("a", item).last().data("bs-content").match(/modup" value="(\d+)/);
            if (modId) {
                modId = modId[1];
                modOrd = $("td", item).eq(1).text();
                modPos = $(item).parents("table").first().attr("id").match(/_([^_]*)$/);
                if (modPos) {
                    modPos = modPos[1];
                }
            }
        } else {                    // .module div dblclicked
            modName = $(item).attr("class").match(/box-[\S_-]+/);
            if (modName) {
                modName = modName[0].substring(4);
            }
            modId = $(item).attr("id").match(/\d+$/);
            if (modId) {
                modId = modId[0];
                var id = $("div", item).first().attr("id");
                if (id) {
                    modPos = id.match(/(top|topbar|pagetop|left|right|pagebottom|bottom)(\d+)$/);
                    if (modPos) {
                        modOrd = modPos[2];
                        modPos = modPos[1];
                    }
                }
                modStyle = $(item).attr("style");
                if (modStyle && !modStyle.match(/absolute/)) {
                    modStyle = "";    // use style from object if draggable
                }
            }
        }
    } else { // new module assignment
        modName = options.modName;
        modPos = typeof options.modPos !== "undefined" ? options.modPos : options.formVals.assign_position;
        modOrd = typeof options.modOrd !== "undefined" ? options.modOrd : options.formVals.assign_order;
        dropped = options.dropped;
        if (typeof options.modId !== "undefined") {
            modId = options.modId;
        }
    }

    let postData = {
        edit_module: true,
        assign_name: modName,
        moduleId: modId,
        assign_position: modPos,
        assign_order: modOrd
    };
    if (item) {
        postData.edit_assign = modId;
    } else {
        if (typeof options.formVals !== "undefined") {
            var v = options.formVals;
            $.extend(v, postData);
            postData = v;
        }
        postData.preview = true;
    }

    $.post("tiki-admin_modules.php", postData, function(data) {
        const form = $("<form action='tiki-admin_modules.php' method='post' class='no-ajax'></form>");
        form.html(data);
        form.append($("<input type='hidden' name='assign' value='popup' />" +
            "<input type='hidden' name='moduleId' value='"+modId+"' />"));
            $.openModal({
                title: tr("Edit module:") + " " + tiki_decodeURIComponent(modName).replace("+"," "),
                content: form,
                open: function() {
                    $(".submit-container", this).remove();
                    if (options && options.nobox) {
                        $('input[name*=nobox]').val("y");
                    }
                },
                buttons: [
                    {
                        text: tr("Remove"),
                        type: "outline-danger",
                        onClick: function() {
                            const editform = $("form", this);
                            // remove all fields except ticket
                            editform.find('input:not("[name=ticket]")').remove();
                            editform.find('select').remove();

                            editform.append(
                                $("<input type='hidden' name='unassign_module_id' value='"+modId+"' />" +
                                "<input type='hidden' name='confirmForm' value='y' />")
                            );
                            editform.trigger("submit");
                        }
                    },
                    {
                        text: tr("Save"),
                        type: "primary",
                        onClick: function() {
                            modAdminDirty = false;
                            $(this).find('input[type=text]').removeClass('ui-state-error');
                            $("form", this).trigger("submit");
                        }
                    }
                ]
            });

            if (window.elementPlus?.autocomplete) {
                autocomplete($('.pagename')[0], "pagename");
            }
            if (modStyle) {
                // preload style field with style if position:absolute (unnecessary spaces removed)
                $('input[name*=style]').val(modStyle.replace(/:\s*/g, ":").replace(/;\s*/g, ";"));
            }
            $("#assign_name", "#module_edit_div").on("change", function () {
                const formVals = {};
                $(this).parents("form").find("input[name!=assign], select, textarea").each( function () {
                    formVals[$(this).attr("name")] = $(this).val();
                });
                window.showModuleEditForm (null, {
                    modName: $(this).val(),
                    modPos: modPos,
                    modOrd: modOrd,
                    dropped: dropped,
                    modId: modId,
                    formVals: formVals
                });
            });
            ajaxLoadingHide();
        },
    "html");

     ajaxLoadingShow('module_edit_div');
};
});    // close closure
