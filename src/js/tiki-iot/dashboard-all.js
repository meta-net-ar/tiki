import Drawflow from "drawflow";
import GridStack from "gridstack/dist/gridstack-all.js";
import { Gauge, Donut } from "gaugeJS/dist/gauge.min.js";
import interact from "interactjs";

export var drawflowImports = {};
export var drawflowInstances = {};

/**
 * TODO Fix the processor for flow with complex paths & node inter connexions
 * Known limitations: the class is unable to process graph correctly when one node feed it's output to more than one node'input
 * One to one flat connexions are working fine plus connections nested right at the parent node
 * Class can also process graph with multiple route nodes
 * Same applies to the js processor
 * @Question : maybe use editor event to build the graph? or at least a flat object for {child:parentID, child:parentID, etc...}, currently we process the json exported from drawflow to traverse nodes
 */
class DrawflowProcessor {
    constructor(drawflowJson) {
        // TODO L drawflowJson can be empty and we should handle it as a valid case (empty flow)
        if (!drawflowJson || !drawflowJson.drawflow || !drawflowJson.drawflow.Home || !drawflowJson.drawflow.Home.data) {
            throw new Error(tr("Invalid Drawflow JSON provided."));
        }
        this.drawflowJson = drawflowJson;
        Array.prototype.diff = function (arr2) {
            return this.filter((x) => !arr2.includes(x));
        };
        this.nodeMap = {};
        this.nodes = drawflowJson.drawflow.Home.data;
        this.adjacencyList = {};
        this.rootNodes;
        this.graph = {};
        return this;
    }

    getAdjacentList() {
        for (const nodeId in this.nodes) {
            const node = this.nodes[nodeId];
            this.nodeMap[nodeId] = {
                id: node.id,
                name: node.name,
                type: node.class,
                data: node.data,
                action: node.action || null, // Add 'action' property from Drawflow data
                isStartNode: !!node.isStartNode, // Use the 'isStartNode' property (if present)
                isVisited: false, // Add a 'isVisited' flag
            };
        }
        for (const nodeId in this.nodes) {
            const node = this.nodes[nodeId];
            this.adjacencyList[nodeId] = []; // List of adjacent node IDs
            // Check if the node has output connections
            if (node.outputs) {
                for (const outputName in node.outputs) {
                    const connections = node.outputs[outputName].connections;
                    // For each connection, add the target node ID to the adjacency list
                    for (const connection of connections) {
                        const targetNodeId = connection.node;
                        this.adjacencyList[nodeId].push(targetNodeId);
                    }
                }
            }
        }
        return this;
    }
    getRootNodes() {
        this.rootNodes = Object.keys(this.adjacencyList).diff(Object.values(this.adjacencyList).flat(1));
        if (this.rootNodes.length == 0) {
            throw new Error(tr("Wrong diagram, no valid starting point found"));
        }
        return this;
    }

    checkBadRouting() {
        const flatAdjacentList = Object.values(this.adjacencyList).flat(1);
        const duplicates = flatAdjacentList.filter((item, index) => flatAdjacentList.indexOf(item) !== index);
        if (duplicates.length > 0) {
            throw { message: tr("You have some single inputs nodes paired with multiple output"), details: JSON.stringify(duplicates, null, 2) };
        }
        return this;
    }

    _buildNodeQueue(nodeIds, mainObject) {
        /**
         * Builds a nested object representing the connections of the given nodes.
         *
         * Args:
         *   nodeIds: An array of node IDs to include in the output object.
         *   mainObject: An object representing the original connections between nodes.
         *
         * Returns:
         *   A nested object representing the connections of the specified nodes.
         */

        const outputObject = {};

        for (const nodeId of nodeIds) {
            // Create the base structure for the node
            outputObject[nodeId] = {};
            // Recursively build the nested connections
            this._buildConnections(nodeId, outputObject[nodeId], mainObject);
        }

        return outputObject;
    }

