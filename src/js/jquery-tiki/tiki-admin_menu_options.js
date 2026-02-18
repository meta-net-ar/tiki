// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//
// This JavaScript file is a handler for tiki-admin_menu_options.php
// It contains shared knowledge about the menu options management interface
// and handles the client-side interactions for menu option editing and deletion.

import Sortable from "sortablejs";

var ADMIN_MENU_OPTIONS_SCRIPT = "tiki-admin_menu_options.php";

$(function () {
    var dirty = false,
        $options = $("#options");

    var setupAdminOptions = function () {
        var setDirty = function () {
            if ($(".save_menu.disabled").length) {
                $(".save_menu").removeClass("disabled").prop("disabled", false);
                dirty = true;
            }
        };

        $(".save_menu").addClass("disabled").prop("disabled", true);

        $options.find("li").each(function () {
            var parent = $(this).data("parent");
            if (parent) {
                $("#node_" + parent)
                    .find(".child-options")
                    .first()
                    .append(this);
            }
        });

        var sortableOptions = {
            group: {
                name: "shared",
            },
            dataIdAttr: "data-id",
            dragClass: "dragging-options",
            animation: 150,
            // invertSwap: true,
            // invertedSwapThreshold: 1,
            swapThreshold: 0.65,
            direction: "vertical",
            forceFallback: true,
            fallbackOnBody: true,
            // Called when dragging element changes position
            onEnd: function (event) {
                var parentId = $(event.item).parents("li").first().data("id");
                if (!parentId) parentId = 0;
                $(event.item).data("parent", parentId);

                setDirty();
            },
        };

        var sortable = new Sortable(document.querySelector("#options"), sortableOptions);

        document.querySelectorAll(".child-options").forEach(function (el) {
            new Sortable(el, sortableOptions);
        });

        document.querySelectorAll(".new-option").forEach((element) => {
            new Sortable(element, {
                group: {
                    name: "shared",
                    pull: "clone",
                    put: false, // Do not allow items to be put into this list
                },
                dragClass: "dragging-options",
                animation: 150,
                // invertSwap: true,
                // invertedSwapThreshold: 1,
                swapThreshold: 0.65,
                direction: "vertical",
                forceFallback: true,
                fallbackOnBody: true,
                // Called when dragging element changes position
                onEnd: function (event) {
                    // Sets ids
                    var parentId = $(event.item).parents("li").first().data("id");
                    if (!parentId) parentId = 0;
                    // $(event.item).data('id', 0);
                    $(event.item).data("parent", parentId);

                    // Additional logic
                    var $dropped = $(event.item);
                    $dropped.find(".hidden").removeClass("hidden");
                    $dropped.find(".field-label").prop("readonly", false).attr("placeholder", tr("Label"));

                    $dropped
                        .find(".icon-edit")
                        .parent()
                        .prop("disabled", true)
                        .attr("title", "|" + tr("Save all options to enable extended properties editing."))
                        .addClass("tips")
                        .css("opacity", 0.5)
                        .parent()
                        .tiki_popover();

                    $dropped.find(".field-label").trigger("focus");
                    setDirty();
                },
            });
        });

        $options.on("click", ".option-remove", function () {
            if (confirm(tr("Are you sure you want to remove this option?"))) {
                var $li = $(this).parents("li").first();
                var optionId = $li.data("id");
                if (optionId) {
                    var $form = $("form");
                    var menuId = $form.find("input[name=menuId]").val();
                    var ticket = $form.find("input[name=ticket]").val();

                    // Submit deletion via POST
                    $.post(
                        ADMIN_MENU_OPTIONS_SCRIPT,
                        {
                            deletemenu: optionId,
                            menuId: menuId,
                            ticket: ticket,
                        },
                        function () {
                            dirty = false;
                            location.reload();
                        }
                    ).fail(function (jqXHR, textStatus, errorThrown) {
                        var errorMessage = tr("An error occurred while deleting the menu option.");
                        if (textStatus) {
                            errorMessage += " " + tr("Status:") + " " + textStatus;
                        }
                        if (errorThrown) {
                            errorMessage += " (" + errorThrown + ")";
                        }
                        if (jqXHR && jqXHR.status) {
                            errorMessage += " [" + jqXHR.status;
                            if (jqXHR.statusText) {
                                errorMessage += " " + jqXHR.statusText;
                            }
                            errorMessage += "]";
                        }
                        feedback(errorMessage, "error");
                    });
                }
            }
            return false;
        });

        $options.find("input").on("change", function () {
            setDirty();
        });

        var $previewForm = $("form.preview"),
            $previewDiv = $(".preview-menu");

        $previewForm
            .off("submit")
            .on("submit", function () {
                $previewDiv.tikiModal(" ");
                var $this = $(this);
                $previewDiv.load($this.attr("action"), $this.serialize(), function () {
                    $previewDiv.tikiModal();
                });
                return false;
            })
            .trigger("submit")
            .find("input,select")
            .on("change", function () {
                $previewForm.trigger("submit");
            });

        $("#col1").tikiModal();

        $(".deploy_menu").on("click", function () {
            window.showModuleEditForm(null, {
                modName: "menu",
                modPos: $("#preview_position").val(),
                modOrd: 1,
                dropped: false,
                modId: 0,
                formVals: {
                    assign_params: {
                        id: $("input[name=menuId]").val(),
                        type: $("#preview_type").val(),
                        css: $("#preview_css:checked").length ? "y" : "n",
                        bootstrap: $("#preview_bootstrap:checked").length ? "y" : "n",
                    },
                },
            });

            return false;
        });
    };

    $(window).on("beforeunload", function () {
        if (dirty) {
            return tr("You have unsaved changes to your menu, are you sure you want to continue?");
        }
    });

    $("a.confirm").on("click", function () {
        if (dirty) {
            if (confirm(tr("You have unsaved changes to your menu, are you sure you want to continue?"))) {
                dirty = false;
                return true;
            } else {
                return false;
            }
        }
        return true;
    });

    $(".save_menu").on("click", function () {
        var lisArr = $("#options li").toArray();
        var parentIds = lisArr.map((el) => $(el).data("parent")).filter((val) => val > 0);
        var dataArr = [];
        // Type values:
        // 's' (0 level),
        // 'o' (option without children)
        // or levels of numbers 1, 2...etc (with children)
        var prevSectionLevel = 0;
        var position = 0;

        lisArr.forEach(function (el, index) {
            // items with samepos checked are alternatives for different perms or prefs etc
            // so share the same position as the previous one
            if ($(el).find("input.samepos:checked").first().length === 0) {
                position++;
            }
            var obj = {
                optionId: $(el).data("id"),
                parentId: $(el).data("parent"),
                position: null,
                name: $(el).find("input.field-label").val(),
                url: $(el).find("input.field-url").val(),
                icon: $(el).find("input.field-icon").val(),
                sectionLevel: null,
                type: null,
            };
            var firstOccurrenceObj = getFirstOccurrenceObjByParentId(obj.parentId, dataArr);
            var lastObj = getLastObj(dataArr);

            if (obj.parentId === 0) {
                obj.sectionLevel = 0;
                obj.type = "s";
            } else if (firstOccurrenceObj) {
                obj.sectionLevel = firstOccurrenceObj.sectionLevel;
                obj.type = firstOccurrenceObj.sectionLevel;
            } else {
                obj.sectionLevel = lastObj.sectionLevel + 1;
                obj.type = lastObj.sectionLevel + 1;
            }

            if (!isParent(obj.optionId, parentIds) && obj.parentId !== 0) {
                obj.type = "o";
            }

            if (obj.sectionLevel < prevSectionLevel && obj.type !== "s") {
                var levelsBack = prevSectionLevel - obj.sectionLevel;
                for (var i = 0; i < levelsBack; i++) {
                    dataArr.push({
                        optionId: null,
                        position: position,
                        name: "separator",
                        url: "",
                        type: "-",
                    });
                    position++;
                }
            }
            prevSectionLevel = obj.sectionLevel;

            obj.position = position;
            dataArr.push(obj);
        });

        // console.log(parentIds,dataArr,$("input[name=ticket]").val());

        $.post(
            $.service("menu", "save"),
            {
                data: JSON.stringify(dataArr),
                menuId: $("input[name=menuId]").val(),
                ticket: $("input[name=ticket]").val(),
            },
            function (data) {
                $options.tikiModal();
                dirty = false;
                location.reload();
            },
            "json"
        ).always(function () {
            $options.tikiModal();
        });

        return false;
    });

    function getFirstOccurrenceObjByParentId(id, arr) {
        return arr.find((el) => el.parentId === id);
    }

    function getLastObj(arr) {
        return arr[arr.length - 1];
    }

    function isParent(id, parentIds) {
        return parentIds.findIndex((parentId) => parentId === id) !== -1;
    }

    $("#col1").tikiModal(tr("Loading..."));

    setTimeout(function () {
        setupAdminOptions();
    }, 100);
});
