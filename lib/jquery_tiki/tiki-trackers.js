// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
(function ($) {

    $.fn = $.extend($.fn, {
        /**
         * options:
         *     trackerId: int
         *     success: function (data) {}
         */
        tracker_add_field: function (options) {
            const dialog = this;
            $('select', dialog).on("change", function () {
                const descriptions = $(this).closest('.tracker-field-group').find('.form-text')
                    .hide();

                if ($(this).val()) {
                    descriptions
                        .filter('.' + $(this).val())
                        .show();
                }
            }).trigger("change");

            $('form', dialog).each(function () {
                const form = this;
                $(form.name).on("keyup", function () {
                    let val = $("#fieldPrefix").val() + " " + $(this).val();
                    val = removeDiacritics(val);
                    val = val.replace(/[^\w]+/g, '_');
                    val = val.replace(/_+([a-zA-Z])/g, function (parts) {
                        return parts[1].toUpperCase();
                    });
                    val = val.replace(/^[A-Z]/, function (parts) {
                        return parts[0].toLowerCase();
                    });
                    val = val.replace(/_+$/, '');

                    $(form.permName).val(val);
                });

                $(form.submit_and_edit).on("click", function () {
                    $(form.next).val('edit');
                });
            });
        },

        tracker_load_fields: function (trackerId) {
            const element = this;
            this.each(function () {
                const $container = $(this).empty();

                element.tikiModal(tr('Loading...'));

                $.getJSON($.service('tracker', 'list_fields'), {
                    trackerId: trackerId
                }, function (data) {
                    element.tikiModal();

                    $.each(data.fields, function (k, field) {
                        let $row = $('<tr/>').addClass("tracker-field-" + field.type);

                        $row.append($('<td class="checkbox-cell"/>').append($('<input type="checkbox" name="fields[]"/>').val(field.fieldId)));
                        $row.append($('<td class="id"/>')
                            .text(field.fieldId)
                            .append($('<input type="hidden" name="field~' + field.fieldId + '~position"/>').val(field.position))
                        );
                        const $nameLink = $('<a/>')
                            .text(field.name == null ? " " : field.name)
                            .attr('href', $.service('tracker', 'edit_field', {trackerId: trackerId, fieldId: field.fieldId, modal: 1}))
                            .clickModal({
                                success: function () {
                                    $.fn.resetFieldsCache();
                                    $container.tracker_load_fields(trackerId);
                                    $.closeModal();
                                }
                            });

                        const $nameCell = $('<td/>').prepend($nameLink);

                        if (field.encryptionKeyId) {
                            $nameCell.append(
                                $('<i class="fas fa-lock ms-1 text-warning-emphasis"/>')
                                    .attr('aria-label', tr('Encrypted'))
                            ).append(
                                $('<span class="badge text-bg-warning ms-1 small align-middle"/>')
                                    .text(tr('Encrypted'))
                            );
                        }

                        $nameCell.append(
                            $('<div class="small">')
                                .text(field.permName)
                                .append($(`<a href="javascript:void(0)" id="copy${field.fieldId}" class="text-info pl-1 tips small" title="${tr("Copy permanent name")}
                                            "><span class="icon fas fa-copy"/></a>`)
                                    .tiki('copy')(() => field.permName, function () {
                                        showMessage(tr('Copied to clipboard'), "success");
                                        $(this).animate({ opacity: .5 }, 200, function () {
                                            $(this).animate({ opacity: 1 }, 500);
                                        });
                                    })
                                )
                        );

                        $row.append($nameCell);

                        if (data.types[field.type]) {
                            $row.append($('<td/>').text(data.types[field.type].name));
                            if (field.options_map.mirrorField) {
                                $row.find('td').last().append($('<div class="small">').text(tr('Mirror field')+': '+field.options_map.mirrorField));
                            }

                            const addCheckbox = function (name, title, disabled = false) {
                                $row.append($('<td class="checkbox-cell"/>').append(
                                    $('<input type="checkbox" name="field~' + field.fieldId + '~' + name + '" value="1" title="' + title + '"/>')
                                        .prop('checked', field[name] === 'y').prop('disabled', disabled === true)
                                ));
                            };

                            if ($("#rulesColumn").length) {
                                if (field.rules) {
                                    let rulesString = "";

                                    // reformat the ruels for a basic preview, slightly prettier than raw JSON
                                    $.each(JSON.parse(field.rules), function (part, rule) {
                                        rulesString += tr(part);
                                        if (rule && rule.hasOwnProperty("predicates")) {
                                            rulesString += " (" + tr(rule.logicalType_id) + "):\n";
                                            rule.predicates.forEach(function (predicate) {
                                                rulesString += "    " + predicate.target_id + " " +
                                                    predicate.operator_id + " " +
                                                    (predicate.argument !== null ? "\"" + predicate.argument + "\"" : "") +
                                                    "\n";
                                            });
                                        } else {
                                            rulesString += ":\n";
                                        }
                                    });

                                    $row.append($('<td class="text-info" />').append(
                                            $().getIcon("ok")
                                                .attr("title", tr("Rules") + "|<pre>" + rulesString + "</pre>")
                                                .addClass("tips")
                                    ).tiki_popover());
                                } else {
                                    $row.append($('<td />'));
                                }
                            }

                            addCheckbox('isTblVisible', tr('List'));
                            addCheckbox('isMain', tr('Main'));
                            addCheckbox('isSearchable', tr('Searchable'), $.inArray(field.type, ['h']) !== -1);
                            addCheckbox('isPublic', tr('Public'));
                            addCheckbox('isMandatory', tr('Mandatory'));

                            $row.append($('<td class="action"/>')
                                .append($('<span class="d-inline-flex gap-2 align-items-center"/>')
                                    .append(
                                        $('<a class="text-info tips"/>')
                                            .attr('href', $.service('tracker', 'config_history', {trackerId: trackerId, fieldId: field.fieldId}))
                                            .attr('title', tr('Configuration History'))
                                            .append('<span class="icon fas fa-history"/>')
                                    )
                                    .append($('<a href="#" class="text-danger"><span class="icon fas fa-times"/></a>')
                                    .attr('href', $.service('tracker', 'remove_fields', {trackerId: trackerId, 'fields~0': field.fieldId}))
                                .on('click', function(e) {
                                    e.preventDefault();
                                    var $link = $(this);
                                    // Use confirmationDialog to bypass the native browser confirm
                                    $link.confirmationDialog({
                                        title: tr('Confirm Delete'),
                                        message: tr('Removing the field will result in data loss. Are you sure?'),
                                        success: function() {
                                            $.ajax($link.attr('href'), {
                                                type: 'POST',
                                                dataType: 'json',
                                                data: { 'confirm': 1 },
                                                success: function () {
                                                    $link.closest('tr').remove();
                                                    $.fn.resetFieldsCache();
                                                    feedback(tr("Field successfully removed."), "success");
                                                }
                                            });
                                        }
                                    });
                                })
                            )));
                        } else if (data.typesDisabled) {
                            if (data.typesDisabled[field.type]) {
                                $row.find('td').last()
                                    .append(' - <a class="ui-state-error" href="tiki-admin.php?lm_criteria=' + data.typesDisabled[field.type].prefs.join('+') + '&exact">' + tr('(Disabled, Click to Enable)') + '</a>');
                            }
                        }

                        $container.append($row);

                        if (data.duplicates && data.duplicates[field.fieldId]) {
                            $.each(data.duplicates[field.fieldId], function (k, v) {
                                $row = $('<tr class="bg-warning"/>');
                                $row.append($('<td colspan="2"/>'));
                                $row.append($('<td colspan="8"/>')
                                    .append(v.message)
                                );
                                $container.append($row);
                            });
                        }
                    });
                });
            });

            return this;
        },
        tracker_get_inputs_from_form: function() {
            let fields = {};
            let $form = $(this);

            // First, manually collect all checkbox values to handle multiple checkboxes with same name
            // This ensures we get all checked checkboxes even if they share the same name
            $form.find('input[type="checkbox"]:checked').each(function() {
                let name = $(this).attr('name');
                if (!name) {
                    return;
                }

                // Handle array notation (name[])
                if (name.substr(-2) == '[]') {
                    if (typeof fields[name] === 'undefined') {
                        fields[name] = [];
                    }
                    fields[name].push($(this).val());
                } else {
                    // For non-array checkboxes, collect all values with same name into array
                    if (typeof fields[name] === 'undefined') {
                        fields[name] = [];
                    }
                    fields[name].push($(this).val());
                }
            });

            // Then process all other form fields (non-checkboxes) using serializeArray
            $.each($form.serializeArray(), function() {
                // Skip checkboxes as we've already handled them above
                if ($form.find('input[type="checkbox"][name="' + this.name.replace(/"/g, '\\"') + '"]').length > 0) {
                    return;
                }

                if (this.name.substr(-2) == '[]') {
                    if (typeof fields[this.name] === 'undefined') {
                        fields[this.name] = [];
                    }
                    fields[this.name].push(this.value);
                } else {
                    // For fields that don't have checkboxes, use the value directly
                    // But check if we already have an array from checkboxes
                    if (typeof fields[this.name] !== 'undefined' && Array.isArray(fields[this.name])) {
                        // Already an array from checkboxes, skip or merge
                        return;
                    }
                    fields[this.name] = this.value;
                }
            });

            // Also collect hidden fields (for old_ values, etc.)
            $form.find('input[type="hidden"]').each(function() {
                let name = $(this).attr('name');
                if (!name) {
                    return;
                }

                // Skip if already collected
                if (typeof fields[name] !== 'undefined' && !Array.isArray(fields[name])) {
                    return;
                }

                if (name.substr(-2) == '[]') {
                    if (typeof fields[name] === 'undefined') {
                        fields[name] = [];
                    }
                    fields[name].push($(this).val());
                } else {
                    // For hidden fields with same name, collect into array
                    if (typeof fields[name] === 'undefined') {
                        fields[name] = [];
                    }
                    // Only add if not already in array (to avoid duplicates)
                    if ($.inArray($(this).val(), fields[name]) === -1) {
                        fields[name].push($(this).val());
                    }
                }
            });

            // Convert single-item arrays back to strings for non-checkbox fields
            // to maintain backward compatibility (except for fields we know should be arrays)
            for (let name in fields) {
                // Keep as array if:
                // 1. It ends with [] (explicit array notation)
                // 2. It's a checkbox field (we collected multiple checkboxes)
                // 3. It has multiple values
                if (Array.isArray(fields[name]) && fields[name].length === 1 && name.substr(-2) != '[]') {
                    // Check if this field had multiple checkboxes with this name
                    let checkboxCount = $form.find('input[type="checkbox"][name="' + name.replace(/"/g, '\\"') + '"]').length;
                    if (checkboxCount <= 1) {
                        // Single value, convert back to string for compatibility
                        fields[name] = fields[name][0];
                    }
                    // Otherwise keep as array since multiple checkboxes exist
                }
            }

            if ($form.data('ajax')) {
                fields['ajax'] = true;
            }

            return fields;
        },
        tracker_insert_item: function(options, fn) {
            $.extend( options, $(this).tracker_get_inputs_from_form() );

            $.tracker_insert_item(options, fn);

            return this;
        },
        tracker_remove_item: function(options, fn) {
            $.tracker_remove_item(options, fn);

            return this;
        },
        tracker_update_item: function(options, fn) {
            $.extend( options, $(this).tracker_get_inputs_from_form() );

            $.tracker_update_item(options, fn);

            return this;
        },
        tracker_get_item_inputs: function(options, fn) {
            $.tracker_get_item_inputs(options, fn);

            return this;
        }
    });

    $ = $.extend($, {
        tracker_insert_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'insert_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                fields: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_remove_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'remove_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_update_item: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'update_item',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                fields: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null),
            });
        },
        tracker_get_item_inputs: function(options, fn) {
            options = $.extend({
                controller: 'tracker',
                action: 'get_item_inputs',
                trackerId: 0,
                trackerName: '',
                itemId: 0,
                byName: false,
                defaults: {}
            }, options);

            $.ajax({
                url: 'tiki-ajax_services.php',
                dataType: 'json',
                data: options,
                type: 'post',
                success: (fn ? fn : null)
            });
        }
    });

    // Tracker import-export

    $(document).on('click', '.use-odbc', function() {
        if (this.checked) {
            $('.odbc-container').show();
        } else {
            $('.odbc-container').hide();
        }
    });

    $(document).on('click', '.use-api', function() {
        if (this.checked) {
            $('.api-container').show();
        } else {
            $('.api-container').hide();
        }
    });

    $(document).on('click', '.use-api-comments', function() {
        if (this.checked) {
            $('.api-comments-container').show();
        } else {
            $('.api-comments-container').hide();
        }
    });

    let unsavedChange = "border border-danger rounded border-2";

    let displaySaveBtn = function () {
        if ($('#btn-save-fields').hasClass('d-none')) {
            $('#tr-save-fields').removeClass('d-none');
            $('#btn-save-fields').removeClass('d-none');
        }
    };

    let highlightElement = function (el) {
        el.addClass(unsavedChange);
        feedback (tr('You have unsaved changes'), 'error', false, tr("Edit Format"), '', true);
        displaySaveBtn();
    };

    $(document).on('mouseenter', '.edit-tabular tbody:not(.ui-sortable) .icon-sort', function () {
        $(this)
            .closest("tbody")
            .filter(":not(.ui-sortable)")
            .each(function () {
                Sortable.create(this, {
                    handle: ".icon-sort",
                    onEnd: function (event) {
                        $(this.el).closest("table").trigger("tabular-update");
                        const draggedItem = $(event.item);
                        if (event.newIndex !== event.oldIndex) {
                            highlightElement(draggedItem);
                        }
                    },
                });
            });
    });

    $(document).on('change', '.edit-tabular .selection', function () {
        let value = $(this).val(), $add = $(this).closest('table').find('.add-field.from-select, .add-filter');

        if (! $add.data('original-href')) {
            $add.data('original-href', $add.attr('href'));
        }

        $add
            .attr('href', $add.data('original-href') + '&permName=' + value);
    });

    $(document).on('click', '.edit-tabular .add-field', $.clickModal({
        success: function (data) {
            let $row;
            if (typeof data.columnIndex !== "undefined") {
                $row = $(this).closest('table').find("tbody tr:not(.d-none)").eq(data.columnIndex);
            } else {
                $row = $(this).closest('table').find('tbody tr.d-none').clone().removeClass('d-none').appendTo($(this).closest('table').find('tbody'));
                highlightElement($row.find('.input-group'));
            }

            if ($('.mode', $row[0]).text() !== data.mode) {
                highlightElement($('.mode', $row[0]).parent('.add-field'));
            }

            $('.field-label', $row[0]).val(data.label);
            $('.field', $row[0]).text(data.field);
            $('.mode', $row[0]).text(data.mode);
            $('.unique-key', $row[0]).prop('checked', data.isUniqueKey);
            $('.read-only', $row[0]).prop('checked', data.isReadOnly);
            $('.export-only', $row[0]).prop('checked', data.isExportOnly);

            $(this).closest('table').trigger('tabular-update');

            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .add-filter', $.clickModal({
        success: function (data) {
            let $row = $(this).closest('table').find('tbody tr.d-none').clone().removeClass('d-none').appendTo($(this).closest('table').find('tbody'));

            $('.filter-label', $row[0]).val(data.label);
            $('.field', $row[0]).text(data.field);
            $('.mode', $row[0]).text(data.mode);

            $(this).closest('table').trigger('tabular-update');

            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .choose-applied-value', $.clickModal({
        success: function (data) {
            $(this).closest('tr').data('applied_value', data.applied_value);
            $(this).closest('table').trigger('tabular-update');
            $.closeModal();
        }
    }));

    $(document).on('click', '.edit-tabular .align-option', function (e) {
        e.preventDefault();
        let hash = this.href.substring(this.href.lastIndexOf('#') + 1);
        $(this).closest('tr')
            .find('.align').text($(this).text()).end()
            .find('.display-align').val(hash).end()
            ;

        $(this).closest('table').trigger('tabular-update');
    });

    $(document).on('click', '.edit-tabular .position-option', function (e) {
        e.preventDefault();
        const hash = this.href.substring(this.href.lastIndexOf('#') + 1);

        $(this).closest('tr')
            .find('.position-label').text($(this).text()).end()
            .find('.position').val(hash).end()
            ;

        $(this).closest('table').trigger('tabular-update');
    });

    $(document).on('click', '.edit-tabular .remove', function (e) {
        const $table = $(this).closest('table');

        e.preventDefault();
        $(this).closest('tr').remove();
        $table.trigger('tabular-update');
    });

    $(document).on('change', '.edit-tabular table :input', function (e) {
        if ($(this).closest('.inline-cypht').length > 0) {
            return;
        }
        $(this).closest('table').trigger('tabular-update');
        highlightElement($(this).closest('tr').find('td:first-child .input-group'));
    });

    $(document).on('click', '.btn.dropdown-toggle', function () {
        if ($(this).closest('.inline-cypht').length > 0) {
            return;
        }
        window.align = $('.align', this).text();
    });

    $(document).on('hide.bs.dropdown', '.btn.dropdown-toggle', function () {
        if ($(this).closest('.inline-cypht').length > 0) {
            return;
        }
        let currentAlign = $('.align', this).text();
        if (currentAlign !== window.align) {
            highlightElement($(this).closest('tr').find('td:first-child .input-group'));
        };

    });

    $(document).on('tabular-update', '.edit-tabular table.fields', function () {
        let data = [], count = 0;

        $('tbody tr:not(.d-none)', this).each(function () {
            const $link = $(this).find("a.add-field");

            if ($link.length) {
                $link.attr("href", $link.attr("href").replace(/columnIndex=\d+/, "columnIndex=" + count));
                count++;
            }

            data.push({
                label: $('.field-label', this).val(),
                field: $('.field', this).text(),
                mode: $('.mode', this).text(),
                remoteField: $('.remote-field', this).val(),
                type: $('.type', this).val(),
                displayAlign: $('.display-align', this).val(),
                isUniqueKey: $('.unique-key', this).is(':checked'),
                isReadOnly: $('.read-only', this).is(':checked'),
                isExportOnly: $('.export-only', this).is(':checked'),
                isPrimary: $('.primary', this).is(':checked')
            });
        });

        $('textarea', this).val(JSON.stringify(data));

        $(".edit-tabular").data("dirty", true);
    });

    $(document).on('tabular-update', '.edit-tabular table.filters', function () {
        let data = [];

        $('tbody tr:not(.d-none)', this).each(function () {
            data.push({
                label: $('.field-label', this).val(),
                field: $('.field', this).text(),
                mode: $('.mode', this).text(),
                position: $('.position', this).val(),
                applied_value: $(this).data('applied_value'),
            });
        });

        $('textarea', this).val(JSON.stringify(data));

        $(".edit-tabular").data("dirty", true);
    });

    if ($(".edit-tabular").length) {
        $(window).on("beforeunload", function () {
            return $(".edit-tabular").data("dirty");
        });
    }

    $(document).on('click', '.previewItemBtn', function (e) {
        var trackerForm = $(this).parents('form');
        if ($('#bootstrap-modal').hasClass('show')) {
            trackerForm = $(this).parents('div.modal-content').find('form');
        }
        var trackerFormId = '#' + trackerForm[0].id;
        var fields = {};

        $.each($('input, select, textarea, radio, img', trackerFormId), function(k) {
            let field = $(this);
            let fieldType = field.attr('type');
            let fieldName = field.attr('name');
            let fieldValue = field.val();

            if (fieldType == 'checkbox') {
                const multicheck = $('input[name="' + fieldName + '"]:checked', trackerFormId);
                fieldValue = [];

                multicheck.each(function () {
                    fieldValue.push($(this).val());
                });
            }
            if (fieldType == 'radio') {
                fieldValue = $('input[name="' + fieldName + '"]:checked', trackerFormId).val();
            }

            if (fieldName) {
                // Handle array fields (fields with [] in name)
                if (fieldName.endsWith('[]')) {
                    let baseFieldName = fieldName.replace('[]', '');
                    if (!fields[baseFieldName]) {
                        fields[baseFieldName] = [];
                    }
                    // Only add non-empty values to avoid empty placeholder values
                    if (fieldValue && fieldValue !== '') {
                        fields[baseFieldName].push(fieldValue);
                    }
                } else {
                    fields[fieldName] = fieldValue;
                }
            }
        });

        let options = [];

        options = $.extend({
            controller: 'tracker',
            action: 'preview_item',
            fields : fields
        }, options);

        $.ajax({
            url: 'tiki-ajax_services.php',
            data: options,
            type: 'post',
        })
        .done(function( html ) {
            $('.previewTrackerItem').empty();
            $('.previewTrackerItem').append(html);
            $('.previewTrackerItem').append('<hr/>');
            $('.previewTrackerItem').trigger("scroll");
            document.getElementsByClassName('previewTrackerItem')[0].scrollIntoView();
        });
    });

    // Global tracker field functions
    function updateTrackerFormSubmitState($input) {
        const $form = $input.closest('form');
        if (! $form.length) {
            return;
        }

        const hasInvalidUrlSyntax = $form.find('input[data-url-wiki-wrapper-validation-input]').filter(function () {
            return !! $(this).data('urlWikiSyntaxInvalid');
        }).length > 0;

        $form.find('input[type="submit"], button[type="submit"], .item-submit-btn').prop('disabled', hasInvalidUrlSyntax);
        $form.closest('.modal').find('.modal-footer .auto-btn').prop('disabled', hasInvalidUrlSyntax);
    }

    function validateUrlInput($input) {
        const rawValue = ($input.val() || '');
        const value = rawValue.trim();

        const feedbackId = $input.data('url-wiki-wrapper-feedback-id');
        const $feedback = feedbackId ? $('#' + feedbackId) : $();

        if (! $feedback.length) {
            return;
        }

        const defaultInfo = $feedback.data('default-info') || '';

        if (value === '') {
            $feedback
                .removeClass('text-warning')
                .addClass('text-muted')
                .text(defaultInfo);
            $input.data('urlWikiSyntaxInvalid', false);
            updateTrackerFormSubmitState($input);
            return;
        }

        const isWrappedDouble = value.startsWith('((') && value.endsWith('))');
        const isWrappedBracket = value.startsWith('[') && value.endsWith(']');
        const isWikiWrapped = isWrappedDouble || isWrappedBracket;
        let message = '';

        if (! isWikiWrapped) {
            const isRelativePath = value.startsWith('/');
            const hasScheme = /^[a-z][a-z0-9+.-]*:/i.test(value);
            const hasWhitespace = /\s/.test(value);
            const INVALID_URL_MESSAGE = tr('Invalid URL syntax');
            if (hasWhitespace || (! isRelativePath && ! hasScheme)) {
                message = INVALID_URL_MESSAGE;
            } else if (hasScheme) {
                try {
                    new URL(value);
                } catch (err) {
                    message = INVALID_URL_MESSAGE;
                }
            }
        }

        if (message) {
            $feedback
                .removeClass('text-muted')
                .addClass('text-warning')
                .text(message);
            $input.data('urlWikiSyntaxInvalid', true);
        } else {
            $feedback
                .removeClass('text-warning')
                .addClass('text-muted')
                .text(defaultInfo);
            $input.data('urlWikiSyntaxInvalid', false);
        }

        updateTrackerFormSubmitState($input);
    }

    $(document).on('input blur', 'input[data-url-wiki-wrapper-validation-input]', function () {
        validateUrlInput($(this));
    });

    $(document).on('tiki.modal.redraw', '.modal.fade', function () {
        $(this).find('input[data-url-wiki-wrapper-validation-input]').each(function () {
            validateUrlInput($(this));
        });
    });

    $(function () {
        $('input[data-url-wiki-wrapper-validation-input]').each(function () {
            validateUrlInput($(this));
        });
    });

    $(document).on('mouseenter', '.currency_output', function(){
      $('.'+$(this).attr('id')).removeClass('d-none');
    });
    $(document).on('mouseleave', '.currency_output', function(){
      $('.'+$(this).attr('id')).addClass('d-none');
    });

    $(document).on("click", ".math-override-toggle", function(e) {
        e.preventDefault();
        var $container = $(this).closest(".math-field-override");
        $container.find(".input-group").slideDown(150);
        $container.find("input").prop("disabled", false).trigger('focus');
        $(this).hide();
    });
    $(document).on("click", ".math-override-cancel", function() {
        var $container = $(this).closest(".math-field-override");
        var $input = $container.find("input");
        $input.val($input.data("original-value")).prop("disabled", true);
        $container.find(".input-group").slideUp(150);
        $container.find(".math-override-toggle").show();
    });

    $(document).on('hidden.bs.modal', function (event) {
        let m = window.location.href.match(/item(\d+)|itemId=(\d+)/);
        if (m) {
            let itemId = m[1] || m[2];
            $.getJSON($.service("semaphore", "unset"), {
                object_id: itemId,
                object_type: 'trackeritem'
            });
        }
    });

    // Multi-column sorting initializer for tracker item lists
    $.trackerInitMultiSort = function() {
        var currentSortModes = [];

        function getCurrentSortModesFromUrl() {
            var modes = [];
            var urlParams = new URLSearchParams(window.location.search);
            var allArrayParams = urlParams.getAll('sort_mode[]');
            if (allArrayParams && allArrayParams.length) {
                allArrayParams.forEach(function(v){ if (v) modes.push(v); });
            } else {
                var allSortParams = urlParams.getAll('sort_mode');
                if (allSortParams && allSortParams.length) {
                    allSortParams.forEach(function(v){ if (v) modes.push(v); });
                }
            }
            return modes;
        }

        function initSortModes() {
            currentSortModes = getCurrentSortModesFromUrl();
            updateSortIndicators();
        }

        function updateSortIndicators() {
            $('[data-sort-field]').find('.sort-indicator').remove();

            currentSortModes.forEach(function(sortMode, index) {
                var parts = sortMode.split('_');
                var field = parts[0];
                var direction;
                if (parts.length > 2 && parts[0] === 'f') {
                    field = parts[0] + '_' + parts[1];
                    direction = parts[2];
                } else {
                    direction = parts[1];
                }
                var header = $('[data-sort-field="' + field + '"]');
                if (header.length) {
                    var indicator = $('<span class="sort-indicator badge bg-info ms-1">' + (index + 1) + '</span>');
                    header.append(indicator);
                }
            });
        }

        // Expose the click handler for inline self_link usage
        window.handleSortClick = function(event, field) {
            if (event.ctrlKey || event.shiftKey) {
                event.preventDefault();

                currentSortModes = getCurrentSortModesFromUrl();
                var currentDirection = 'asc';
                var isCurrentSort = false;
                var existingIndex = -1;
                for (var i = 0; i < currentSortModes.length; i++) {
                    if (currentSortModes[i].indexOf(field + '_') === 0) {
                        existingIndex = i;
                        var parts = currentSortModes[i].split('_');
                        currentDirection = parts[parts.length - 1];
                        isCurrentSort = true;
                        break;
                    }
                }
                var newDirection = isCurrentSort ? (currentDirection === 'asc' ? 'desc' : 'asc') : 'asc';
                var sortMode = field + '_' + newDirection;

                if (existingIndex >= 0) {
                    currentSortModes[existingIndex] = sortMode;
                } else {
                    currentSortModes.push(sortMode);
                }

                // Build the URL with new sort parameters
                var url = new URL(window.location.href);
                // Clear all existing sort parameters (array and non-array)
                url.searchParams.delete('sort_mode');
                url.searchParams.delete('sort_mode[]');

                // Always use array format for multiple sorts
                currentSortModes.forEach(function(v){ url.searchParams.append('sort_mode[]', v); });

                window.location.href = url.toString();
                return false;
            }
            return true;
        };

        // Tooltip
        $('[data-sort-field]').attr('title', tr('Click to sort, Ctrl+click or Shift+click to add secondary sort'));

        // Initialize
        initSortModes();
    };

    $(function(){
        if ($('[data-sort-field]').length && typeof $.trackerInitMultiSort === 'function') {
            $.trackerInitMultiSort();
        }
    });

}(jQuery));