    _buildConnections(nodeId, connectionsObject, mainObject) {
        /**
         * Recursively builds the nested connections for a given node.
         *
         * Args:
         *   nodeId: The ID of the current node.
         *   connectionsObject: The object to store the connections for this node.
         *   mainObject: The original connections object.
         */

        const connectedNodes = mainObject[nodeId] || [];

        for (const connectedNodeId of connectedNodes) {
            connectionsObject[connectedNodeId] = {};
            this._buildConnections(connectedNodeId, connectionsObject[connectedNodeId], mainObject);
        }
    }
    buildQueue() {
        this.rootNodes.forEach((val) => {
            const direct_nodes = this.adjacencyList[val];
            this.graph[val] = {};
            let node_children = {};
            for (let node_id of direct_nodes) {
                //recursion here;
                this.graph[val][node_id] = this._buildNodeQueue(this.adjacencyList[node_id], this.adjacencyList);
            }
        });
        return this;
    }
    traverseGraph() {
        const graph = this.graph;
        const visited = new Set();
        var input;

        function traverseNode(nodeValue, currentTopLevelNodeId, drawflowJson) {
            for (const node_id in nodeValue) {
                //console.log(node_id);
                const { name, data, html } = drawflowJson.drawflow.Home.data[node_id];
                //console.log(name, data, html);
                if (Object.values(nodeValue[node_id]).length > 0) {
                    traverseNode(nodeValue[node_id], currentTopLevelNodeId, drawflowJson);
                } else {
                    //console.log("end of path");
                    //console.log("\n");
                    //console.log(currentTopLevelNodeId);
                }
            }
        }
        for (const topLevelNodeId in this.graph) {
            input = "1"; //to be assigned as per parent node
            //console.log(topLevelNodeId);
            const { name, data, html } = this.drawflowJson.drawflow.Home.data[topLevelNodeId];
            //console.log(name, data, html);
            traverseNode(this.graph[topLevelNodeId], topLevelNodeId, this.drawflowJson);
            //console.log("end of full direction");
            //console.log("\n");
        }
        return this;
    }
}

/**
 * customCreateCurvature
 * @author https://github.com/jerosoler
 * @source https://github.com/jerosoler/Drawflow/issues/20#issuecomment-669753826
 */
function customCreateCurvature(start_pos_x, start_pos_y, end_pos_x, end_pos_y, curvature_value, type) {
    var line_x = start_pos_x;
    var line_y = start_pos_y;
    var x = end_pos_x;
    var y = end_pos_y;
    var curvature = curvature_value;
    //type openclose open close other
    switch (type) {
        case "open":
            if (start_pos_x >= end_pos_x) {
                var hx1 = line_x + Math.abs(x - line_x) * curvature;
                var hx2 = x - Math.abs(x - line_x) * (curvature * -1);
            } else {
                var hx1 = line_x + Math.abs(x - line_x) * curvature;
                var hx2 = x - Math.abs(x - line_x) * curvature;
            }
            return " M " + line_x + " " + line_y + " C " + hx1 + " " + line_y + " " + hx2 + " " + y + " " + x + "  " + y;

            break;
        case "close":
            if (start_pos_x >= end_pos_x) {
                var hx1 = line_x + Math.abs(x - line_x) * (curvature * -1);
                var hx2 = x - Math.abs(x - line_x) * curvature;
            } else {
                var hx1 = line_x + Math.abs(x - line_x) * curvature;
                var hx2 = x - Math.abs(x - line_x) * curvature;
            } //M0 75H10L5 80L0 75Z

            return (
                " M " +
                line_x +
                " " +
                line_y +
                " C " +
                hx1 +
                " " +
                line_y +
                " " +
                hx2 +
                " " +
                y +
                " " +
                x +
                "  " +
                y +
                " M " +
                (x - 11) +
                " " +
                y +
                " L" +
                (x - 20) +
                " " +
                (y - 5) +
                "  L" +
                (x - 20) +
                " " +
                (y + 5) +
                "Z"
            );
            break;
        case "other":
            if (start_pos_x >= end_pos_x) {
                var hx1 = line_x + Math.abs(x - line_x) * (curvature * -1);
                var hx2 = x - Math.abs(x - line_x) * (curvature * -1);
            } else {
                var hx1 = line_x + Math.abs(x - line_x) * curvature;
                var hx2 = x - Math.abs(x - line_x) * curvature;
            }
            return " M " + line_x + " " + line_y + " C " + hx1 + " " + line_y + " " + hx2 + " " + y + " " + x + "  " + y;
            break;
        default:
            var hx1 = line_x + Math.abs(x - line_x) * curvature;
            var hx2 = x - Math.abs(x - line_x) * curvature;

            //return ' M '+ line_x +' '+ line_y +' C '+ hx1 +' '+ line_y +' '+ hx2 +' ' + y +' ' + x +'  ' + y;
            return (
                " M " +
                line_x +
                " " +
                line_y +
                " C " +
                hx1 +
                " " +
                line_y +
                " " +
                hx2 +
                " " +
                y +
                " " +
                x +
                "  " +
                y +
                " M " +
                (x - 11) +
                " " +
                y +
                " L" +
                (x - 20) +
                " " +
                (y - 5) +
                "  L" +
                (x - 20) +
                " " +
                (y + 5) +
                "Z"
            );
    }
}

