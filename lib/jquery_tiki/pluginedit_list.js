/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 *
 * Handles list plugin GUI
 */


// event for plugin list
$(document)
    .off('plugin_list_ready')
    .on('plugin_list_ready', function (params) {

        jqueryTiki.plugins.list.setup(
            params.modal.find("textarea[name=content]")
        );

    });

$(function () {

    jqueryTiki.plugins = jqueryTiki.plugins || {};

    jqueryTiki.plugins.list = {
        /**
         * Local data
         */
        current: {},
        fields: {},
        plugins: {},
        objectType: null,
        $editor: null,

        /**
         * Main GUI setup
         *
         * @param $textarea
         */
        setup: function ($textarea) {

            const gui = this;

            gui.current = {};
            gui.fields = {};
            gui.plugins = {};
            gui.trackers = {};
            gui.objectType = null;
            gui.sortableOptions = {
                listType: "ul",
                maxLevels: 2,
                handle: "div:first",
                items: "li",
                disableNesting: "no-nesting"
            };

            // display GUI interface here
            gui.$editor = $("<div>")
                .addClass('plugin-list-editor clearfix');

            const buildMainToolbar = function () {
                const $tb = $("<div>")
                    .addClass("btn-toolbar")
                    .append(
                        gui.buildToolBar(gui.plugins, "", "", function () {
                            if ($(this).data("plugin")) {
                                const params = [];
                                if ($(this).data("value")) {
                                    params[$(this).data("value")] = "";
                                }
                                $ul.append(
                                    gui.addPlugin({
                                        name: $(this).data("plugin"),
                                        params: params,
                                        plugins: []
                                    })
                                ).nestedSortable(gui.sortableOptions);

                                return false;
                            }
                        })
                    );

                return $tb;
            };

            const $toolbar = buildMainToolbar();
            gui.$editor.append($toolbar);

            const $ul = $("<ul>")
                .addClass('plugin-list-gui clearfix')
                .appendTo(gui.$editor);

            const showGui = function () {
                const $form = $textarea.parents("form");

                $form.tikiModal(tr("Loading..."));

                $textarea
                    .parents(".row")
                    .find("> label").hide()            // hide the label column
                    .parent().find("> .col-sm-9").removeClass("col-sm-9").addClass("col-sm-12")
                    .find(".description").hide();    // hide the body description

                $.getJSON(
                    $.service("plugin", "list_edit"),
                    {
                        body: $textarea.val()
                    },
                    function (data) {
                        try {
                            if (data) {
                                $ul.empty().show();
                                $(".gui-only", $toolbar)
                                    .prop("disabled", false)
                                    .css("opacity", 1);

                                gui.current = data.current;
                                gui.plugins = data.plugins;
                                gui.fields = data.fields;
                                gui.fields.formatted = [];
                                gui.trackers = data.trackers;

                                for (let p = 0; p < gui.current.length; p++) {    // check for format names and add to fields
                                    if (gui.current[p].name === "format" && typeof gui.current[p].params.name !== "undefined") {
                                        gui.fields.formatted.push(gui.current[p].params.name);
                                    }
                                }

                                for (let p = 0; p < gui.current.length; p++) {
                                    $ul.append(
                                        gui.addPlugin(gui.current[p])
                                    );
                                }

                                if (jqueryTiki.select2) {
                                    gui.$editor.find("select").trigger("change.select2");
                                }

                                $toolbar.replaceWith(buildMainToolbar()).applySelect2();

                                $form.find(".nav-tabs li:first-child a").tab("show");
                                $form.find(".nav-tabs li:first-child a").tab("show");
                                $form.tikiModal();

                                // bs5 (not working)
                                const dropdownElementList = gui.$editor.get(0).querySelectorAll('.dropdown-toggle');
                                const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
                            }
                        } catch (e) {
                            console.error(e);
                            $ul.empty().hide();
                            $toolbar.hide();
                            $form.tikiModal();
                            $("#source-tab").tabs("show")
                                .find($textarea)
                                .showError(tr("List plugin syntax is currently not compatible with the GUI, so source editing only is available."));
                        }
                    }
                );

            };

            $ul.nestedSortable(gui.sortableOptions);

            const $tabs = $("<ul>").addClass("nav nav-tabs")
                .append(
                    $("<li>").addClass("nav-item")
                        .append($("<a>").addClass("nav-link")
                            .attr("id", "editor-tab").attr("href", "#editor").text(tr("Editor"))
                            .on("click", function (e) {
                                e.preventDefault();
                                showGui();
                            })),
                    $("<li>").addClass("nav-item")
                        .append($("<a>").addClass("nav-link")
                            .attr("id", "source-tab").attr("href", "#source").text(tr("Source"))
                            .on("click", function (e) {
                                e.preventDefault();
                                gui.saveToTextarea();
                                $(this).tab('show');
                            })),
                    $("<li>").addClass("nav-item")
                        .append($("<a>").addClass("nav-link")
                            .attr("id", "params-tab").attr("href", "#params").text(tr("Parameters"))
                            .on("click", function (e) {
                                e.preventDefault();
                                $("#editor").find(".done").removeClass("done");
                                $(this).tab('show');
                            }))
                );


            const $form = $textarea.parents("form");
            const $params = $(".row", $form).not($(".row", $form).last()).detach();
            const $tabContent = $("<div>").addClass("tab-content")
                .append(
                    $("<div>").attr("id", "editor").addClass("tab-pane mt-2")
                        .append(gui.$editor),
                    $("<div>").attr("id", "source").addClass("tab-pane mt-2")
                        .append(
                            $textarea.detach()
                        ),
                    $("<div>").attr("id", "params").addClass("tab-pane mt-2")
                        .append(
                            $params.detach()
                        )
                );


            $form
                .append(
                    $tabs,
                    $tabContent
                )
                .on("submit", function () {
                    return $textarea.is(":visible") || gui.saveToTextarea();
                });

            showGui();

        },

        /**
         * Convert the current state of the GUI into plugin markup in the content textarea
         */
        saveToTextarea: function () {
            let $ul = this.$editor.find(".plugin-list-gui"), markup = "";
            let currentPlugins;

            const findPlugins = function ($element, parentPluginName) {
                    parentPluginName = parentPluginName || "";

                    const plugins = [];

                    $element.find(".plugin").each(function () {
                        const $plugin = $(this),
                            pluginName = $plugin.data("name"),
                            params = {};
                        let body;

                        $plugin.find("> .params .input-group").each(function () {
                            const $param = $(this),
                                paramName = $param.find(".param-name > span").text();

                            params[paramName] = $param.find("> select, > input").val();
                        });

                        if ($plugin.is(".done")) {
                            return plugins;
                        }

                        if (parentPluginName) {
                            $plugin.addClass("done");
                        }

                        if (pluginName === "wiki text") {
                            body = $plugin.find("textarea").val();
                        } else {
                            body = "";
                        }

                        plugins.push({
                            name: pluginName,
                            params: params,
                            body: body,
                            plugins: findPlugins($plugin, pluginName),
                            parent: parentPluginName
                        });

                    });

                    return plugins;
            },
                getSyntax = function (plugin, indent, noLineFeeds) {
                    indent = indent && !noLineFeeds ? indent : "  ";
                    noLineFeeds = noLineFeeds || false;
                    const name = plugin.plugins.length ? plugin.name.toUpperCase() : plugin.name;
                    let output = "\n\n";

                    if (name === "wiki text") {
                        output = plugin.body;
                    } else {
                        output = indent + "{" + name + (plugin.plugins.length ? "(" : "");

                        for (const param in plugin.params) {
                            if (plugin.params.hasOwnProperty(param)) {
                                output += " " + param + "=\"" + plugin.params[param] + "\"";
                            }
                        }
                        output += (plugin.plugins.length ? ")" : "") + "}";
                        if (!noLineFeeds) {
                            output += "\n";
                        }

                        for (let i = 0; i < plugin.plugins.length; i++) {
                            output += getSyntax(plugin.plugins[i], indent + "  ", name === "OUTPUT" && !plugin.params.template);
                        }
                        if (plugin.plugins.length) {
                            output += "{" + name + "}\n";
                        }
                    }
                    return output;
                };


            currentPlugins = findPlugins($ul);

            for (let i = 0; i < currentPlugins.length; i++) {
                markup += getSyntax(currentPlugins[i]);
            }

            this.$editor.parents("form").find("textarea[name=content]").val(markup);

            return true;
        },

        /**
         * Add the visual representation of a plugin
         *
         * @param plugin Object          plugin to add
         * @param parentPath string      location of the enclosing plugin
         * @return $li jQuery            list item representing the plugin
         */
        addPlugin: function (plugin, parentPath) {


            const gui = this;
            let parentPlugin,
                pluginName = plugin.name,
                paramsDef = gui.plugins[pluginName] ? gui.plugins[pluginName].params : null;

            parentPath = parentPath || "";

            parentPlugin = gui.getPlugin(parentPath);

            if (typeof plugin === 'string') {            // from current plugins
                pluginName = tr("wiki text");
                plugin = plugin.replace(/^\s*[\r\n]/, "");    // strip off initial blank line

                if (!plugin.trim()) {
                    return null;
                }
            } else if (pluginName === "wiki text") {    // from the toolbar
                plugin = "";
            } else if (!paramsDef && parentPlugin.params) {
                paramsDef = parentPlugin.params;
            }

            if (!paramsDef && typeof plugin !== 'string') {
                console.log("addPlugin error: " + pluginName + "->" + parentPlugin.name + " not found");
                return null;
            }

            const $li = $("<li>")
                .addClass("plugin inline-form card mb-3")
                .data("name", pluginName)
                .append(
                    $("<div>")
                        .addClass("card-header d-flex justify-content-between")
                        .append(
                            $("<div>")
                                .addClass("d-flex")
                                .append($("<div>").addClass("name")
                                    .text(pluginName))
                            ,
                            $("<a>")
                                .addClass("close small text-danger")
                                .html("&times;")
                                .on("click", function () {
                                    $(this).parents("li").first().remove();
                                })
                        )
                );

            if (pluginName !== "output" && pluginName !== "format") {
                $li.addClass("no-nesting");
            } else {
                $li.find(".card-header > div").first().append(
                    $("<div>").addClass("ms-2").append(
                        gui.buildToolBar(gui.plugins, pluginName, "", function () {
                            if ($(this).data("plugin")) {
                                const params = [];
                                if ($(this).data("value")) {
                                    params[$(this).data("value")] = "";
                                }
                                $ul.append(
                                    gui.addPlugin({
                                        name: $(this).data("plugin"),
                                        params: params,
                                        plugins: []
                                    })
                                ).nestedSortable(gui.sortableOptions);

                                return false;
                            }
                            $(".dropdown-menu", $tb).hide();
                        })
                    )
                );
            }

            if (pluginName === "wiki text") {
                $li.append(
                    $("<div>")
                        .addClass("params card-body row")
                        .append(
                            $("<textarea>")
                                .addClass("form-control")
                                .val(typeof plugin === "string" ? plugin : "")
                        )
                );
                return $li;
            }

            let $paramsDivs = $("<div>").addClass("params card-body d-flex flex-wrap"),
                value = "", otherParams = [];

            for (const paramName in plugin.params) {
                if (plugin.params.hasOwnProperty(paramName)) {
                    value = plugin.params[paramName];

                    if (plugin.params) {
                        otherParams = plugin.params;
                    } else if (paramsDef[paramName] && paramsDef[paramName].params) {
                        otherParams = paramsDef[paramName].params;
                    }
                    $paramsDivs.append(this.buildParam(pluginName, paramName, value, parentPath, otherParams));
                }
            }
            // would be nice to move the extra params dropdown to the card header TODO
            $li.find(".card-header > div").first().append(
                $("<ul>")
                    .addClass("list-unstyled ms-2")
                    .append(
                        $paramsDivs.find("li.nav-item.dropdown").detach()
                    )
            );

            $li.append($paramsDivs);

            const $ul = $("<ul>");

            if (plugin.plugins && plugin.plugins.length) {
                parentPath += "/" + plugin.name;

                // note Object.values does not exist in IE/Edge but Object.keys does
                if (plugin.params && Object.keys(plugin.params).length) {
                    parentPath += "/" + Object.keys(plugin.params)[0];
                    parentPath += "/" + plugin.params[Object.keys(plugin.params)[0]];
                }

                for (let i = 0; i < plugin.plugins.length; i++) {
                    $ul.append(
                        gui.addPlugin(
                            plugin.plugins[i],
                            parentPath + (plugin.plugins[i].name ? "/" + plugin.plugins[i].name : "")
                        )
                    );
                }
            }

            $ul.appendTo($li);

            return $li;
        },

        /**
         *
         * @param path string     path of plugin to find, e.g. output/template/table/column
         *
         * @return Object         the plugin
         */
        getPlugin: function (path) {
            if (!path) {
                return {};
            }

            let parts = path.split("/"),
                plugin = this.plugins, pluginOrParam = plugin;

            for (let i = 0; i < parts.length; i++) {
                if (parts[i]) {
                    if (typeof  pluginOrParam[parts[i]] !== "undefined") {
                        pluginOrParam = pluginOrParam[parts[i]];
                        plugin = pluginOrParam;
                    } else if (pluginOrParam.plugins && typeof pluginOrParam.plugins[parts[i]] !== "undefined") {
                        pluginOrParam = pluginOrParam.plugins[parts[i]];
                        plugin = pluginOrParam;
                    } else if (pluginOrParam.params && typeof pluginOrParam.params[parts[i]] !== "undefined") {
                        pluginOrParam = pluginOrParam.params[parts[i]];
                    } else if (pluginOrParam.options && typeof pluginOrParam.options[parts[i]] !== "undefined") {
                        pluginOrParam = pluginOrParam.options[parts[i]];
                    }
                }
            }

            return plugin;
        },

        /**
         * Create the div representing the parameter to attach to the plugin li element
         *
         * @param pluginName String
         * @param paramName String
         * @param value String
         * @param parentPath String
         * @param otherParams Object
         * @return {*|jQuery}
         */
        buildParam: function (pluginName, paramName, value, parentPath, otherParams) {
            let paramDef, gui = this, $input, $moreParamsDropDown = "";

            if (paramName === "empty") {    // dummy parameter for output etc
                return;
            }

            if (paramName === "*") {    // wildcard for wikiplugins - TODO better
                paramName = prompt("Enter wikiplugin parameter name");
            }

            value = value || "";
            parentPath = parentPath || "";
            otherParams = otherParams || {};

            const parentPlugin = gui.getPlugin(parentPath);

            if (gui.plugins[pluginName] && typeof gui.plugins[pluginName].params[paramName] !== "undefined") {
                // simple case first, e.g. filter.content
                paramDef = this.plugins[pluginName].params[paramName];
            }
            if (!paramDef && parentPlugin && parentPlugin.params) {
                // nested output/column etc plugins e.g. format.display.name
                if (parentPlugin.params) {
                    paramDef = parentPlugin.params[paramName];
                } else {
                    paramDef = parentPlugin.plugins[pluginName].params[paramName];
                }
            }
            if (!paramDef && otherParams) {
                // for params dependent on others, like filter.lat with filter.distance or display.singleList with display.categorylist
                for (const otherParam in otherParams) {
                    if (otherParams.hasOwnProperty(otherParam)) {
                        if (otherParam === paramName) {
                            paramDef = otherParams[otherParam];
                            break;
                        }
                    }
                }
            }

            if (!paramDef) {
                console.log("Warning: param " + paramName + " not found in plugin " + pluginName);
                paramDef = {};
            }

            paramDef.name = paramName;
            const path = parentPath + "/" + pluginName + "/" + paramName;

            if (paramDef.options) {            // select
                const list = gui.arrayKeys(paramDef.options);
                if (pluginName === "output" && paramName === "template" && value &&
                    ($.inArray(value, list) === -1 || value === "input")) {
                    $input = $("<input>")
                        .addClass("param-value form-control")
                        .val(value === "input" ? "" : value);
                } else {
                    $input = gui.buildSelector(
                        list,
                        value,
                        path
                    );
                }
            } else {
                switch (paramDef.type) {
                    case "object_type":
                        gui.objectType = value;
                        $input = gui.objectTypesSelector(value);
                        break;

                    case "field":
                        if (otherParams.format && otherParams.format === "wikiplugin") {
                            gui.trackerId = otherParams.content || otherParams.exact;
                            $input = gui.pluginsSelector(value);
                        } else {
                            if (value === "tracker_id") {
                                gui.trackerId = otherParams.content || otherParams.exact;
                            }
                            $input = gui.fieldsSelector(value);
                        }
                        break;

                    case "number":
                        $input = $("<input>")
                            .attr("type", "number")
                            .attr("step", paramDef.step ? paramDef.step : 1)
                            .addClass("param-value form-control")
                            .val(value);
                        break;

                    default:    // text
                        $input = $("<input>")
                            .addClass("param-value form-control")
                            .val(value);
                }
            }

            $input.on("change", function () {
                // add param specific toolbar
                if ($input.is("select") && paramDef.options && $input.val() && paramDef.options[$input.val()].plugins) {
                    $input.parents(".plugin").find(".btn-toolbar").empty().append(
                        gui.buildToolBar(paramDef.options[$input.val()].plugins, pluginName, path + "/" + $input.val(), function () {
                            if ($(this).data("plugin")) {
                                const params = [];
                                if ($(this).data("value")) {
                                    params[$(this).data("value")] = "";
                                }

                                $(this).parents(".plugin").find("> ul").append(
                                    gui.addPlugin({
                                        name: $(this).data("plugin"),
                                        params: params,
                                        plugins: []
                                    }, $(this).data("path"))
                                ).nestedSortable(gui.sortableOptions);

                                return false;
                            }
                            $(".dropdown-menu", $tb).hide();
                        })
                    );
                }
            });

            // quick fix to get extra params for format options
            if (paramName === "format" && this.plugins.display.params.name.params.format.options[value]) {
                paramDef = this.plugins.display.params.name.params.format.options[value];
            }

            if (paramDef.params) {
                // extra params
                $input.data("params", gui.arrayKeys(paramDef.params));
                $moreParamsDropDown = this.buildDropDown(
                    gui.arrayKeys(paramDef.params),
                    tr("parameters"),
                    function () {
                        const otherParams = paramDef.params;
                        $input.parents(".params").append(
                            gui.buildParam(pluginName, $(this).data("value"), "", path, otherParams)
                        );
                    }
                );
            }

            if (value) {
                setTimeout(function () {
                    $input.trigger("change");
                }, 100);
            }

            return $("<div class='col-md-6'>")
                .append(
                    $moreParamsDropDown,
                    $("<div class='input-group input-group-sm mb-2'>")
                        .append(
                            $("<div class='param-name input-group-prepend'>")
                                .append(
                                    $("<span class='input-group-text'>").text(paramName)
                                ),
                            $input,
                            $("<div class='input-group-append'>")
                                .append(
                                    $("<a class='input-group-text text-danger'>")
                                        .html("&times;")
                                        .on("click", function () {
                                            $(this).parents(".input-group").remove();
                                        })
                                )
                        )
                );

        },

        arrayKeys: function (object) {
            const list = [];

            for (const key in object) {
                if (object.hasOwnProperty(key)) {
                    list.push(key);
                }
            }
            return list;
        },

        objectTypesSelector: function (value) {

            const gui = this;

            return this.buildSelector(
                this.arrayKeys(this.fields.object_types),
                value,
                "",
                function () {
                    gui.objectType = $(this).val();
                }
            );
        },
        pluginsSelector: function (value) {

            return $("<input>")
                .addClass("param-value form-control")
                .val(value);
        },
        paramsSelector: function (value) {

            return this.buildSelector(
                this.arrayKeys(this.plugins[value]),
                ''
            );
        },
        fieldsSelector: function (value) {
            let i, fields;
            if (this.objectType && this.fields.object_types.hasOwnProperty(this.objectType)) {
                fields = this.fields.object_types[this.objectType];
                if (this.trackerId && this.trackers[this.trackerId]) {
                    const generalFields = [], myTrackerFields = [], otherTrackerFields = [];
                    for (i in fields) {    // N.B. although fields.object_types are arrays here they appear as objects
                        if (fields.hasOwnProperty(i)) {
                            if (fields[i].indexOf("tracker_field_") === 0) {
                                if ($.inArray(fields[i], this.trackers[this.trackerId]) > -1) {
                                    myTrackerFields.push(fields[i]);
                                } else {
                                    otherTrackerFields.push(fields[i]);
                                }
                            } else {
                                generalFields.push(fields[i]);
                            }
                        }
                    }
                    fields = generalFields;
                    fields.push("--");
                    fields = fields.concat(myTrackerFields);
                    fields.push("--");
                    fields = fields.concat(otherTrackerFields);
                } else {
                    const fieldsArray = [];
                    for (i in fields) {    // convert object to an array
                        if (fields.hasOwnProperty(i)) {
                            fieldsArray.push(fields[i]);
                        }
                    }
                    fields = fieldsArray;
                }
            } else {
                for (const type in this.fields.object_types) {
                    if (this.fields.object_types.hasOwnProperty(type)) {
                        if ($.inArray(value, this.fields.object_types[type]) > -1) {
                            this.objectType = type;
                            fields = this.fields.object_types[type];
                        }
                    }
                }
            }

            if (fields) {
                fields.push("--");    // separator before globals if object specific fields are found
            } else {
                fields = [];
            }

            // always add globals
            for (i = 0; i < this.fields.global.length; i++) {
                if ($.inArray(this.fields.global[i], fields) < 0) {
                    fields.push(this.fields.global[i]);
                }
            }

            if (this.fields.formatted) {
                fields = fields.concat(this.fields.formatted);
            }

            return this.buildSelector(fields, value);
        },
        buildSelector: function (list, value, parentPath, changeFunction) {

            parentPath = parentPath || "";

            const $select = $("<select>")
                .addClass("form-control noselect2");

            $select.append(
                $("<option>").val("").text("")
            );

            for (const item in list) {
                if (list.hasOwnProperty(item)) {
                    if (list[item] !== "--") {
                        $select.append(
                            $("<option>")
                                .val(list[item])
                                .text(tr(list[item]))
                                .prop("selected", list[item] === value)
                                .data("path", parentPath + "/" + list[item])
                        );
                    } else {
                        $select.append("<option disabled>──────────</option>");
                    }
                }
            }

            if (changeFunction) {
                $select.on("change", changeFunction);
            }

            return $select;
        },
        buildDropDown: function (list, title, clickFunction, icon, plugin, parentPath) {
            if (!list.length) {
                return "";
            }

            icon = icon || "plus";
            title = title || tr("Add");
            clickFunction = clickFunction || function () {
            };
            plugin = plugin || "";
            parentPath = parentPath || "";

            const $li = $("<li>")
                    .addClass("nav-item dropdown")
                    .append(
                        $("<a data-bs-toggle='dropdown'>")
                            .addClass("nav-link dropdown-toggle")
                            //.data("bs-toggle", "dropdown")    n.b. bootstrap 5 doesn't get on with jQuery's data fn any more
                            .attr("title", tr("Add") + " " + title)
                            .attr("href", "#")
                            .append(
                                title,
                                $("<span>").getIcon(icon).addClass("pl-2")
                            )
                    ),
                $ul = $("<ul>")
                    .addClass("dropdown-menu")
                    .appendTo($li)
                    .append(
                        $("<li>")
                            .addClass("dropdown-header")
                            .text(title)
                    );

            for (const item in list) {
                if (list.hasOwnProperty(item)) {
                    $ul.append(
                        $("<a>")
                            .addClass("dropdown-item")
                            .data("value", list[item])
                            .data("plugin", plugin)
                            .data("path", parentPath + "/" + plugin + "/" + list[item])
                            .text(tr(list[item]))
                    );
                }
            }

            $(".dropdown-toggle", $li).dropdown();
            $li.find(".dropdown-menu a").on("click", function () {
                clickFunction.call(this);
                $(".dropdown-menu", $li).removeClass('show');
            });
            $(".dropdown-menu", $li).on("mouseleave", function () {
                $(".dropdown-menu", $li).removeClass('show');
            });

            return $li;
        },
        buildToolBar: function (plugins, parent, parentPath, clickFunction) {
            if (!plugins) {
                return "";
            }

            parent = parent || "";
            parentPath = parentPath || "";

            clickFunction = clickFunction || function () {};

            const $nav = $("<nav>").addClass("navbar bg-light w-100");
            const $ul = $("<ul>").addClass("navbar-nav me-auto");

            for (const plugin in plugins) {
                if (plugins.hasOwnProperty(plugin) && plugins[plugin].icon &&
                    ((!parent && !plugins[plugin].parents) || (parent && $.inArray(parent, plugins[plugin].parents) > -1))
                ) {
                    $ul.append(
                        this.buildDropDown(
                            this.arrayKeys(plugins[plugin].params),
                            plugin,
                            clickFunction,
                            plugins[plugin].icon,
                            plugin,
                            parentPath
                        )
                    );
                }
            }
            return $nav.append(
                $("<div>")
                    .addClass("container-fluid")
                    .append(
                        $("<div>")
                            .addClass("navbar-expand")
                            .append($ul)
                    )
            );
        }

    };

});

