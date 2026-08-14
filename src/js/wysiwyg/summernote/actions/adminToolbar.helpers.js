import Sortable from "sortablejs";

export function bindToolbarData(toolSet, data, toolbar) {
    toolSet.forEach((set) => {
        const group = toolbar.find(`.note-btn-group.note-${set[0]}`);
        const buttons = group.children("button").add(group.children(".note-btn-group").children("button"));
        set[1].forEach((tool, index) => {
            const active = data.find((activeTool) => activeTool.token === tool);
            const button = buttons.eq(index);
            button.attr("data-token", tool);
            button.attr("data-name", active.name);
            button.attr("aria-label", active.label);
        });
    });
}

export function handleFilterPlugins(container) {
    const input = container.find("el-input");
    input.on("input", function () {
        const value = $(this).val().toLowerCase();
        container.find("button").each(function () {
            const name = $(this).data("name");
            if (
                name.toLowerCase().includes(value) ||
                $(this).data("token").toLowerCase().includes(value) ||
                $(this).text().toLowerCase().includes(value)
            ) {
                $(this).parent().show();
            } else {
                $(this).parent().hide();
            }
        });
    });
}

export function initializeSortable(container) {
    [container.find("#tools")[0], container.find("#plugins")[0]].forEach((el) => {
        new Sortable(el, {
            sort: false,
            group: {
                name: "toolbar",
                pull: true,
                put: (_, fromSortable) => {
                    return !["plugins", "tools"].includes(fromSortable.el.id);
                },
            },
            onAdd: function (e) {
                const item = $(e.item);

                if (item.data("name").startsWith("wikiplugin_") && e.to.id !== "plugins") {
                    item.remove();
                    container.find("#plugins").prepend(item);
                }
                if (!item.data("name").startsWith("wikiplugin_") && e.to.id !== "tools") {
                    item.remove();
                    container.find("#tools").prepend(item);
                }

                item.attr("class", "btn btn-outline-secondary btn-sm rounded-3");
                item.wrap('<div class="d-flex align-items-center gap-2"></div>');
                item.after('<span class="label">' + item.attr("aria-label") + "</span>");
            },
        });
    });

    // Allow new groups to be formed
    $(".note-toolbar", container).append('<div class="note-btn-group btn-group"></div>');

    $(".note-toolbar", container).find(".note-btn-group").each(initBtnGroupSortableCb);

    // allow groups to be reorded
    new Sortable($(".note-toolbar", container)[0], {
        sort: true,
        animation: 150,
    });
}

function initBtnGroupSortableCb() {
    new Sortable(this, {
        sort: true,
        group: "toolbar",
        onAdd: function (e) {
            const item = $(e.item);
            if (!item.is("button")) {
                const button = item.find("button");
                button.attr("aria-label", item.find(".label").text());
                button.attr("class", "note-btn btn btn-outline-secondary btn-sm");
                item.find(".label").remove();
                item.children().unwrap();
            }

            // if no more empty groups, add a new one
            const emptyGroups = $(".note-toolbar .note-btn-group:empty");
            if (emptyGroups.length === 0) {
                const newGroup = $("<div class='note-btn-group btn-group'></div>");
                $(".note-toolbar").append(newGroup);
                initBtnGroupSortableCb.call(newGroup[0]);
            }
        },
    });
}