export function DrawflowInteractiveZone(editor_instance, draw_area_id) {
    interact("#" + draw_area_id).dropzone({
        accept: ".draggable-node.clone",
        overlap: 0.75,

        ondropactivate: function (event) {
            event.target.classList.add("drop-active");
        },
        ondragenter: function (event) {
            var draggableElement = event.relatedTarget;
            var dropzoneElement = event.target;
            dropzoneElement.classList.add("drop-target");
            draggableElement.classList.add("can-drop");
        },
        ondragleave: function (event) {
            event.target.classList.remove("drop-target");
            event.relatedTarget.classList.remove("can-drop");
        },
        ondrop: function (event) {
            const el = $(event.relatedTarget);
            el.data("drop-just-happened", true);
            var offsetElement1 = el.offset();
            var offsetElement2 = $("#" + draw_area_id).offset();
            const allinputs = el.find("input,select,textarea");
            const data = $(this).data("flow-io") ? JSON.parse($(this).data("flow-io")) : {};
            allinputs.each(function () {
                const attrs = $(this).get(0).attributes;
                for (let attr of attrs) {
                    if (attr.name.startsWith("df-")) {
                        const attribute_map = attr.name.split("-"); //df-email-xxx
                        data[attribute_map[1]] = "";
                        continue;
                        //continue here if we need to nest data
                        if (!(attribute_map[1] in data)) {
                            data[attribute_map[1]] = {};
                        }
                        data[attribute_map[1]][attribute_map[2]] = "";
                    }
                }
            });
            el.attr("data-flow-io", JSON.stringify(data));
            var relativeOffsetX = offsetElement1.left - offsetElement2.left;
            var relativeOffsetY = offsetElement1.top - offsetElement2.top;
            editor_instance.addNode(
                el.data("for"),
                el.data("inputs"),
                el.data("outputs"),
                relativeOffsetX,
                relativeOffsetY,
                el.data("for"),
                data,
                el.data("for"),
                true
            );
            (function () {
                setTimeout(function () {
                    el.css("transform", "translate(0px, 0px)");
                    el.attr("data-x", 0);
                    el.attr("data-y", 0);
                }, 0);
            })(el);
        },
        ondropdeactivate: function (event) {
            const el = $(event.relatedTarget);
            if (!el.data("drop-just-happened")) {
                // remove active dropzone feedback
                $("body").removeClass("non-scrollable");
                event.target.classList.remove("drop-active");
                event.target.classList.remove("drop-target");
                const prevTransition = el.css("transition");
                el.css("transition", "transform 0.5s ease");
                el.css("transform", "translate(0px, 0px)");
                el.attr("data-x", 0);
                el.attr("data-y", 0);
                setTimeout(function () {
                    el.css("transition", "transform 0s linear");
                }, 500);
            }
            el.data("drop-just-happened", false);
        },
    });
}

function dragMoveListener(event) {
    var target = event.target;
    var x = (parseFloat(target.getAttribute("data-x")) || 0) + event.dx;
    var y = (parseFloat(target.getAttribute("data-y")) || 0) + event.dy;
    target.style.transform = "translate(" + x + "px, " + y + "px)";
    target.setAttribute("data-x", x);
    target.setAttribute("data-y", y);
    $("body").addClass("non-scrollable");
}

interact(".draggable-node.clone").draggable({
    inertia: true,
    modifiers: [
        interact.modifiers.restrictRect({
            //restriction: "parent",
            endOnly: true,
        }),
    ],
    autoScroll: false,
    listeners: {
        move: dragMoveListener,
    },
});

export function zoomOut(editor_instance) {
    drawflowInstances[editor_instance].zoom_out();
}

export function zoomIn(editor_instance) {
    drawflowInstances[editor_instance].zoom_in();
}

export function zoomReset(editor_instance) {
    drawflowInstances[editor_instance].zoom_reset();
}

