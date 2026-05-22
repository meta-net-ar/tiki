// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

import Sortable from "sortablejs";

$(function () {
    let tocDirty = false;

    // Get page_ref_id from URL parameters
    const getPageRefId = function () {
        const params = new URLSearchParams(window.location.search);
        return params.get("page_ref_id");
    };

    // Restore collapsed/expanded state from localStorage
    const restoreStructureState = function () {
        const pageRefId = getPageRefId();
        if (pageRefId) {
            const storageKey = "tiki_structure_state_" + pageRefId;
            const savedState = localStorage.getItem(storageKey);
            // First, hide all sub-levels by default (except root level)
            $(".admintoclevel").each(function () {
                const $node = $(this);
                const $children = $node.find("ol.admintoc").first().parent();
                // Only hide if it's not the root level (check if it has a parent admintoclevel)
                if ($node.parents(".admintoclevel").length > 0 && $children.length > 0) {
                    $children.hide();
                    $node.find(".flip-children .icon").setIcon("caret-right");
                }
            });

            // Then restore saved expanded states
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    Object.keys(state).forEach(function (nodeId) {
                        const $node = $("#" + nodeId);
                        if ($node.length && state[nodeId] === "expanded") {
                            const $children = $node.find("ol.admintoc").first().parent();
                            $children.show();
                            $node.find(".flip-children .icon").setIcon("caret-down");
                        }
                    });
                } catch (e) {
                    console.error("Error restoring structure state:", e); // eslint-disable-line no-console
                }
            }
        } else {
            // If no storage key, hide all sub-levels by default
            $(".admintoclevel").each(function () {
                const $node = $(this);
                const $children = $node.find("ol.admintoc").first().parent();
                if ($node.parents(".admintoclevel").length > 0 && $children.length > 0) {
                    $children.hide();
                    $node.find(".flip-children .icon").setIcon("caret-right");
                }
            });
        }
    };

    // Save collapsed/expanded state to localStorage
    const saveStructureState = function () {
        const pageRefId = getPageRefId();
        if (pageRefId) {
            const storageKey = "tiki_structure_state_" + pageRefId;
            const state = {};
            $(".admintoclevel").each(function () {
                const $node = $(this);
                const nodeId = $node.attr("id");
                if (nodeId) {
                    const $children = $node.find("ol.admintoc").first().parent();
                    state[nodeId] = $children.is(":visible") ? "expanded" : "collapsed";
                }
            });
            try {
                localStorage.setItem(storageKey, JSON.stringify(state));
            } catch (e) {
                console.error("Error saving structure state:", e); // eslint-disable-line no-console
            }
        }
    };

    const setupStructure = function () {
        const sortableOptions = {
            group: {
                name: "shared",
            },
            dataIdAttr: "data-id",
            ghostClass: "draggable-background",
            animation: 150,
            // invertSwap: true,
            swapThreshold: 0.65,
            direction: "vertical",
            forceFallback: true,
            fallbackOnBody: true,
            // Called when dragging element changes position
            onAdd: function (event) {
                const pageName = $(event.item).data("page-name");
                if (!jqueryTiki.structurePageRepeat && $(`.structure-container li .link:contains(${pageName})`).length > 0) {
                    $.getJSON($.service("object", "report_error", { message: tr("Page only allowed once in a structure") }));
                    $(event.item).remove();
                }
            },
            onEnd: function (event) {
                if ($(".save_structure:visible").length === 0) {
                    $(".save_structure").show("fast").parent().show("fast");
                    tocDirty = true;
                }
            },
        };

        document.querySelectorAll(".admintoc").forEach(function (el) {
            new Sortable(el, sortableOptions);
        });

        $(".flip-children", ".admintoc").on("click", function (event) {
            const $this = $(this),
                $children = $this
                    .parents("li.admintoclevel")
                    .first()
                    .find("ol.admintoc")
                    .filter(function (index) {
                        return event.altKey || index === 0;
                    })
                    .parent();

            if ($children.is(":visible")) {
                $this.find(".icon").setIcon("caret-right");
                if (event.altKey) {
                    $children.find(".icon-caret-down").setIcon("caret-right");
                }
                $children.hide("fast");
                saveStructureState();
            } else {
                $this.find(".icon").setIcon("caret-down");
                if (event.altKey) {
                    $children.find(".icon-caret-right").setIcon("caret-down");
                }
                $children.show("fast");
                saveStructureState();
            }
        });

        $(".page-alias-input")
            .on("change", function () {
                $(".save_structure").show("fast").parent().show("fast");
                tocDirty = true;
            })
            .on("click", function () {
                // for Firefox
                $(this).trigger("focus").selection($(this).val().length);
            });

        const sortableListOptions = {
            group: {
                name: "shared",
                pull: "clone",
                put: false, // Do not allow items to be put into this list
            },
            sort: false,
            animation: 500,
            onEnd: function (event) {
                const pageName = $(event.item).data("page-name");

                if ($(event.to).closest(".structure-container").length > 0) {
                    $(event.item).text("");
                    $(event.item).removeClass("ui-state-default").addClass("row admintoclevel new").append(`
                        <div class="col-sm-12">
                            <label>${pageName}</label>
                            <div class="actions input-group input-group-sm mb-2"><input type="text" class="page-alias-input form-control" value="" placeholder="Page alias..."></div>
                        </div>
                        <div class="col-sm-12">
                            <ol class="admintoc"></ol>
                        </div>
                    `);
                    new Sortable($(event.item).find(".admintoc")[0], sortableOptions);

                    $(".save_structure").show("fast").parent().show("fast");
                    tocDirty = true;
                }
            },
        };
        new Sortable(document.querySelector("#page_list_container"), sortableListOptions);
    };

    $(window).on("beforeunload", function () {
        if (tocDirty) {
            return tr("You have unsaved changes to your structure, are you sure you want to leave the page without saving?");
        }
    });

    setupStructure();
    restoreStructureState();

    $(".save_structure").on("click", function () {
        const $sortable = $(this).parent().find(".admintoc").first();
        $sortable.tikiModal(tr("Saving..."));

        let fakeId = 1000000;
        $(".admintoclevel.new").each(function () {
            $(this).attr("id", "node_" + fakeId);
            $(this).data("id", fakeId);
            fakeId++;
        });
        // Adjusted to previous sortable result array
        const arr = [
            {
                item_id: "root",
                parent_id: "none",
                structure_id: $sortable.data("params").page_ref_id,
                depth: 0,
            },
        ];

        $sortable.find("li.admintoclevel").each(function () {
            const parentId = $(this).parent().closest("li.admintoclevel").data("id");
            const itemId = $(this).data("id");
            const pageAlias = $(this).find(".page-alias-input").val();
            const structureId = $sortable.data("params").page_ref_id;
            const pageName = $(this).find("> div").text().trim();
            const obj = {
                item_id: itemId,
                parent_id: parentId || "root",
                structure_id: structureId,
                page_name: pageName.split("\n")[0],
                page_alias: pageAlias,
                depth: 1,
                // el: $(this)[0] // Debug only
            };

            let item = arr.find((el) => el.parent_id === parentId);
            if (!parentId) {
                obj.depth = 1;
            } else if (item) {
                obj.depth = item.depth;
            } else {
                obj.depth = arr[arr.length - 1].depth + 1;
            }

            arr.push(obj);
        });

        $.post(
            $.service("wiki_structure", "save_structure"),
            { data: JSON.stringify(arr), params: JSON.stringify($sortable.data("params")) },
            function (data) {
                $sortable.tikiModal();
                if (data) {
                    $sortable.replaceWith(data.html);
                    setupStructure();
                    restoreStructureState();
                    $(".save_structure").hide();
                    tocDirty = false;
                }
            },
            "json"
        );
        return false;
    });

    $(".add_new_child_page").on("click", function () {
        let id = $(this).parents(".admintoclevel").first().attr("id").match(/\d*$/);
        if (id) {
            id = id[0];
        }
        $("input[name=page_ref_id]", "#newpage_dialog").val(id);

        $.openModal({
            title: tr("Add page"),
            content: $("#newpage_dialog").html(),
        });
        return false;
    });

    $(".move_page").on("click", function () {
        let id = $(this).parents(".admintoclevel").first().attr("id").match(/\d*$/);
        if (id) {
            id = id[0];
        }
        $("input[name=page_ref_id]", "#move_dialog").val(id);
        $.openModal({
            title: tr("Move page"),
            content: $("#move_dialog").html(),
        });
        return false;
    });
});
