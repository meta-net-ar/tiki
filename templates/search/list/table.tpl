{if empty($iListExecute)}{$iListExecute=$id|replace:'wplistexecute-':''}{/if}
{if ! empty($downloadable) && ! empty($downloadabletop) && $downloadabletop == 'y'}
    <form method="post" id="listexecute-download-top-{$iListExecute}">
        <input type="hidden" name="download" value="1">
        <input type="hidden" name="listId" value="{$iListExecute}">
        <input type="hidden" name="tsAjax" value="y">
        <input type="submit" name="submit" value="{tr}Download{/tr}" class="btn btn-primary">
    </form>
{/if}
{if isset($tableparams.title)}
    <div class="list-table-heading">{wiki}{$tableparams.title|escape}{/wiki}</div>
{/if}
{$showFacets = not empty($facets) and isset($tableparams.facets) and $tableparams.facets eq 'y'}
{if $showFacets}
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
                $('.facets ul').registerFacet();
            {/jq}
        {/if}
{/if}
{if $actions}
<form method="post" action="#{$id}" class="d-flex flex-row flex-wrap align-items-center list-executable" id="listexecute-{$iListExecute}" data-id="{$id}"{if $tsOn} data-page-reload="1"{/if}>
<input type="hidden" name="plugin" value="{$fingerprint}">
{ticket}
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
                        {if isset($entry.report_status) && $row.report_status eq 'success'}
                            {icon name='ok'}
                        {elseif isset($entry.report_status) && $row.report_status eq 'error'}
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
    <div class="list_execute_actions mb-2 w-100">
        <div class="row">
            <div class="col mb-2">
                <select name="list_action" class="form-control check_submit_select" id="check_submit_select_{$id}">
                    <option></option>
                    {foreach from=$actions item=action}
                        <option value="{$action->getName()|escape}" data-input='{$action->requiresInput()}' data-inputtype='{$action->inputtype()}'{if $action->getDefault()} selected{/if}>
                            {$action->getName()|tra|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
            <div class="col-auto list_input_container mb-2" id="list_input_container_{$id}" style="display:none"></div>
            <div class="col-auto mb-2" style="display:none">
                <input type="text" name="list_input" value="" class="form-control">
            </div>
            {*category_tree*}
            {if $prefs.feature_categories eq 'y' and $tiki_p_modify_object_categories eq 'y' and count($categories) gt 0}
                <div class="col-sm-12 mb-2 multiselect form-select cat_tree" style="display:none">
                    {if is_array($categories) and count($categories) gt 0}
                        {$cat_tree}
                        <input type="hidden" name="cat_categorize" value="on">
                        <div class="clearfix">
                            {if $tiki_p_admin_categories eq 'y'}
                                <div class="float-sm-end">
                                    <a class="btn btn-link btn-sm tips" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}" role="button">
                                        {icon name="cog"} {tr}Categories{/tr}
                                    </a>
                                </div>
                            {/if}
                            {select_all checkbox_names='cat_categories[]' label="{tr}Select/deselect all categories{/tr}"}
                        </div> {* end .clear *}
                    {else}
                        <div class="clearfix">
                            {if $tiki_p_admin_categories eq 'y'}
                                <div class="float-sm-end">
                                    <a class="btn btn-link" href="tiki-admin_categories.php" title=":{tr}Admin Categories{/tr}" role="button">
                                        {icon name="cog"} {tr}Categories{/tr}
                                    </a>
                                </div>
                            {/if}
                        </div> {* end .clear *}
                        {tr}No categories defined{/tr}
                    {/if}
                </div> {* end #multiselect *}
            {/if}
            <div class="col-auto align-self-center me-auto mb-2 ms-auto justify-content-center">
                <input type="submit" class="btn btn-primary btn-sm list_execute_submit" title="{tr}Apply Changes{/tr}" id="submit_form_{$id}" disabled value="{if !empty($label)}{tr}{$label|escape}{/tr}{else}{tr}Apply{/tr}{/if}">
            </div>
        </div>
    </div>

</form>
{$checkboxVisible = (isset($tableparams.checkboxVisible) and $tableparams.checkboxVisible eq 'y')}
{jq}
(function(){
    {{if $checkboxVisible}}
        $( "#{{$id}}" ).on( 'tablesorter-ready', function() {
            $(this).data("tablesorter").checkboxVisible = true;
        });
    {{/if}}
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
{/if}
{if $showFacets}
        </div>
    </div>
{/if}