function getDrawing(editor_instance) {
    return drawflowInstances[editor_instance].export();
}

export function clearEditor(editor_instance) {
    drawflowInstances[editor_instance].clear();
}

function loadDrawing(editor_instance, app_name, drawing_config) {
    try {
        let editor_processor = new DrawflowProcessor(drawing_config);
        editor_processor.getAdjacentList().getRootNodes().checkBadRouting();
        drawflowInstances[editor_instance].zoom_out();
        drawflowInstances[editor_instance].zoom_out();
        drawflowInstances[editor_instance].createCurvature = customCreateCurvature; //if we need arrows
        drawflowInstances[editor_instance].import(drawing_config);
        return true;
    } catch (error) {
        if (Object.keys(drawing_config).length > 0) {
            //if not empty object then we have good corruped data
            $.fn.toastNotification({
                title: tr("Error processing app: ") + app_name,
                body: error.message,
                position: "bottom-end",
                classes: "bg-danger text-white",
            });
        }
        return false;
    }
}

export function initFlowIfFirstTime(editor_instance) {
    if (drawflowImports.hasOwnProperty(editor_instance)) {
        setTimeout(function () {
            loadDrawing(editor_instance, drawflowImports[editor_instance]["app_name"], drawflowImports[editor_instance]["data"]);
            delete drawflowImports[editor_instance];
        }, 1000);
    }
}

export function saveDrawing(el, editor_instance, app_uuid, app_name) {
    $(el).tikiModal(" ");
    const drawing_config = getDrawing(editor_instance);
    try {
        let processor = new DrawflowProcessor(drawing_config);
        processor.getAdjacentList().getRootNodes().checkBadRouting().buildQueue().traverseGraph();
        let processor_graph = processor.graph;
        let formData = {
            app_uuid,
            scenario_config: JSON.stringify(drawing_config),
            app_name,
        };

        $.post("tiki-ajax_services.php", {
            controller: "iotapps",
            action: "save_flow_drawing",
            payload: formData,
        }).always(function () {
            location.reload();
        });
        return true;
    } catch (error) {
        $.fn.toastNotification({
            title: tr("Error"),
            body: error.message,
            position: "bottom-end",
            classes: "bg-danger text-white",
        });
        $(el).tikiModal("");
    }
}

const $container = $("#iot_dashboard");
const $fullscreenButton = $("#fullscreen-button");

const fullscreenMethods = [
    "requestFullscreen",
    "mozRequestFullScreen", // Firefox
    "webkitRequestFullscreen", // Chrome, Safari, Opera
    "msRequestFullscreen", // IE/Edge
];

$fullscreenButton.on("click", function () {
    const element = $container.get(0);
    for (const method of fullscreenMethods) {
        if (element[method]) {
            element[method]();
            break;
        }
    }
});

// Event Listeners for Fullscreen Change
$(document).on("fullscreenchange mozfullscreenchange webkitfullscreenchange msfullscreenchange", handleFullscreenChange);

function handleFullscreenChange() {
    if (document.fullscreenElement || document.mozFullScreenElement || document.webkitFullscreenElement || document.msFullscreenElement) {
        $fullscreenButton.hide();
        $container.css("height", "100svh");
    } else {
        $fullscreenButton.show();
        $container.css("height", "unset");
    }
}

