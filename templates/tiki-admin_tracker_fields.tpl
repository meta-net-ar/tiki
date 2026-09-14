{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title help="Adding fields to a tracker" url="`$trackerId|sefurl:'trackerfields'`"}{tr}Tracker Fields:{/tr} {tr}{$tracker_info.name}{/tr}{/title}
{/block}

{block name="navigation"}
    {$title="{tr}Admin Tracker:{/tr} "|cat:$tracker_info.name|escape}
    <div class="t_navbar mb-4">
        <div class="btn-group">
            <a href="{service controller=tracker action=add_field trackerId=$trackerId}" class="btn btn-primary add-field">{icon name="create"} {tr}Add Field{/tr}</a>
            <a href="{bootstrap_modal controller=tracker action=import_fields trackerId=$trackerId}" class="btn btn-primary">{icon name="import"} {tr}Import Fields{/tr}</a>
            <a href="{service controller=tracker action=config_history trackerId=$trackerId}" class="btn btn-outline-info">{icon name="history"} {tr}Config History{/tr}</a>
        </div>
        {include file="tracker_actions.tpl"}
    </div>
{/block}

{block name="content"}
    <form class="form save-fields" method="post" action="{service controller=tracker action=save_fields}">
    <div class="table-responsive">
        <table id="fields" class="table table-condensed table-hover">
            <thead>
                <tr>
                    <th>{select_all checkbox_names="fields[]"}</th>
                    <th>{tr}ID{/tr}</th>
                    <th>{tr}Name{/tr}</th>
                    <th>{tr}Type{/tr}</th>
                    {if $prefs.tracker_field_rules eq 'y'}
                        <th id="rulesColumn">{tr}Rules{/tr}</th>
                    {/if}
                    <th>{tr}List{/tr}
                    <a class="tikihelp text-info tikihelp-prefs" title="{tr}List{/tr}" aria-label="{tr}Help{/tr}" data-bs-original-title="{tr}Description of List{/tr}" data-bs-content="{tr}Make the column visible when listing tracker items{/tr}" data-bs-trigger="hover focus" tabindex="0" data-mobile-tooltip="">
                        <span class="icon icon-information fas fa-info-circle "></span>
                    </a>
                    </th>
                    <th>{tr}Title{/tr}
                    <a class="tikihelp text-info tikihelp-prefs" title="{tr}Title{/tr}" aria-label="{tr}Help{/tr}" data-bs-original-title="{tr}Description of Title{/tr}" data-bs-content="{tr}This column's content displays as a link which redirects to the content of the item{/tr}" data-bs-trigger="hover focus" tabindex="0" data-mobile-tooltip="">
                        <span class="icon icon-information fas fa-info-circle "></span>
                    </a>
                    </th>
                    <th>{tr}Search{/tr}
                    <a class="tikihelp text-info tikihelp-prefs" title="{tr}Search{/tr}" aria-label="{tr}Help{/tr}" data-bs-original-title="{tr}Description of Search{/tr}" data-bs-content="{tr}Add the ability to search and retrieve specific information from a column in a table{/tr}.<br>
{tr}Essentially, this means that the data within that column can be easily found using search filters{/tr}.<br>" data-bs-trigger="hover focus" tabindex="0" data-mobile-tooltip="">
                        <span class="icon icon-information fas fa-info-circle "></span>
                    </a>
                    </th>
                    <th>{tr}Public{/tr}
                    <a class="tikihelp text-info tikihelp-prefs" title="{tr}Public{/tr}" aria-label="{tr}Help{/tr}" data-bs-original-title="{tr}Description of Public{/tr}" data-bs-content="{tr}Hides or reveals columns displayed using the trackerlist plugin{/tr}" data-bs-trigger="hover focus" tabindex="0" data-mobile-tooltip="">
                        <span class="icon icon-information fas fa-info-circle "></span>
                    </a>
                    </th>
                    <th>{tr}Mandatory{/tr}
                    <a class="tikihelp text-info tikihelp-prefs" title="{tr}Mandatory{/tr}" aria-label="{tr}Help{/tr}" data-bs-original-title="{tr}Description of Mandatory{/tr}" data-bs-content="{tr}The field must contain a value when saving an item{/tr}" data-bs-trigger="hover focus" tabindex="0" data-mobile-tooltip="">
                        <span class="icon icon-information fas fa-info-circle "></span>
                    </a>
                    </th>
                    <th>{tr}Actions{/tr}</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        </div>
        <div class="mb-3 row">
            <div class="input-group col-sm-6">
                <select name="action" class="form-control">
                    <option value="save_fields">{tr}Save All{/tr}</option>
                    <option value="remove_fields">{tr}Remove Selected{/tr}</option>
                    <option value="export_fields">{tr}Export Selected{/tr}</option>
                </select>
                <input type="hidden" name="trackerId" value="{$trackerId|escape}">
                <input type="hidden" name="confirm" value="0">
                <button type="submit" class="btn btn-primary" name="submit">{tr}Go{/tr}</button>
            </div>
        </div>
    </form>

    {jq}
        var trackerId = {{$trackerId|escape}};
        $('.save-fields').on("submit", function () {
            var form = this;
            var action = $(form.action).val();

            // Internal helper to perform the Ajax submission logic
            var executeAjax = function () {
                $.ajax($(form).attr('action'), {
                    type: 'POST',
                    data: $(form).serialize(),
                    dataType: 'json',
                    success: function () {
                        $container.tracker_load_fields(trackerId);
                        if (action === 'remove_fields') {
                            $.fn.resetFieldsCache();
                            feedback(tr('Fields successfully removed.'), 'success');
                        } else if (action === 'save_fields') {
                            feedback(tr('Fields successfully saved.'), 'success');
                        }
                    }
                });
            };
            if (action === 'remove_fields') {
                if (validateForm()) {
                    $(form).confirmationDialog({
                        title: tr('Delete Fields'),
                        message: tr('Do you really want to delete the selected fields?'),
                        success: function() {
                            $(form.confirm).val('1');
                            executeAjax();
                        }
                    });
                }
                return false;
            }

            if ($(form.action).val() === 'export_fields') {
                if (validateForm()) {
                    var url = $.serviceUrl({ controller: 'tracker', action: 'export_fields' });
                    var target = $('.modal.fade:not(.in)').first();
                    $.post(url, $(form).serialize() + '&modal=1', function (data) {
                        $(".modal-content", target).html(data);
                        target.modal('show');
                    });
                }
                return false;

            } else {
                executeAjax();
            }
            return false;
        });
        const $container = $('.save-fields tbody');

        Sortable.create($container.get(0), {
            onUpdate: function (event) {
                const rows = event.to.querySelectorAll('tr');
                rows.forEach((row, index) => {
                    row.querySelector('td.id > input').value = index;
                });
            }
        });

        $container.tracker_load_fields(trackerId);

        $('.add-field').clickModal({
            open: function () {
                $(this).tracker_add_field({
                    trackerId: trackerId
                });
            },
            success: function (data) {
                $container.tracker_load_fields(trackerId);
                $.fn.resetFieldsCache();

                $.closeModal({
                    done: function () {
                        if (! data.FORWARD) {
                            return false;
                        }

                        setTimeout(function () {
                            $.openModal({
                                remote: $.service(data.FORWARD.controller, data.FORWARD.action, data.FORWARD)
                            });
                        }, 0);
                    }
                });
            }
        });

        $('.import-fields').on("submit", function () {
            var form = this;
            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                data: $(form).serialize(),
                success: function () {
                    $container.tracker_load_fields(trackerId);
                    $('textarea', form).val('');
                    tikitabs(1);
                }
            });

            return false;
        });

        var trackerFieldsUrl;

        $('.tracker-properties').clickModal({
            open: function () {
                var element = $("#fieldsDetails textarea");
                if (element) {
                    trackerFieldsUrl = lookupUrl(element);
                }
            },
        });

        function lookupUrl(selector) {
            var input = selector
                , filter = $(input).data('filters')
                , threshold = $(input).data('threshold')
                , format = $(input).data('format') || ''
                , sort = $(input).data('sort') || 'score_desc';

            var args = {
                maxRecords: threshold,
                format: format,
                sort_order: sort
            };

            var url;

            url = $.service('search', 'lookup', $.extend(args, {
                filter: filter
            }));

            return url;
        }

        $.fn.resetFieldsCache = function () {
            if (trackerFieldsUrl) {
                if ($.object_selector_cache && $.object_selector_cache.hasOwnProperty(trackerFieldsUrl)) {
                    delete $.object_selector_cache[trackerFieldsUrl];
                    trackerFieldsUrl = '';
                }
            }
        };
        function validateForm() {
            // Get all checkbox elements with the name "fields[]"
            const checkboxes = document.querySelectorAll('input[name="fields[]"]');

            // Check if at least one checkbox is checked
            let isChecked = false;
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    isChecked = true;
                    return;
                }
            });

            // If no checkbox is checked, show an alert and prevent submission
            if (!isChecked) {
                feedback(tr("Please select at least one element."), "error");
                return false;
            }

            // If at least one checkbox is checked, allow form submission
            return true;
        }
    {/jq}
{/block}
