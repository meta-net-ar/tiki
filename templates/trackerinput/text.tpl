{if $field.isMultilingual ne 'y'}
    <div{if $field.options_map.prepend or $field.options_map.append or $field.options_map.labelasplaceholder} class="input-group"{/if}>
        {if !empty($field.options_map.prepend)}
            <span class="input-group-text">{$field.options_map.prepend}</span>
        {/if}
        <input type="text" class="form-control {if !empty($field.options_map.labelasplaceholder)}labelasplaceholder{/if}"
                id="{$field.ins_id|replace:'[':'_'|replace:']':''}" name="{$field.ins_id}"
                value="{$field.value|default:$field.defaultvalue|escape}"
                {if !empty($context.disabled)}disabled{/if}
                {if !empty($field.options_map.labelasplaceholder)}placeholder="{$field.name|tra|escape:'htmlattr'}"{/if}
                {if !empty($field.options_map.size)}size="{$field.options_map.size}"{/if}
                {if !empty($field.options_map.max)}maxlength="{$field.options_map.max}"{/if}
        >
        {if $field.options_map.labelasplaceholder and $field.isMandatory eq 'y'}
             <span class="input-group-text">
                <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}" style="font-size: 100%">{icon name='asterisk'}</strong>
            </span>
        {/if}
        {if !empty($field.options_map.append)}
            <span class="input-group-text">{$field.options_map.append}</span>
        {/if}
        {if $field.options_map.autocomplete eq 'y' and $prefs.elementplus_autocomplete eq 'y'}
            {autocomplete element="#"|cat:$field.ins_id|replace:"[":"_"|replace:"]":"" type="trackervalue"
                    options="trackerId:"|cat:$field.trackerId|cat:",fieldId:"|cat:$field.fieldId}
        {/if}
    </div>
{else}
    {foreach from=$field.lingualvalue item=ling name=multi}
        {if !$field.options_map.labelasplaceholder}<label for="{$ling.id|escape}">{$ling.lang|langname}</label>{/if}
        <div{if $field.options_map.prepend or $field.options_map.append} class="input-group"{/if}>
            {if !empty($field.options_map.prepend)}
                <span class="input-group-text">{$field.options_map.prepend}</span>
            {/if}

            <input type="text" id="{$ling.id|escape}" name="{$field.ins_id}[{$ling.lang}]" value="{$ling.value|escape}"
                class="form-control{if !empty($field.options_map.labelasplaceholder)} labelasplaceholder mb-2{/if}"
                {if !empty($field.options_map.labelasplaceholder)} placeholder="{tr}{$field.name}{/tr} - {$ling.lang|langname}"{/if}
                {if !empty($field.options_map.size)}size="{$field.options_map.size}"{/if}
                {if !empty($field.options_map.max)}maxlength="{$field.options_map.max}"{/if}
            >

            {if !empty($field.options_map.append)}
                <span class="input-group-text">{$field.options_map.append}</span>
            {/if}

            {if $field.options_map.autocomplete eq 'y' and $prefs.elementplus_autocomplete eq 'y'}
                {autocomplete element="#`$ling.id`" type="trackervalue"
                    options="trackerId:`$field.trackerId`,fieldId:`$field.fieldId`"}
            {/if}
        </div>
    {/foreach}
{/if}
