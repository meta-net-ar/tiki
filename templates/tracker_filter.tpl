<form action="" method="get">
    <input type="hidden" name="trackerId" value="{$trackerId|escape}">
    {if $status}<input type="hidden" name="status" value="{$status}">{/if}
    {if $sort_mode}<input type="hidden" name="sort_mode" value="{$sort_mode}">{/if}
    {if $offset}<input type="hidden" name="offset" value="{$offset}">{/if}
    <div class="search_container mb-3 d-flex flex-row flex-wrap align-items-center gap-2">
        {if ($tracker_info.showStatus|default:null eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')) and $showstatus|default:null ne 'n'}
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted text-nowrap">{tr}Filter by:{/tr}</span>
                <div class="btn-group flex-wrap" role="group" aria-label="{tr}Filter by status{/tr}">
                    {* Status Toggles: Bootstrap semantic checkboxes *}
                    {foreach key=st item=stdata from=$status_types}
                        <input type="checkbox" class="btn-check js-status-checkbox" id="status-btn-{$stdata.statuslink}"
                            value="{$st}" {if $stdata.class eq 'statuson'}checked{/if}>
                        <label class="btn btn-outline-primary d-flex align-items-center gap-1" for="status-btn-{$stdata.statuslink}">
                            {icon name="{$stdata.iconname}"}
                            <span>{$stdata.label}</span>
                        </label>
                    {/foreach}
                </div>

                {if isset($smarty.get.status) and $smarty.get.status neq ''}
                    {* "Reset" button: Restores the default status filter (clears manual selections) *}
                    <button id="js-status-all" type="button" class="btn btn-link d-flex align-items-center gap-1 tips text-decoration-none"
                        title=":{tr}Reset to default status filter{/tr}">
                        {icon name="undo"} <span>{tr}Reset{/tr}</span>
                    </button>
                {/if}
            </div>
        {/if}

        <div class="w-25">
            {if $show_filters eq 'y'}
                {jq}
                fields = [];
                {{$c=0}}
                {{foreach key=fid item=field from=$listfields}
                    {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i'}
                        fields[{$c}] = '{$fid}';
                        {$c=$c+1}
                    {/if}
                {/foreach}}
                {/jq}
                <select name="filterfield" class="form-select" placeholder="{tr}Choose a filter{/tr}"
                    onchange="this.form.submit(); {literal}showit = 'show_filterbutton'; if(this.selectedIndex == 0){document.getElementById('filterbutton').style.display = 'none';setSessionVar(showit,'n');}else{ document.getElementById('filterbutton').style.display = 'block'; setSessionVar(showit,'y');}{/literal}">
                    <option value="">{tr}Choose a filter{/tr}</option>
                    {foreach key=fid item=field from=$listfields}
                        {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i' and ($field.isHidden ne 'y' or $tiki_p_admin_trackers eq 'y')}
                            <option value="{$fid}" {if $fid eq $filterfield} selected="selected" {/if}>
                                {tr}{$field.name|truncate:65|escape}{/tr}</option>
                            {$filter_button='y'}
                        {/if}
                    {/foreach}
                </select>
            {/if}
        </div>
        <div class="d-flex gap-2">
            {$cnt=0}
            {foreach key=fid item=field from=$listfields}
                {if $field.isSearchable eq 'y' and $field.type ne 'f' and $field.type ne 'j' and $field.type ne 'i'}
                    {if $field.type eq 'c'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <select name="filtervalue[{$fid}]" class="form-select">
                                <option value="y"{if $filtervalue eq 'y'} selected="selected"{/if}>{tr}Yes{/tr}</option>
                                <option value="n"{if $filtervalue eq 'n'} selected="selected"{/if}>{tr}No{/tr}</option>
                            </select>
                        </div>
                    {elseif $field.type eq 'd' or $field.type eq 'D'}
                        <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                            <select name="filtervalue[{$fid}]" class="form-select">
                                {if $field.type eq 'D'}<option value=""></option>{/if}
                                {foreach from=$field.possibilities key=dropdown_key item=dropdown_value}
                                    <option value="{$dropdown_key|escape}" {if $fid == $filterfield}
                                            {if $filtervalue eq $dropdown_key}{$gotit='y'}selected="selected" {/if} {/if}>
                                            {$dropdown_value|tr_if}</option>
                                    {/foreach}
                                </select>
                                {if $field.type eq 'D'}
                                    <input class="form-control" type="text" name="filtervalue_other" {if $gotit ne 'y'}
                                            value="{if $fid == $filterfield}{$filtervalue}{/if}" {/if}>
                                    {/if}
                                </div>

                            {elseif $field.type eq 'R'}
                                <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                                    {foreach from=$field.possibilities key=radio_key item=radio_value}
                                        <input type="radio" class="form-check-input" name="filtervalue[{$fid}]" value="{$radio_key|escape}"
                                            {if $fid == $filterfield} {if $filtervalue eq $radio_key}checked="checked" {/if}
                                        {/if}>{$radio_value|escape}
                                {/foreach}
                            </div>

                        {elseif $field.type eq 'M'}
                            <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                                {if empty($field.options_map.inputtype)}
                                    {foreach from=$field.possibilities key=value item=label}
                                        <label>
                                            <input type="checkbox" class="form-check-input" name="filtervalue[{$fid}][]" value="{$value|escape}"
                                                {if $fid == $filterfield and is_array($filtervalue) and in_array($value, $filtervalue)}checked="checked"
                                                {/if}>
                                            {$label|tr_if|escape}
                                        </label>
                                    {/foreach}
                                {elseif $field.options_map.inputtype eq 'm'}
                                    {if $prefs.elementplus_select neq 'y'}<small>{tr}Hold "Ctrl" in order to select multiple
                                        values{/tr}</small><br>{/if}
                                    <select name="filtervalue[{$fid}][]" multiple="multiple" class="form-select">
                                        {foreach key=ku from=$field.possibilities key=value item=label}
                                            <option value="{$value|escape}"
                                                {if is_array($filtervalue) and in_array($value, $filtervalue)}selected="selected" {/if}>
                                                {$label|escape}</option>
                                        {/foreach}
                                    </select>
                                {/if}
                            </div>

                        {elseif $field.type eq 'e'}{* category *}
                            <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}" class="card-body">
                                {if count($field.list) gt $prefs.maxRecords}
                                    <select name="filtervalue[{$fid}][]" class="form-control" multiple>
                                        {foreach key=ku item=iu from=$field.list name=eforeach}
                                            <option value="{$iu.categId}"
                                                {if $fid == $filterfield && is_array($filtervalue) && in_array($iu.categId,$filtervalue)}
                                                selected="selected" {/if}>
                                                {$iu.categpath|escape}
                                            </option>
                                        {/foreach}
                                    </select>
                                {else}
                                    <ul class="list-unstyled">
                                        {foreach key=ku item=iu from=$field.list name=eforeach}
                                            <li class="form-check justify-content-start">
                                                <input type="checkbox" class="form-check-input" name="filtervalue[{$fid}][]"
                                                    value="{$iu.categId}" id="cat{$iu.categId}"
                                                    {if $fid == $filterfield && is_array($filtervalue) && in_array($iu.categId,$filtervalue)}
                                                    checked="checked" {/if}>
                                                <label for="cat{$iu.categId}" class="form-check-label">{$iu.categpath|escape}</label>
                                            </li>
                                        {/foreach}
                                    </ul>
                                {/if}
                            </div>
                        {elseif $field.type eq 'u'}{* user with autocomplete *}
                            <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                                <input type="text" class="form-control" name="filtervalue[{$fid}]"
                                    value="{if $fid == $filterfield}{$filtervalue}{/if}" id="filter-username">
                            </div>
                            {autocomplete element='#filter-username' type='username'}
                        {else}
                            <div style="display:{if $filterfield eq $fid}block{else}none{/if};" id="fid{$fid}">
                                <input type="text" class="form-control" name="filtervalue[{$fid}]"
                                    value="{if $fid == $filterfield}{$filtervalue}{/if}">
                            </div>
                        {/if}
                        {$cnt=$cnt+1}
                    {/if}
                {/foreach}
            </div>
            {if isset($filter_button) && $filter_button eq 'y'}
                <div id="filterbutton" class="btn-group" role="group"
                    style="display:{if $filterfield}inline-flex{else}none{/if}">
                    <button id="filterbutton_primary" type="submit" class="btn btn-primary" name="filter">
                        {tr}Filter{/tr}
                    </button>

                    <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="visually-hidden">{tr}Toggle dropdown{/tr}</span>
                    </button>

                    <ul class="dropdown-menu">
                        <li>
                            <button class="dropdown-item" type="submit" name="filter_exact">
                                {tr}Exact filter{/tr}
                            </button>
                        </li>
                    </ul>
                </div>
            {/if}
        </div>
        {jq}
        // "Reset" button: clears manual status filter, lets backend use defaultStatus
        $(document).on('click', '#js-status-all', function() {
            let $form = $(this).closest('form');
            // Remove any explicit status parameter so backend defaults apply
            $form.find('input[name="status"]').remove();
            $form.trigger('submit');
        });

        // Handle individual status checkbox changes
        $(document).on('change', '.js-status-checkbox', function() {
            let form = this.form;
            let $form = $(form);
            let allCheckboxes = $form.find('.js-status-checkbox');
            let checkboxes = allCheckboxes.filter(':checked');

            // Mandatory Rule: if user unchecks the last checkbox, reset to default filter
            if (checkboxes.length === 0) {
                // Reset to default: remove explicit status, let backend decide
                $form.find('input[name="status"]').remove();
                $form.trigger('submit');
                return;
            }

            let values = [];
            checkboxes.each(function() {
                values.push(this.value);
            });

            let outputVal = values.join('');

            // Synchronize status hidden input
            $form.find('input[name="status"]').remove();
            $('<input>').attr({
                type: 'hidden',
                name: 'status',
                value: outputVal
            }).appendTo($form);

            $form.trigger('submit');
        });
        {/jq}
    </form>
