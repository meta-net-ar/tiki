{if empty($iListExecute)}{assign var=iListExecute value=$id|replace:'wplistexecute-':''}{/if}
{if ! empty($downloadable) && ! empty($downloadabletop) && $downloadabletop == 'y'}
    <form method="post" id="listexecute-download-top-{$iListExecute}">
        <input type="hidden" name="download" value="1">
        <input type="hidden" name="listId" value="{$iListExecute}">
        <input type="hidden" name="tsAjax" value="y">
        <input type="submit" name="submit" value="{tr}Download{/tr}" class="btn btn-primary">
    </form>
{jq}
(function(){
    $('#listexecute-download-top-{{$iListExecute}}').on("submit", function(){
        var $form = $(this);
        $form.find('input[name^=filter]').remove();
        $('.tablesorter-filter').each(function(i,el){
            var column = $(el).data('column'),
                    value = $(el).val();
            if( value ) {
                $('<input type="hidden" name="filter['+column+']">')
                    .val(value)
                    .appendTo($form);
            }
        });
        var m = "{{$id}}".match(/wpcs\-(\d+)$/);
        var id = m ? m[1] : null;
        var cs = window['customsearch_'+id];
        if (cs) {
            $form.attr('action', $.service('search_customsearch', 'customsearch'));
            var datamap = {
                definition: cs.definition,
                adddata: $.toJSON(cs.searchdata),
                searchid: cs.id,
                offset: cs.offset,
                maxRecords: cs.maxRecords,
                store_query: cs.store_query
            }
            $.each(datamap, function(k, v) {
                $('<input type="hidden">').attr('name', k).val(v).appendTo($form);
            });
        }
    });
})();
{/jq}
{/if}
{if isset($tableparams.title)}
    <div class="list-table-heading">{wiki}{$tableparams.title|escape}{/wiki}</div>
{/if}
{$showFacets = not empty($facets) and isset($tableparams.facets) and $tableparams.facets eq 'y'}
{if $showFacets}
{*    <pre>{$facets|var_dump}</pre>*}
{*    <pre>{$results|var_dump}</pre>*}
    <div class="row">
        <div class="col-sm-2">
            <div class="facets filters" id="filters">
                {foreach $facets as $facetField => $facet}
                    {if count($facet.options) gt 0}
                        <div class="clearfix margin-bottom-sm">
                            <label class="h4 other">{$facet.label|replace:' (Tree)':''|tr_if|escape}</label>
                            <ul data-for="#{$facet.name}" data-join="{$facet.operator|escape}">
                                {foreach from=$facet.options key=value item=label}
                                    <li>
                                        <label>
                                            {if not empty($adddata)}<input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" value="{$value|escape}">{/if}
                                            {$label|escape}
                                        </label>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    {/if}
                {/foreach}
            </div>
        </div>
        <div class="col-sm-10">{* for the table *}
        {*    close these after the table </div></div>*}
        {if not empty($adddata)}
            {jq}
                $.applySelect2();
                $('.facets ul').registerFacet();
            {/jq}
        {/if}
{/if}
{if $actions}
<form method="post" action="#{$id}" class="d-flex flex-row flex-wrap align-items-center" id="listexecute-{$iListExecute}">
{/if}
{if not empty($column.field)}
    {$column = [$column]}{* if there is only one column then it will not be in an array *}
{/if}
{if isset($tableparams.allowtableexpansion) && $tableparams.allowtableexpansion eq 'y'}
    {button href='javascript:void(0)' _type="primary" _class="btn btn-primary btn-sm table-expand-toggle" _icon_name="caret-square-right" _title="{tr}Expand table{/tr}"}
    {jq}
        $(".table-expand-toggle").on("click", function(){
            var $this = $(this);
            if ( $this.data('expandStatus') != 'expanded' ) {
                $this.data('expandStatus','expanded');
                var $parentdiv = $(this).parent('div');
                $parentdiv.find('div.table-responsive').each(function () {
                    $(this).removeClass('table-responsive').addClass('table');
                }); // end each
                $this.attr('title','{tr}Restore layout{/tr}');
                $this.find(".icon").setIcon("caret-square-left");
            }else{
                $this.data('expandStatus','responsive');
                var $parentdiv = $(this).parent('div');
                $parentdiv.find('div.table').each(function () {
                    $(this).addClass('table-responsive').removeClass('table');
                }); // end each
                $this.attr('title','{tr}Expand table{/tr}');
                $this.find(".icon").setIcon("caret-square-right");
            }
        });
    {/jq}
{/if}
{if isset($tableparams.shownbitems) && $tableparams.shownbitems eq 'y'}
    <div class="nbitems">
        {tr}Items found:{/tr} <span class='badge bg-secondary'>{$count}</span>
    </div>
{/if}
<div {if $id}id="{$id}-div" {/if}class="table-responsive ts-wrapperdiv {if $sticky}table-sticky{/if}" {if $tsOn}style="visibility:hidden;"{/if}>
    <table {if $id}id="{$id}" {/if}class="table normal table-hover table-striped" data-count="{$count}">
        <thead class="{if $sticky}bg-body-tertiary{/if}">
        {$header=false}
        {if isset($column)}
            {foreach from=$column item=col}
                {if !empty($col.label) or !empty($col.sort)}
                    {$header=true}
                    {break}
                {/if}
            {/foreach}
        {/if}
        {if $header}
            {$fieldcount = 0}
            <tr>
                {if $actions}
                    {$fieldcount = 1}
                    <th>
                        <input type="checkbox" class="form-check-input listexecute-select-all" aria-label="{tr}Select{/tr}" name="selectall" value="">
                        <input type="hidden" name="objects{$iListExecute}[]" value="" class="listexecute-all">
                    </th>
                {/if}
                {if isset($column)}
                    {foreach from=$column item=col}
                        {$fieldcount = $fieldcount + 1}
                        <th{if not empty($col.class)} class="{$col.class}"{/if}>
                            {if isset($col.sort) && $col.sort}
                                {if !empty($sort_jsvar) and !empty($_onclick)}
                                    {$order = '_asc'}
                                    {if !empty($smarty.request.sort_mode) and stristr($smarty.request.sort_mode, $col.sort) neq false}
                                        {if stristr($smarty.request.sort_mode, '_asc')}
                                            {$order = '_desc'}
                                        {elseif stristr($smarty.request.sort_mode, '_nasc')}
                                            {$order = '_ndesc'}
                                        {elseif stristr($smarty.request.sort_mode, '_desc')}
                                            {$order = '_asc'}
                                        {elseif stristr($smarty.request.sort_mode, '_ndesc')}
                                            {$order = '_nasc'}
                                        {/if}
                                    {/if}
                                    {$click = $sort_jsvar|cat:'=\''|cat:$col.sort|cat:$order|cat:'\';'|cat:$_onclick}
                                    {if isset($col.translatelabel) && $col.translatelabel == 'y'}
                                        {self_link _onclick=$click _ajax='y' _sort_arg='sort_mode' _sort_field=$col.sort}{$col.label|tra|escape}{/self_link}
                                    {else}
                                        {self_link _onclick=$click _ajax='y' _sort_arg='sort_mode' _sort_field=$col.sort}{$col.label|escape}{/self_link}
                                    {/if}
                                {else}
                                    {if isset($col.translatelabel) && $col.translatelabel == 'y'}
                                        {self_link _sort_arg=$sort_arg _sort_field=$col.sort}{$col.label|tra|escape}{/self_link}
                                    {else}
                                        {self_link _sort_arg=$sort_arg _sort_field=$col.sort}{$col.label|escape}{/self_link}
                                    {/if}
                                {/if}
                            {else}
                                {if isset($col.translatelabel) && $col.translatelabel == 'y'}
                                    {$col.label|tra|escape}
                                {else}
                                    {$col.label|escape}
                                {/if}
                            {/if}
                        </th>
                    {/foreach}
                {/if}
            </tr>
        {/if}
        </thead>
        <tbody>
        {foreach from=$results item=row}
            <tr>
                {if $actions}
                    <td>
                        <input type="checkbox" name="objects{$iListExecute}[]" class="checkbox_objects form-check-input" aria-label="{tr}Select{/tr}" value="{$row.object_type|escape}:{$row.object_id|escape}">
                        {if $row.report_status eq 'success'}
                            {icon name='ok'}
                        {elseif $row.report_status eq 'error'}
                            {icon name='error'}
                        {/if}
                    </td>
                {/if}
                {if isset($column)}
                    {foreach from=$column item=col}
                        <td{if not empty($col.class)} class="{$col.class}"{/if}>
                            {if isset($col.mode) && $col.mode eq 'raw'}
                                {if !empty($row[$col.field])}{$row[$col.field]|strip}{/if}
                            {else}
                                {if !empty($row[$col.field])}{$row[$col.field]|escape|strip}{/if}
                            {/if}
                        </td>
                    {/foreach}
                {/if}
            </tr>
        {/foreach}
        </tbody>
        {if !empty($tstotals) && $tsOn}
            {include file="../../tablesorter/totals.tpl" fieldcount="{$fieldcount}"}
        {/if}
    </table>