$("[data-button-open-app]").click(function () {
    const $uuid = $(this).data("button-open-app");
    $(".app-entry").addClass("d-none");
    $(`#${$uuid}`).removeClass("d-none");
    $("[data-button-open-app]").removeClass("active");
    $(this).addClass("active");
    $(".non-app-selected-warning").fadeOut();
});
$("[data-app-control]").click(function (e) {
    e.stopPropagation();
    $(this).tikiModal(" ");
    let state = $(this).prop("checked") ? "y" : "n";
    let app_uuid = $(this).data("app-control");
    let app_name = $(this).data("app-name");
    $.post($.service("iotapps", "toggle_app_status"), {
        app_uuid,
        state,
        app_name,
    }).always(function () {
        location.reload();
    });
});
$(".create_app").click(function (e) {
    e.stopPropagation();
    $.openModal({
        title: tr("Create new IoT App"),
        size: "modal-lg",
        remote: null,
        open: function () {
            $(this).find(".modal-content").html(createIotAppFormTemplate);
            $(this)
                .find(".modal-footer .btn-primary")
                .click(function (e) {
                    e.preventDefault();
                    const form = $(this).parents(".modal").find("#create-new-iot-app-form").get(0);
                    form.classList.add("was-validated");
                    if (!form.checkValidity()) {
                        return;
                    }
                    const formData = {};
                    $(this)
                        .parents(".modal")
                        .find("#create-new-iot-app-form input[id], #create-new-iot-app-form select[id]")
                        .each(function () {
                            formData[$(this).attr("id")] = $(this).val();
                        });
                    $.post($.service("iotapps", "create_app"), {
                        payload: formData,
                    }).always(function () {
                        location.reload();
                    });
                });
        },
    });
});
$(".edit_app").click(function (e) {
    e.stopPropagation();
    let app_name = $(this).parents("button").data("app-name");
    let app_uuid = $(this).parents("button").data("button-open-app"); // or app_data.app_uuid
    let modalContent = editIotAppForms[app_uuid];
    $.openModal({
        title: tr("Edit IoT App") + ": " + app_name,
        size: "modal-lg",
        remote: null,
        open: function () {
            $(this).find(".modal-content").html(modalContent);
            $(this)
                .find(".modal-footer .btn-primary")
                .click(function (e) {
                    e.preventDefault();
                    const form = $(this).parents(".modal").find("#update-iot-app-form").get(0);
                    form.classList.add("was-validated");
                    if (!form.checkValidity()) {
                        return;
                    }
                    const formData = {};
                    $(this)
                        .parents(".modal")
                        .find("#update-iot-app-form input[id], #update-iot-app-form select[id]")
                        .each(function () {
                            formData[$(this).attr("id")] = $(this).val();
                        });
                    $.post($.service("iotapps", "edit_app"), {
                        payload: formData,
                    }).always(function () {
                        location.reload();
                    });
                });
        },
    });
});
$(".delete_app").click(function (e) {
    e.stopPropagation();
    let app_name = $(this).parents("button").data("app-name");
    let app_uuid = $(this).parents("button").data("button-open-app");
    let app_data = $(this).parents("button").data("app-raw-info");
    $(this).confirmationDialog({
        title: tr("Delete IoT app"),
        message: tr("Are you sure you want to delete the app ") + '"' + app_name + '"?' + tr(" This does not delete existing data in your Tracker."),
        success: function () {
            $.post($.service("iotapps", "delete_app"), {
                app_uuid: app_data["app_uuid"],
                app_name: app_data["app_name"],
            }).always(function () {
                location.reload();
            });
        },
    });
});

$(function () {
    $("span[data-icon-name]").each(function () {
        $(this).setIcon($(this).data("icon-name"));
    });
});

export function showAccessTokenForm(selector) {
    $(selector).toggleClass("d-none");
}

export function generateToken(el) {
    const array = new Uint32Array(4);
    window.crypto.getRandomValues(array);
    const token = Array.from(array, (dec) => ("00000000" + dec.toString(16)).slice(-8)).join("");
    $(el).siblings("input[name='access_token']").val(token);
}

$(document).ready(function () {
    const forms = $(".needs-validation");
    forms.each(function () {
        $(this).on("submit", function (event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            $(this).addClass("was-validated");
        });
    });
});

$('form[id^="iot-accesstoken-form"]').on("submit", function (e) {
    e.preventDefault();
    const form = $(this);
    if (!form.get(0).checkValidity()) {
        return;
    }
    form.find("button[type='submit']").tikiModal(" ");
    const access_token = form.find("input[name='access_token']").val();
    const token_expire_at = form.find("input[name='token_expire_at']").val();
    const app_uuid = form.find("input[name='app_uuid']").val();
    const app_name = form.find("input[name='app_name']").val();
    $.post($.service("iotapps", "save_access_token"), {
        payload: { access_token, token_expire_at, app_uuid, app_name },
    }).always(function () {
        location.reload();
    });
});

export function revoqueAccessToken(el, app_uuid, app_name) {
    $(el).tikiModal(" ");
    $.post($.service("iotapps", "revoque_access_token"), {
        payload: { app_uuid, app_name },
    }).always(function () {
        location.reload();
    });
}

