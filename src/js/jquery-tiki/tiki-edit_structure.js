// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

import Sortable from "sortablejs";

$(function () {
    let newNodeSequence = 1;
    let tocDirty = false;
    // Admin nodes use the single class "admintoclevel"; newly dragged nodes use "toclevel".
    const structureNodeSelector = ".structure-container li.admintoclevel, .structure-container li.toclevel";
    const structureNodeChildSelector = "li.admintoclevel, li.toclevel";

    const isStructureNode = function ($node) {
        return $node.hasClass("admintoclevel") || ($node.hasClass("toclevel") && !$node.hasClass("list-group-item"));
    };

    const markDirty = function () {
        tocDirty = true;
        if ($(".save_structure:visible").length === 0) {
            $(".save_structure").show("fast").parent().show("fast");
        }
    };

    const markClean = function () {
        tocDirty = false;
        $(".save_structure").hide();
        $(".save-structure-wrapper").hide();
    };

    const normalizePageName = function (name) {
        return String(name || "").trim();
    };

    const structurePageNameSet = new Set((jqueryTiki.structurePageNames || []).map(normalizePageName).filter(Boolean));

    const registerStructurePageName = function (pageName) {
        const normalized = normalizePageName(pageName);
        if (normalized) {
            structurePageNameSet.add(normalized);
        }
    };

    const syncStructurePageNamesFromDom = function () {
        $(structureNodeSelector).each(function () {
            registerStructurePageName(getNodePageName($(this)));
        });
    };

    const getPageNameFromElement = function ($el) {
        const dataName = $el.attr("data-page-name");
        if (dataName) {
            return normalizePageName(dataName);
        }
        if ($el.hasClass("list-group-item")) {
            return normalizePageName($el.text());
        }
        const $link = $el.children("div.col-sm-12").first().find("label a.link").first();
        if ($link.length) {
            return normalizePageName($link.attr("title") || $link.text());
        }
        return "";
    };

    const getNodePageName = function ($node) {
        return getPageNameFromElement($node);
    };

    const getNodeId = function ($node) {
        const id = $node.attr("data-id");
        if (id === undefined || id === "") {
            return undefined;
        }
        return id;
    };

    const getChildOl = function ($node) {
        return $node.children("div.col-sm-12").last().children("ol.admintoc").first();
    };

    const getChildOlContainer = function ($node) {
        const $childOl = getChildOl($node);
        return $childOl.parent();
    };

    const isPageAlreadyInStructure = function (pageName) {
        const normalized = normalizePageName(pageName);
        if (!normalized || jqueryTiki.structurePageRepeat) {
            return false;
        }
        return structurePageNameSet.has(normalized);
    };

    const createNewStructureNode = function (item, pageName) {
        const nodeId = "new_" + newNodeSequence++;
        $(item)
            .attr("id", "node_" + nodeId)
            .attr("data-id", nodeId)
            .attr("data-page-name", pageName)
            .data("id", nodeId)
            .data("pageName", pageName)
            .text("")
            .removeClass("ui-state-default list-group-item")
            .addClass("row admintoclevel new").append(`
                <div class="col-sm-12">
                    <label>
                        <a href="#" class="link" title="${pageName}">${pageName}</a>
                    </label>
                    <div class="actions input-group input-group-sm mb-2">
                        <span class="input-group-text"><span class="icon icon-sort fa-fw"></span></span>
                        <input type="text" class="page-alias-input form-control" value="" placeholder="Page alias...">
                    </div>
                </div>
                <div class="col-sm-12">
                    <ol class="admintoc"></ol>
                </div>
            `);
        registerStructurePageName(pageName);
    };

    const convertPendingListItems = function () {
        $(".structure-container li.list-group-item").each(function () {
            const $item = $(this);
            const pageName = getPageNameFromElement($item);
            if (!pageName) {
                return;
            }
            if (isPageAlreadyInStructure(pageName)) {
                $item.remove();
                return;
            }
            createNewStructureNode(this, pageName);
        });
    };

    // Get page_ref_id from URL parameters
    const getPageRefId = function () {
        const params = new URLSearchParams(window.location.search);
        return params.get("page_ref_id");
    };

    // Restore collapsed/expanded state from localStorage
    const restoreStructureState = function () {
        const pageRefId = getPageRefId();
        let savedState = null;
        if (pageRefId) {
            savedState = localStorage.getItem("tiki_structure_state_" + pageRefId);
        }

        $(structureNodeSelector).each(function () {
            const $node = $(this);
            const nodeId = $node.attr("id");
            const $childOl = getChildOl($node);
            const $childContainer = getChildOlContainer($node);
            const hasChildItems = $childOl.children(structureNodeChildSelector).length > 0;

            if ($childContainer.length === 0) {
                return;
            }

            // Empty child lists must stay visible: they are drop targets for nesting.
            if (!hasChildItems) {
                $childContainer.show();
                return;
            }

            // Top-level tree nodes stay expanded.
            if ($node.parents(structureNodeChildSelector).length === 0) {
                $childContainer.show();
                $node.find(".flip-children .icon").setIcon("caret-down");
                return;
            }

            let expanded = false;
            if (savedState) {
                try {
                    const state = JSON.parse(savedState);
                    expanded = !!(nodeId && state[nodeId] === "expanded");
                } catch (e) {
                    console.error("Error restoring structure state:", e); // eslint-disable-line no-console
                }
            }

            if (expanded) {
                $childContainer.show();
                $node.find(".flip-children .icon").setIcon("caret-down");
            } else {
                $childContainer.hide();
                $node.find(".flip-children .icon").setIcon("caret-right");
            }
        });
    };

    const revealDropTargets = function () {
        $(structureNodeSelector).each(function () {
            const $childContainer = getChildOlContainer($(this));
            if ($childContainer.length) {
                $childContainer.show();
            }
        });
    };

    const destroyStructureSortables = function () {
        document.querySelectorAll(".structure-container .admintoc").forEach(function (el) {
            const instance = Sortable.get(el);
            if (instance) {
                instance.destroy();
            }
        });
        const pageList = document.querySelector("#page_list_container");
        if (pageList) {
            const listInstance = Sortable.get(pageList);
            if (listInstance) {
                listInstance.destroy();
            }
        }
    };

    // Save collapsed/expanded state to localStorage
    const saveStructureState = function () {
        const pageRefId = getPageRefId();
        if (pageRefId) {
            const storageKey = "tiki_structure_state_" + pageRefId;
            const state = {};
            $(structureNodeSelector).each(function () {
                const $node = $(this);
                const nodeId = $node.attr("id");
                if (nodeId) {
                    const $children = getChildOlContainer($node);
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

    const appendStructureNodes = function ($container, parentId, depth, arr, structureId) {
        $container.children(structureNodeChildSelector).each(function () {
            const $node = $(this);
            const itemId = getNodeId($node);
            const pageName = getNodePageName($node);
            const pageAlias = $node.find(".page-alias-input").val();

            if (itemId === undefined || !pageName) {
                return;
            }

            arr.push({
                item_id: itemId,
                parent_id: parentId || "root",
                structure_id: structureId,
                page_name: pageName,
                page_alias: pageAlias,
                depth: depth,
            });

            const $childOl = getChildOl($node);
            if ($childOl.length) {
                appendStructureNodes($childOl, itemId, depth + 1, arr, structureId);
            }
        });
    };

    const setupStructure = function () {
        destroyStructureSortables();

        const sortableOptions = {
            group: {
                name: "shared",
            },
            dataIdAttr: "data-id",
            ghostClass: "draggable-background",
            chosenClass: "draggable-background",
            animation: 150,
            invertSwap: true,
            swapThreshold: 0.65,
            direction: "vertical",
            scroll: true,
            bubbleScroll: true,
            emptyInsertThreshold: 20,
            handle: ".icon-sort",
            filter: ".flip-children, a, button, input, select, textarea, form, label",
            preventOnFilter: false,
            onChoose: revealDropTargets,
            onAdd: function (event) {
                const $item = $(event.item);
                if (isStructureNode($item)) {
                    markDirty();
                    return;
                }

                const pageName = getPageNameFromElement($item);
                if (!pageName) {
                    $item.remove();
                    return;
                }

                if (isPageAlreadyInStructure(pageName)) {
                    $.getJSON($.service("object", "report_error", { message: tr("Page only allowed once in a structure") }));
                    $item.remove();
                    return;
                }

                createNewStructureNode(event.item, pageName);
                const childOl = getChildOl($item)[0];
                if (childOl) {
                    new Sortable(childOl, sortableOptions);
                }

                markDirty();
            },
            onEnd: function (event) {
                if (event.oldIndex !== event.newIndex || event.from !== event.to) {
                    restoreStructureState();
                    markDirty();
                }
            },
        };

        document.querySelectorAll(".structure-container .admintoc").forEach(function (el) {
            new Sortable(el, sortableOptions);
        });

        $(".flip-children", ".structure-container")
            .off("click")
            .on("click", function (event) {
                event.preventDefault();
                event.stopPropagation();

                const $flip = $(this);
                const $node = $flip.parents(structureNodeChildSelector).first();
                const $children = getChildOlContainer($node);

                if (event.altKey) {
                    if ($children.is(":visible")) {
                        $node.find(".flip-children .icon").setIcon("caret-right");
                        $node.find("ol.admintoc").parent().hide("fast");
                    } else {
                        $node.find(".flip-children .icon").setIcon("caret-down");
                        $node.find("ol.admintoc").parent().show("fast");
                    }
                    saveStructureState();
                    return;
                }

                if ($children.is(":visible")) {
                    $flip.find(".icon").setIcon("caret-right");
                    if (event.altKey) {
                        $children.find(".icon-caret-down").setIcon("caret-right");
                    }
                    $children.hide("fast");
                    saveStructureState();
                } else {
                    $flip.find(".icon").setIcon("caret-down");
                    if (event.altKey) {
                        $children.find(".icon-caret-right").setIcon("caret-down");
                    }
                    $children.show("fast");
                    saveStructureState();
                }
            });

        $(".page-alias-input")
            .on("change", function () {
                markDirty();
            })
            .on("click", function () {
                // for Firefox
                $(this).trigger("focus").selection($(this).val().length);
            });

        const sortableListOptions = {
            group: {
                name: "shared",
                pull: "clone",
                put: false,
            },
            sort: false,
            animation: 500,
        };
        const pageList = document.querySelector("#page_list_container");
        if (pageList) {
            new Sortable(pageList, sortableListOptions);
        }

        syncStructurePageNamesFromDom();
    };

    $(window).on("beforeunload", function () {
        if (tocDirty) {
            return tr("You have unsaved changes to your structure, are you sure you want to leave the page without saving?");
        }
    });

    setupStructure();
    restoreStructureState();

    $(document).on("click", ".save_structure", function (event) {
        event.preventDefault();
        event.stopPropagation();

        const $sortable = $(".structure-container .admintoc").first();
        if ($sortable.length === 0 || !$sortable.data("params")) {
            $.getJSON($.service("object", "report_error", { message: tr("Unable to save structure: missing structure data.") }));
            return false;
        }

        convertPendingListItems();

        $sortable.tikiModal(tr("Saving..."));

        let fakeId = 1000000;
        $(".structure-container li.admintoclevel.new, .structure-container li.toclevel.new").each(function () {
            $(this).attr("id", "node_" + fakeId);
            $(this).attr("data-id", fakeId);
            $(this).data("id", fakeId);
            fakeId++;
        });

        const structureId = $sortable.data("params").page_ref_id;
        const arr = [
            {
                item_id: "root",
                parent_id: "none",
                structure_id: structureId,
                depth: 0,
            },
        ];

        appendStructureNodes($sortable, null, 1, arr, structureId);

        if (arr.length < 2) {
            $sortable.tikiModal();
            $.getJSON($.service("object", "report_error", { message: tr("Nothing to save. Drag a page into the structure first.") }));
            return false;
        }

        $.post(
            $.service("wiki_structure", "save_structure"),
            { data: JSON.stringify(arr), params: JSON.stringify($sortable.data("params")) },
            function (data) {
                $sortable.tikiModal();
                if (data && data.error) {
                    $.getJSON($.service("object", "report_error", { message: data.error }));
                } else if (data && data.html) {
                    const $wrapper = $sortable.closest(".col-sm-12");
                    if ($wrapper.length) {
                        $wrapper.replaceWith(data.html);
                    } else {
                        $sortable.replaceWith(data.html);
                    }
                    setupStructure();
                    restoreStructureState();
                    syncStructurePageNamesFromDom();
                    markClean();
                } else {
                    $.getJSON($.service("object", "report_error", { message: tr("Unable to save structure.") }));
                }
            },
            "json"
        ).fail(function () {
            $sortable.tikiModal();
            $.getJSON($.service("object", "report_error", { message: tr("Unable to save structure.") }));
        });
        return false;
    });

    $(".add_new_child_page").on("click", function () {
        let id = $(this).parents(structureNodeChildSelector).first().attr("id").match(/\d*$/);
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
        let id = $(this).parents(structureNodeChildSelector).first().attr("id").match(/\d*$/);
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
