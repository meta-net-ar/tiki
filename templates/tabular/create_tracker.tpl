{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="nav d-inline-flex">
        {permission name=admin_trackers}
            <a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
        {/permission}
    </div>
{/block}

{block name="content"}

    <form class="edit-tabular" method="post" action="{service controller=tabular action=create_tracker}" enctype="multipart/form-data">
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3" for="name">{tr}Name{/tr}</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" name="name" id="name" maxlength="255" required>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3" for="tracker_name">{tr}Tracker Name{/tr}</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" name="tracker_name" id="tracker_name" maxlength="255" required>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3" for="delimiter">{tr}File Delimiter{/tr}</label>
            <div class="col-sm-9">
                <select class="form-select file-delimiter">
                    <option value="comma" selected>{tr}Comma (,){/tr}</option>
                    <option value="semicolon">{tr}Semicolon (;){/tr}</option>
                </select>
                <input type="hidden" id="delimiter" name="delimiter" value="comma">
            </div>
        </div>
        <div class="tiki-form-group row file-container">
            <label class="col-form-label col-sm-3" for="file">{tr}File{/tr}</label>
            <div class="col-sm-9">
                <input type="file" name="file" id="file" accept="text/csv" class="form-control" required>
                <div id="file-size-error" class="alert alert-danger mt-3" style="display:none">
                    {tr _0=$config.upload_max_filesize}<strong>Error:</strong> Selected file has <span id="file-size"></span> bytes. The max file size upload is %0 bytes.{/tr}
                </div>
            </div>
        </div>
        <div id="tracker-columns" class="tiki-form-group row" style="display:none">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table fields">
                        <thead>
                        <tr>
                            <th>{tr}Field{/tr}</th>
                            <th>{tr}Field Type{/tr}</th>
                            <th><abbr title="{tr}Primary Key{/tr}">{tr}PK{/tr}</abbr></th>
                            <th><abbr title="{tr}Unique Key{/tr}">{tr}UK{/tr}</abbr></th>
                            <th><abbr title="{tr}Read-Only{/tr}">{tr}RO{/tr}</abbr></th>
                            <th><abbr title="{tr}Export-Only{/tr}">{tr}EO{/tr}</abbr></th>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr id="row-template" class="d-none" style="display: none;">
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="field-label form-control" readonly/>
                                    <div class="input-group-text">
                                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="align">{tr}Left{/tr}</span>
                                            <input class="display-align" type="hidden" value="left">
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" role="menu">
                                            <a class="dropdown-item align-option" href="#left">{tr}Left{/tr}</a>
                                            <a class="dropdown-item align-option" href="#center">{tr}Center{/tr}</a>
                                            <a class="dropdown-item align-option" href="#right">{tr}Right{/tr}</a>
                                            <a class="dropdown-item align-option" href="#justify">{tr}Justify{/tr}</a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="tiki-form-group">
                                <select class="form-select type">
                                    {foreach from=$types key=k item=info}
                                        <option value="{$k}" {if $k eq 't'}selected{/if}>{$info.name|escape} {if !empty($info.deprecated)}- Deprecated{/if}</option>
                                    {/foreach}
                                </select>
                                {*<ul class="dropdown-menu dropdown-menu-end" role="menu">*}
                                    {**}
                                {*</ul>*}

                            </td>
                            <td><input class="primary" type="radio" name="pk" required aria-label="{tr}Primary Key:{/tr}" /></td>
                            <td><input class="unique-key form-check-input" type="checkbox" aria-label="{tr}Unique Key:{/tr}" /></td>
                            <td><input class="read-only form-check-input" type="checkbox" aria-label="{tr}Read-only:{/tr}" /></td>
                            <td><input class="export-only form-check-input" type="checkbox" aria-label="{tr}Export-Only{/tr}" /></td>
                            <td class="text-end"><button class="remove" aria-label="{tr}Remove{/tr}">{icon name=remove}</button></td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="5">
                                <textarea name="fields" class="d-none" aria-label="{tr}Fields{/tr}"></textarea>
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="form-text">
                    <p><strong>{tr}Primary Key:{/tr}</strong> {tr}Required to import data. Can be any field as long as it is unique.{/tr}</p>
                    <p><strong>{tr}Unique Key:{/tr}</strong> {tr}Impose unique value requirement for the target column. This only works with Transactional Import feature.{/tr}</p>
                    <p><strong>{tr}Read-only:{/tr}</strong> {tr}When importing a file, read-only fields will be skipped, preventing them from being modified, but also speeding-up the process.{/tr}</p>
                    <p>{tr}When two fields affecting the same value are included in the format, such as the ID and the text value for an Item Link field, one of the two fields must be marked as read-only to prevent a conflict.{/tr}</p>
                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="form-check-label col-sm-3" for="config-simple_headers">{tr}Simple headers{/tr} <a class="tikihelp" title="{tr}Simple headers:{/tr} {tr}Allow using field labels only as a header row when importing rather than the full &quot;Field [permName:type]&quot; format.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <div class="form-check">
                    <input type="hidden" name="config[simple_headers]" value="1">
                    <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" id="config-simple_headers" value="1" checked disabled>

                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="form-check-label col-sm-3" for="config-import_update">{tr}Import updates{/tr} <a class="tikihelp" title="{tr}Import update:{/tr} {tr}Allow updating existing entries matched by PK when importing. If this is disabled, only new items will be imported.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="config[import_update]" id="config-import_update" value="1" {if $config['import_update']} checked {/if}>

                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="form-check-label col-sm-3" for="config-ignore_blanks">{tr}Ignore blanks{/tr} <a class="tikihelp" title="{tr}Ignore blanks:{/tr} {tr}Ignore blank values when import is updating existing items. Only non-blank values will be updated this way.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="config[ignore_blanks]" id="config-ignore_blanks" value="1" {if $config['ignore_blanks']} checked {/if}>

                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="form-check-label col-sm-3" for="config-import_transaction">{tr}Transactional import{/tr} <a class="tikihelp" title="{tr}Import transaction:{/tr} {tr}Import in a single transaction. If any of the items fails validation, the whole import is rejected and nothing is saved.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="config[import_transaction]" id="config-import_transaction" value="1" {if $config['import_transaction']} checked {/if}>

                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="form-check-label col-sm-3" for="config-bulk_import">{tr}Bulk import{/tr} <a class="tikihelp" title="{tr}Bulk Import:{/tr} {tr}Import in 'bulk' mode so the search index is not updated for each item and no notifications should be sent.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="config[bulk_import]" id="config-bulk_import" value="1" {if $config['bulk_import']} checked {/if}>

                </div>
            </div>
        </div>
        <div class="tiki-form-group submit">
            <div class="col-sm-9 offset-sm-3">
                <input type="submit" class="btn btn-primary" value="{tr}Import{/tr}">
            </div>
        </div>
    </form>

    {jq}
        var uploadMaxFileSize = {{$config.upload_max_filesize}};
        var submitFormButton = $('input[type="submit"]');
        var fileSelect = $('input[name="file"]');
        var delimiterElement = $("#delimiter");
        var delimiter = ',';

        $('.file-delimiter').on('change', function(e) {
            var currentValue = $(this).val();
            delimiter = ((currentValue == 'comma') ? ',' : ';');
            delimiterElement.val(currentValue);
            fileSelect.trigger("change");
        });

        fileSelect.on('change', function(e) {

            var fileUpload = fileSelect[0];

            // remove existing elements
            $('#tracker-columns').find('tr[id^="row"]:not(".d-none")').each(function(){
                $(this).remove();
            });

            if (!fileUpload.value) {
                $('#tracker-columns').hide();
                $('#tracker-columns').find('table').trigger('tabular-update');
            }

            if (typeof (FileReader) != "undefined" && fileUpload.value != '') {

                var reader = new FileReader();
                var file = e.target.files[0];
                var fileSize = file.size;


                reader.onload = function (e) {
                    if (delimiter == ';') {
                        var rows = Plotly.d3.dsv(';').parse(e.target.result);
                    } else {
                        var rows = Plotly.d3.csv.parse(e.target.result);
                    }
                    var columns = Object.keys(rows[0]);

                    for(i = 0; i < columns.length; i++) {
                        var $elem = $('#row-template').clone();
                        $elem.attr('id', 'row'+i);
                        $elem.removeClass('d-none');
                        $elem.find('input[type="text"]').val(columns[i]);
                        $elem.show();
                        $('#tracker-columns table tbody').append($elem);
                    }

                    $('#tracker-columns').show();
                    $('#tracker-columns').find('table').trigger('tabular-update');
                }

                reader.readAsText(fileUpload.files[0]);

                if (fileSize <= uploadMaxFileSize) {
                    submitFormButton.prop('disabled', false);
                    $('#file-size-error').hide();
                } else {
                    submitFormButton.prop('disabled', true);
                    $('#file-size').html(fileSize);
                    $('#file-size-error').show();
                }
            }
        });

    {/jq}
{/block}