function addEvents(grid, id) {
    let g = id !== undefined ? "grid" + id + " " : "";

    grid.on("added removed change", function (event, items) {
        let str = "";
        items.forEach(function (item) {
            str += " (" + item.x + "," + item.y + " " + item.w + "x" + item.h + ")";
        });
        //console.log(g + event.type + " " + items.length + " items (x,y w h):" + str);
        $(".grid-stack-item").on("dblclick", function () {
            grid.removeWidget($(this).get(0)); //update the remove handler, this will be called when (load items, insert new block, delete block)
        });
    });

    // We are currently not using the following events but probably in the future
    /*
       grid.on('enable', function (event) {
            let grid = event.target;
            console.log(g + 'enable');
        })
        .on('disable', function (event) {
            let grid = event.target;
            console.log(g + 'disable');
        })
        .on('dragstart', function (event, el) {
            let n = el.gridstackNode;
            let x = el.getAttribute('gs-x'); // verify node (easiest) and attr are the same
            let y = el.getAttribute('gs-y');
            console.log(g + 'dragstart ' + (n.content || '') + ' pos: (' + n.x + ',' + n.y + ') = (' + x + ',' + y + ')');
        })
        .on('drag', function (event, el) {
            let n = el.gridstackNode;
            let x = el.getAttribute('gs-x'); // verify node (easiest) and attr are the same
            let y = el.getAttribute('gs-y');
            console.log(g + 'drag ' + (n.content || '') + ' pos: (' + n.x + ',' + n.y + ') = (' + x + ',' + y + ')');
        })
        .on('dragstop', function (event, el) {
            let n = el.gridstackNode;
            let x = el.getAttribute('gs-x'); // verify node (easiest) and attr are the same
            let y = el.getAttribute('gs-y');
            console.log(g + 'dragstop ' + (n.content || '') + ' pos: (' + n.x + ',' + n.y + ') = (' + x + ',' + y + ')');
        })
        .on('dropped', function (event, previousNode, newNode) {
            if (previousNode) {
                console.log(g + 'dropped - Removed widget from grid:', previousNode);
            }
            if (newNode) {
                console.log(g + 'dropped - Added widget in grid:', newNode);
            }
        })
        .on('resizestart', function (event, el) {
            let n = el.gridstackNode;
            let rec = el.getBoundingClientRect();
            console.log(`${g} resizestart ${n.content || ''} size: (${n.w}x${n.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);

        })
        .on('resize', function (event, el) {
            let n = el.gridstackNode;
            let rec = el.getBoundingClientRect();
            console.log(`${g} resize ${n.content || ''} size: (${n.w}x${n.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);
        })
        .on('resizestop', function (event, el) {
            let n = el.gridstackNode;
            let rec = el.getBoundingClientRect();
            console.log(`${g} resizestop ${n.content || ''} size: (${n.w}x${n.h}) = (${Math.round(rec.width)}x${Math.round(rec.height)})px`);
        });
    */
}

var grids = {};
let options = {
    column: 6,
    minRow: 6, // don't collapse when empty
    cellHeight: 80,
    float: true,
    disableResize: $("[data-app-id-plain]").length > 0 ? true : false, //[data-app-id-plain] exist only in the final view dashboard template
    disableDrag: $("[data-app-id-plain]").length > 0 ? true : false,
};

$("[data-grid-stack]").each(function () {
    grids[$(this).attr("id")] = GridStack.init(options, $(this).get(0));
});

for (const gridId in grids) {
    let grid = grids[gridId];
    addEvents(grid, gridId);
    //let grid_items = $(`[data-grid-items-for='${gridId}']`).text();
    //if (grid_items != "{}") {
    //    grid.load(JSON.parse(grid_items));
    //}
}

$(".grid-stack-item").on("dblclick", function () {
    let parent = $(this).parents("[data-grid-stack]");
    grids[parent.attr("id")].removeWidget($(this).get(0));
});

export function toggleFloat(el) {
    let id = $(el).data("grid-stack-id");
    grids[id].float(el.prop("checked"));
}

export function compact(el) {
    let id = $(el).data("grid-stack-id");
    grids[id].compact();
}