</div>
{jq}
    // multi-column sort by alt key
    $('#{{$id}} thead th a').on('click', function(e) {
        if (e.originalEvent && e.originalEvent.altKey) {
            e.preventDefault();
            var params = new URLSearchParams(window.location.search);
            var current_sort_mode = params.get('{{$sort_arg}}');
            if (! current_sort_mode) {
                return true;
            }
            var orders = current_sort_mode.split(',');
            params = new URLSearchParams($(this).attr('href').replace(/^[^?]*/, ''));
            var sort_mode = params.get('{{$sort_arg}}');
            var found = false;
            for (var i = 0, l = orders.length; i < l; i++) {
                if (orders[i].replace(/_n?(asc|desc)$/, '') == sort_mode.replace(/_n?(asc|desc)$/, '')) {
                    orders[i] = sort_mode;
                    found = true;
                    break;
                }
            }
            if (! found) {
                orders.push(sort_mode);
            }
            params.delete('{{$sort_arg}}');
            params.append('{{$sort_arg}}', orders.join(','));
            window.location.href = $(this).attr('href').replace(/\?.*$/, '') + '?' + params.toString();
            return false;
        }
        return true;
    });
{/jq}
{if $actions}
    <div class="row w-100 list_execute_actions">
        <div class="col-sm-1">
            <input type="submit" class="btn btn-primary btn-sm" title="{tr}Apply Changes{/tr}" id="submit_form_{$id}" disabled value="{tr}Apply{/tr}">
        </div>
        <div class="col-sm-4">
            <select name="list_action" class="form-control" id="check_submit_select_{$id}">
                <option></option>
                {foreach from=$actions item=action}
                    <option value="{$action->getName()|escape}" data-input='{$action->requiresInput()}'>
                        {$action->getName()|escape}
                    </option>
                {/foreach}
            </select>
        </div>
        <div class="col-sm-4" id="list_input_container_{$id}">
        </div>
        <input type="text" name="list_input" value="" class="form-control" style="display:none">
    </div>

