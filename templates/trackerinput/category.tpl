{if !empty($field.options_array[2]) && ($field.options_array[2] eq '1' or $field.options_array[2] eq 'y')}
    {select_all checkbox_names=$field.html_name label="{tr}Select All{/tr}"}
{/if}
{if $field.options_array[1] eq 'd' || $field.options_array[1] eq 'm' || $field.options_array[1] eq 'transfer'}
    {foreach key=ku item=cat from=$categories}
        <input id="cat{$cat.categId|escape}_hidden" type="hidden" name="cat_managed_{$field.html_name}" value="{$cat.categId|escape}">
    {/foreach}
    {if $field.options_array[1] eq 'transfer'}
        {jstransfer_list fieldName="{$field.html_name|escape}" defaultSelected=$field.selected_categories
                data=$transfer_data sourceListTitle=$field.options_map.sourceListTitle
                targetListTitle=$field.options_map.targetListTitle filterable=$field.options_map.filterable
                filterPlaceholder=$field.options_map.filterPlaceholder ordering=$field.options_map.ordering cardinalityParam=$field.validationParam validationMessage=$field.validationMessage}
    {else}
        {if $field.options_array[1] eq 'm' and $prefs.elementplus_select neq 'y'}<small>{tr}Hold "Ctrl" in order to select multiple values{/tr}</small><br>{/if}
        <select name="{$field.html_name}"{if $field.options_array[1] eq 'm'} multiple="multiple"{/if} class="form-select">
            {if $field.options_array[1] eq 'd' and (empty($field.value[0]) or $field.isMandatory ne 'y')}
                <option value=""></option>
            {/if}
            {foreach key=ku item=cat from=$categories}
                <option value="{$cat.categId|escape}" {if in_array($cat.categId, $field.selected_categories)}selected="selected"{/if} {if !$cat.canchange} disabled="disabled"{/if}>{$cat.relativePathString|escape}</option>
            {/foreach}
        </select>
    {/if}
{elseif !empty($cat_tree)}
    {$cat_tree}{* checkboxes with descendents *}
{else}
    <div class="input-group col-md-12">
        {foreach key=ku item=iu from=$categories name=eforeach}
            {$fcat=$iu.categId}
            <div class="col-md-4">
                <label for="cat{$iu.categId}" class="{if $field.options_array[1] eq "radio"}radio{else}checkbox{/if}">
                    <input id="cat{$iu.categId|escape}_hidden" type="hidden" name="cat_managed_{$field.html_name}" value="{$iu.categId|escape}">
                    <input type={if $field.options_array[1] eq "radio"}"radio"{else}"checkbox"{/if} name="{$field.html_name}" value="{$iu.categId}" id="cat{$iu.categId}" {if in_array($fcat, $field.selected_categories)} checked="checked"{/if} {if !$iu.canchange} disabled="disabled"{/if}>
                    {if $field.options_array[4] eq 1 && !empty($iu.description)}<a href="{$iu.description|escape}" target="tikihelp" class="tikihelp" title="{$iu.name|escape}:{$iu.description|escape}">{icon name='help'}</a>{/if}
                    {$iu.name|escape}
                </label>
            </div>
        {/foreach}
    </div>
{/if}