$("button[data-bs-target^='#dashboard-tab-pane'").click(function () {
    let $pane_id = $(this).data("bs-target");
    $($pane_id)
        .not("[half-gauge-updated]")
        .find(".initialized[data-widget-type='gauge-half']")
        .each(function () {
            const target = $(this);
            let opts = {
                angle: 0,
                lineWidth: 0.28,
                radiusScale: 1,
                pointer: {
                    length: 0.35,
                    strokeWidth: 0.035,
                    color: $(target).data("pointer-color"),
                },
                limitMax: 100,
                limitMin: 0,
                colorStart: $(target).data("gauge-bg-color"),
                colorStop: $(target).data("gauge-bg-color"),
                strokeColor: $(target).data("pointer-color"),
                generateGradient: true,
                highDpiSupport: true,
            };
            let gauge = new Gauge(target.get(0)).setOptions(opts);
            gauge.set(0);
        });

    $($pane_id)
        .not("[gauge-updated]")
        .find(".initialized[data-widget-type='gauge']")
        .each(function () {
            const target = $(this);
            let opts = {
                angle: 0.5,
                lineWidth: 0.09,
                radiusScale: 1,
                pointer: {
                    length: 0.6,
                    strokeWidth: 0.035,
                    color: $(target).data("pointer-color"),
                },
                limitMax: 100,
                limitMin: 0,
                colorStart: $(target).data("gauge-bg-color"),
                colorStop: $(target).data("gauge-bg-color"),
                strokeColor: $(target).data("pointer-color"),
                generateGradient: true,
                highDpiSupport: true,
            };
            let gauge = new Donut(target.get(0)).setOptions(opts);
            gauge.set(0);
        });

    $($pane_id).attr("gauge-update", true).attr("half-gauge-updated", true);
});

$("[add-widget-button]").click(function () {
    const container = $(this).parents("[new-widget-config]");
    const options = {};
    options.widget = container.find("[name='widget-select']").val();
    options.dataSource = container.find("[name='data-source-select']").val();
    options.widgetIcon = container.find("[name='widget-icon-select']").val();
    options.bgColor = container.find("[name='bgcolor']").val();
    options.textColor = container.find("[name='textcolor']").val();
    options.borderColor = container.find("[name='bordercolor']").val();
    options.widgetLabel = container.find("[name='widgetLabel']").val();
    options.dataUnit = container.find("[name='dataUnit']").val();

    let id = $(this).data("grid-stack-id");
    grids[id].addWidget(window.getWidgetMarkup(options));
    if (options.widget == "gauge") {
        let target = $("canvas[data-widget-type='gauge']").not(".initialized").get(0);
        let opts = {
            angle: 0.5,
            lineWidth: 0.09,
            radiusScale: 1,
            pointer: {
                length: 0.6,
                strokeWidth: 0.035,
                color: $(target).data("pointer-color"),
            },
            limitMax: 100,
            limitMin: 0,
            colorStart: $(target).data("gauge-bg-color"),
            colorStop: $(target).data("gauge-bg-color"),
            strokeColor: $(target).data("pointer-color"),
            generateGradient: true,
            highDpiSupport: true,
        };
        let gauge = new Donut(target).setOptions(opts);
        gauge.set(0);
        $(target).addClass("initialized");
    }
    if (options.widget == "gauge-half") {
        var target = $("canvas[data-widget-type='gauge-half']").not(".initialized").get(0);
        var opts = {
            angle: 0,
            lineWidth: 0.28,
            radiusScale: 1,
            pointer: {
                length: 0.35,
                strokeWidth: 0.035,
                color: $(target).data("pointer-color"),
            },
            limitMax: 100,
            limitMin: 0,
            colorStart: $(target).data("gauge-bg-color"),
            colorStop: $(target).data("gauge-bg-color"),
            strokeColor: $(target).data("pointer-color"),
            generateGradient: true,
            highDpiSupport: true,
        };
        let gauge = new Gauge(target).setOptions(opts);
        gauge.set(0);
        $(target).addClass("initialized");
    }
});

export function saveDashboardUi(el, app_name, app_uuid) {
    $(el).tikiModal(" ");
    const drawingId = $(el).data("grid-stack-id");
    const grid = grids[drawingId];
    const formData = {
        grid_data: $(`#${drawingId}`).html(),
        app_name,
        app_uuid,
    };

    try {
        $.post("tiki-ajax_services.php", {
            controller: "iotapps",
            action: "save_dashboard",
            payload: formData,
        }).always(function () {
            location.reload();
        });
        return true;
    } catch (error) {
        $.fn.toastNotification({
            title: tr("Error"),
            body: error.message,
            position: "bottom-end",
            classes: "bg-danger text-white",
        });
        $(el).tikiModal("");
    }
}

export { Drawflow, GridStack };
