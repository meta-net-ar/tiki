// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

import Sortable from "sortablejs";

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

        // $options.nestedSortable({

        //     disableNesting: 'no-nest',
        //     forcePlaceholderSize: true,
        //     handle: 'div',
        //     helper: 'clone',
        //     items: 'li',
        //     maxLevels: 4,
        //     opacity: .6,
        //     tabSize: 20,
        //     tolerance: 'pointer',
        //     toleranceElement: '> div',
        //     placeholder: "ui-state-highlight",
        //     rootID: "root",
        //     // connectWith:"#page_list_container",

        //     stop: function (event, ui) {
        //         setDirty();
        //         $(this).removeClass("ui-state-active");
        //     },
        //     start: function (event, ui) {
        //         $(this).addClass("ui-state-active");
        //     }
        // })
        //     .droppable({
        //         classes: {
        //             "ui-droppable-hover": "ui-state-active"
        //         },
        //         drop: function (event, ui) {
        //             // new option dropped?
        //             var $dropped = $(ui.draggable).clone();

        //             if ($dropped.hasClass("new")) {

        //                 $dropped.find(".hidden").removeClass("hidden");
        //                 $dropped.find(".field-label")
        //                     .prop("readonly", false)
        //                     .attr("placeholder", tr("Label"));

        //                 $(ui.helper).hide();
        //                 var element = null,
        //                     $target = [],
        //                     y = event.clientY;

        //                 do {
        //                     element = document.elementFromPoint(event.clientX, y);
        //                     $target = $(element);
        //                     if (! $target.is("li")) {
        //                         $target = $target.parents("li").first();
        //                     }
        //                     y -= 10;

        //                 } while (y > $options.offset().top && ! element);

        //                 $dropped
        //                     .removeClass("new")
        //                     .addClass("option-added")
        //                     .show()
        //                     .attr("id", "node_new_" + $(".option-added", $options).length);

        //                 if ($target.length) {
        //                     $target.after($dropped);
        //                 } else {
        //                     $options.prepend($dropped);    // add new one to the top if all else fails
        //                 }

        //                 $dropped.find(".icon-edit").parent()
        //                     .prop("disabled", true)
        //                     .attr("title", "|" + tr("Save all options to enable extended properties editing."))
        //                     .addClass("tips")
        //                     .css("opacity", 0.5)
        //                     .parent()
        //                     .tiki_popover();

        //                 $dropped.find(".field-label").trigger("focus");
        //                 setDirty();
        //             }
        //         }
        //     })
        //     .disableSelection();

        new Sortable(document.getElementsByClassName("new-option")[0], {
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

        // $("#node_new").draggable({
        //     connectToSortable:"#options",
        //     revert:"invalid",
        //     helper:"clone",
        //     start:function (event, ui) {
        //         $(ui.helper)
        //             .css({
        //                 zIndex: 10000,
        //                 width: "800px"
        //             })
        //             .find(".hidden").removeClass("hidden")
        //         ;

        //         $options.mouseover(function () {
        //             $options.addClass("over");
        //         }).mouseout(function () {
        //             $options.removeClass("over");
        //         });
        //     },
        //     stop:function (event, ui) {
        //         $(ui.helper).css("z-index", "auto");

        //         $options.off("mouseover").off("mouseout");
        //     }
        // }).disableSelection();

        $options.on("click", ".option-remove", function () {
            if (confirm(tr("Are you sure you want to remove this option?"))) {
                $(this).parents("li").first().remove();
                setDirty();
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
            })
            .trigger("change.select2");

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
                data: $.toJSON(dataArr),
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