</form>
{$checkboxVisible = (isset($tableparams.checkboxVisible) and $tableparams.checkboxVisible eq 'y')}
{jq}
(function(){
    var countChecked = function() {
        if ($('#{{$id}}-div .checkbox_objects').is(':checked')) {
            if($('select#check_submit_select_{{$id}}').val()){
                $('input#submit_form_{{$id}}').prop('disabled', false);
            }
        } else {
            $('input#submit_form_{{$id}}').prop('disabled', true);
        }
        var header_checked = $('#{{$id}}-div .checkbox_objects').not(':checked').length == 0;
        $('#listexecute-{{$iListExecute}} .listexecute-all').val(header_checked ? 'ALL' : '');
    };
    $('#listexecute-{{$iListExecute}} .listexecute-select-all').removeClass('listexecute-select-all')
        .on('click', function (e) {
            $(this).closest('form').find('tbody :checkbox{{if $checkboxVisible}}:visible{{/if}}:not(:disabled)').each(function () {
                $(this).prop("checked", ! $(this).prop("checked"));
            }).promise().done(function(){ countChecked(); });
        });
    {{if $checkboxVisible}}
        $( "#{{$id}}" ).on( 'tablesorter-ready', function() {
            $(this).data("tablesorter").checkboxVisible = true;
        });
    {{/if}}
    $('#listexecute-{{$iListExecute}}').find('select[name=list_action]')
        .on('change', function() {
            var valueSel = $('select#check_submit_select_{{$id}}').val();
            if(valueSel == ''){
                $('input#submit_form_{{$id}}').prop('disabled', true);
            } else {
                if($('#{{$id}}-div .checkbox_objects').is(':checked')){
                    $('input#submit_form_{{$id}}').prop('disabled', false);
                }
            }
            var params = $(this).find('option:selected').data('input');
            if(typeof params === "object") {
                params = Object.values(params).filter(function(el){ return !!el; }).shift();
            }
            if (typeof params === "object") {
                $("#list_input_container_{{$id}}").load(
                    $.service('tracker', 'fetch_item_field', params),
                    function () {
                        $(this).tiki_popover().applySelect2();
                    }
                ).show();
            } else if( params ) {
                $(this).closest('.list_execute_actions').find('input[name=list_input]').show();
                $("#list_input_container_{{$id}}").hide();
            } else {
                $(this).closest('.list_execute_actions').find('input[name=list_input]').hide();
                $("#list_input_container_{{$id}}").hide();
            }
        });
    $( "#{{$id}}-div .checkbox_objects" ).on( "click", countChecked );
    countChecked();
    $('#listexecute-{{$iListExecute}}').on("submit", function(){
        feedback(tr('Action is being executed, please wait.'));
        $(this).tikiModal(" ");
        var filters = $('#list_filter{{$iListExecute|replace:'wplistexecute-':''}} form').serializeArray(),
            inp, i;
        for(i = 0, l = filters.length; i < l; i++) {
            inp = $('<input type="hidden">');
            inp.attr('name', filters[i].name);
            inp.val(filters[i].value);
            $('#listexecute-{{$iListExecute}}').append(inp);
        }
        var trackerInputs = $("input,select,textarea", "#list_input_container_{{$id}}").serializeArray();
        if (trackerInputs) {
            for (i = 0; i < trackerInputs.length; i++) {
                inp = $('<input type="hidden">');
                inp.attr("name", "list_input~" + trackerInputs[i].name);    // add tracker inputs as an array "inside" list_input
                inp.val(trackerInputs[i].value);
                $('#listexecute-{{$iListExecute}}').append(inp);
            }
            $("#listexecute-{{$iListExecute}}").remove("input[list_input]");
        }
    });
})();
{/jq}
{/if}
{if ! empty($downloadable) && ! empty($downloadablebottom) && $downloadablebottom == 'y'}
    {if $actions}
    <br>
    {/if}
    <form method="post" id="listexecute-download-{$iListExecute}">
        <input type="hidden" name="download" value="1">
        <input type="hidden" name="listId" value="{$iListExecute}">
        <input type="hidden" name="tsAjax" value="y">
        <input type="submit" name="submit" value="{tr}Download{/tr}" class="btn btn-primary">
    </form>
{jq}
(function(){
    $('#listexecute-download-{{$iListExecute}}').on("submit", function(){
        var $form = $(this);
        $form.find('input[name^=filter]').remove();
        $('.tablesorter-filter').each(function(i,el){
            var column = $(el).data('column'),
                    value = $(el).val();
            if( value ) {
                $('<input type="hidden" name="filter['+column+']">')
                    .val(value)
                    .appendTo($form);
            }
        });
        var m = "{{$id}}".match(/wpcs\-(\d+)$/);
        var id = m ? m[1] : null;
        var cs = window['customsearch_'+id];
        if (cs) {
            $form.attr('action', $.service('search_customsearch', 'customsearch'));
            var datamap = {
                definition: cs.definition,
                adddata: $.toJSON(cs.searchdata),
                searchid: cs.id,
                offset: cs.offset,
                maxRecords: cs.maxRecords,
                store_query: cs.store_query
            }
            $.each(datamap, function(k, v) {
                $('<input type="hidden">').attr('name', k).val(v).appendTo($form);
            });
        }
    });
})();
{/jq}
{/if}
{if $showFacets}
        </div>
    </div>
{/if}
