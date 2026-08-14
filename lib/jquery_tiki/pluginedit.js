/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 *
 * Handles wiki plugin edit forms
 */


(function ($) {

    /* wikiplugin editor */
    window.popupPluginForm = function (area_id, type, index, pageName, pluginArgs, isMarkdown, bodyContent, edit_icon, selectedMod, textToReplace, extra) {

        if (!area_id) {
            area_id = $.noteEditorId;
        }
        var $textArea = $("#" + area_id);

        if ($textArea.length && $textArea[0].createTextRange) {    // save selection for IE
            storeTASelection();
        }

        var container = $('<div class="plugin"></div>');

        if (!index) {
            index = 0;
        }
        if (!pageName && jqueryTiki.current_object && jqueryTiki.current_object.type === "wiki page") {
            pageName = jqueryTiki.current_object.object;
        }
        var textarea = $textArea[0];
        var replaceText = false;

        if (!pluginArgs && !bodyContent) {
            pluginArgs = {};
            bodyContent = "";

            dialogSelectElement(area_id, '{' + type.toUpperCase(), '{' + type.toUpperCase() + '}');
            var sel = getTASelection(textarea);
            if (sel.match(/^\$\$tiki(.+)\$\$$/gs)) {
                sel = sel.replace(/^\$\$tiki$/mg, "");
                sel = sel.replace(/^\$\$$/mg, "");
            }

            if (sel && sel.length > 0) {
                sel = sel.replace(/^\s\s*/, "").replace(/\s\s*$/g, "");    // trim
                if (sel.length > 0 && sel.substring(0, 1) === '{') { // whole plugin selected
                    var l = type.length,
                        thisType = sel.match(/\{(\w+)/);
                    thisType = thisType[1].toUpperCase();
                    if (thisType === type.toUpperCase()) { // same plugin
                        var rx = new RegExp("{" + type + "[\\(]?([\\s\\S^\\)]*?)[\\)]?}([\\s\\S]*){" + type + "}", "mi"); // using \s\S matches all chars including lineends
                        sel = cleanString(sel)[0];
                        var m = sel.match(rx);
                        if (!m) {
                            rx = new RegExp("{" + type + "[\\(]?([\\s\\S^\\)]*?)[\\)]?}([\\s\\S]*)", "mi"); // no closing tag
                            m = sel.match(rx);
                        }
                        if (m) {
                            var paramStr = m[1];
                            bodyContent = m[2];

                            var pm = paramStr.match(/([^=]*)=\"([^\"]*)\"\s?/gi);
                            if (pm) {
                                for (var i = 0; i < pm.length; i++) {
                                    var ar = pm[i].split("=");
                                    if (ar.length) { // add cleaned vals to params object
                                        pluginArgs[ar[0].replace(/^[,\s\"\(\)\{\}]*/g, "")] = ar[1].replace(/^[,\s\"\(\)\{\}]*/g, "").replace(/[,\s\"\(\)\{\}]*$/g, "");
                                    }
                                }
                            }
                        }
                        replaceText = sel;
                    } else if (confirm("Click OK to include the " + thisType + " plugin inside a " + type.toUpperCase() + " plugin. Click Cancel to edit the " + thisType + " plugin.")) {
                        bodyContent = sel;
                        replaceText = true;
                    } else {
                        // different plugin, try again with the selected plugin type
                        window.popupPluginForm(area_id, thisType, index, pageName, null, isMarkdown, "", edit_icon, selectedMod);
                        return;
                    }
                } else { // not (this) plugin
                    if (type === 'mouseover') { // For MOUSEOVER, we want the selected text as label instead of body
                        bodyContent = '';
                        pluginArgs = {};
                        pluginArgs['label'] = sel;
                    } else {
                        bodyContent = sel;
                    }
                    replaceText = true;
                }
            } else {    // no selection
                replaceText = false;
            }
        } else {
            if (typeof pluginArgs === 'object' && pluginArgs !== null) {
                const regex = /[{}()]/g;
                Object.entries(pluginArgs).forEach(([key, value]) => {
                    pluginArgs = {...pluginArgs, [key]: value.replace(regex, "")};
                });
            }
        }

        if (! replaceText && textToReplace) {
            replaceText = textToReplace;
        }

        var $modal;
        if (selectedMod) {
            if (!!!edit_icon) {
                replaceText = getTASelection(textarea);
            }
            $modal = $('.footer-modal.show').first();    // if selecting a new module then reuse the existing modal
        } else {
            $modal = $('.footer-modal:not(.show)').first();
        }

        var url = $.service("plugin", "edit", {
            area_id: area_id,
            type: type,
            index: index,
            page: pageName,
            pluginArgs: pluginArgs,
            isMarkdown: isMarkdown,
            bodyContent: bodyContent,
            edit_icon: !!edit_icon ? 1 : 0,
            selectedMod: selectedMod ? selectedMod : "",
            modal: 1
        });

        // START BOOTSTRAP 4 CHANGE
        // Make the form load into the modal
        var prepareModal = function () {            // Bind remote loaded event
            const $modal = $(this);
            // enables conditional display of inputs with a "parentparam" selector
            handlePluginFieldsHierarchy();
            // bind form button events and form validation
            handleFormSubmit($modal, type, edit_icon, area_id, replaceText, extra);
            // Trigger jQuery event 'plugin_#type#_ready' (see plugin_code_ready in codemirror_tiki.js for example)
            $document
                .trigger({
                    type: 'plugin_' + type + '_ready',
                    container: container,
                    arguments: arguments,
                    modal: $modal
                })
                .trigger({
                    type: 'plugin_ready',
                    container: container,
                    arguments: arguments,
                    modal: $modal
                });

            if ($("form", this).length && edit_icon) {
                $modal.one("hidden.bs.modal", function () {
                    // unset semaphore on object/page on cancel
                    $.getJSON($.service("semaphore", "unset"), {
                        object_id: pageName
                    });
                });
            }
        };

        $.openModal({
            remote: url,
            size: "modal-lg",
            open: function () {
                prepareModal.call(this);
            },
        });

    };

    /*
    * Removes special characters { } ( ) from the plugin parameters
    */
    function cleanString(string) {
        const pattern = /\b(\w+)="(.*?)"/g;
        const matches = string.matchAll(pattern);
        const allParam = [];

        for (const match of matches) {
            var param = match[0];
            allParam.push(param);

            // Remove the special characters from the parameter.
            param = param.replace(/[{}()]/g, "");

            // Replace the parameter in the string.
            string = string.replace(match[0], param);
        }

        return [string, allParam];
    }

    /*
     * Hides all children fields in a wiki-plugin form and
     * add javascript events to display them when the appropriate
     * values are selected in the parentparam fields.
     */
    function handlePluginFieldsHierarchy() {
        var $container = $('#plugin_params');

        var parents = {};

        $("[data-parent_name]", $container).each(function () {
            var parentName = $(this).data("parent_name"),
                parentValue = $(this).data("parent_value");
            if (parentName) {
                var $parent = $('[name$="params[' + parentName + ']"]', $container);

                var $row = $(this).parents(".row");
                $row.addClass('parent_' + parentName + '_' + parentValue);

                if ($parent.val() !== parentValue) {
                    if (!$parent.val() && $("input, select", $row).val()) {
                        $parent.val(parentValue);
                    } else {
                        $row.hide();
                    }
                }

                if (!parents[parentName]) {
                    parents[parentName] = {
                        children: [],
                        parentElement: $parent
                    };
                }

                parents[parentName]['children'].push($(this).attr("id"));
            }
        });

        $.each(parents, function (parentName, parent) {
            parent.parentElement.on("change", function () {
                $.each(parent.children, function (index, id) {
                    $container.find('#' + id).parents(".row").hide();
                });
                $container.find('.parent_' + parentName + '_' + this.value).show();
            })
                .trigger("change");
        });
    }

    /**
     * set up insert/replace button and submit handler in "textarea" edit mode
     *
     * @param container
     * @param type
     * @param edit_icon
     * @param area_id
     * @param replaceText
     * @param extra
     */
    function handleFormSubmit(container, type, edit_icon, area_id, replaceText, extra) {

        var params = [], viewPageMode = !!edit_icon, bodyContent = "";

        var $form = $("form", container);

        $form.on("submit", function () {
            if (typeof process_submit === "function" && ! process_submit(this)) {
                return false;
            }

            if (type === "list" && ! viewPageMode && typeof jqueryTiki.plugins.list.saveToTextarea === "function") {
                jqueryTiki.plugins.list.saveToTextarea();
            }

            $("[name^=params]", $form).each(function () {
                var name = $(this).attr("name"),
                    matches = name.match(/params\[(.*)\]/),
                    val = $(this).val();

                if (!matches) {
                    if (name === "content") {
                        bodyContent = $(this).val();
                    }
                    return;
                }

                if (val && ! viewPageMode) {
                    // Handle multi-select values
                    if (Array.isArray(val)) {
                        var $element = $(this);
                        var separator = ',';  // default separator
                        // Check if this is within object-selector-options
                        if ($element.closest('.object-selector-options').length) {
                            separator = $element.attr('data-separator') || ',';
                        }
                        val = val.join(separator);
                    }
                    val = val.toString();
                    val = val.replace(/"/g, '\\"');    // escape double quotes
                    params.push(matches[1] + '="' + val + '"');
                }
            });

            var blob, pluginContentTextarea = $("[name=content]", $form),
                pluginContentTextareaEditor = syntaxHighlighter.get(pluginContentTextarea);

            if (! viewPageMode) {
                if (!bodyContent) {
                    bodyContent = (pluginContentTextareaEditor ? pluginContentTextareaEditor.getValue() : pluginContentTextarea.val());
                }
                if (bodyContent) {
                    blob = '{' + type.toUpperCase() + '(' + params.join(' ') + ')}' + bodyContent + '{' + type.toUpperCase() + '}';
                } else {
                    blob = '{' + type.toLowerCase() + (params.length ? ' ' : '') + params.join(' ') + '}';
                }

                insertAt(area_id, blob, false, false, replaceText, extra);
                const textareaModal = $(`#${area_id}`).closest('.modal');
                if (textareaModal.length) {
                    $.closeModal({ downTo: Number(textareaModal.attr("counter")) + 1 });
                } else {
                    $.closeModal({ all: true });
                }

                return false;
            }
            return true;
        });
    }

    $('body').on('click', '.card.plugin-link', function() {
        const pluginName = $(this).data('plugin-name');
        const areaId = $(this).data('area-id');
        popupPluginForm(areaId, pluginName);
    });

})(jQuery);
