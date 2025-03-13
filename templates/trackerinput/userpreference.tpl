{if $field.options_map.type eq 'password'}
    {if ($prefs.auth_method neq 'cas' || ($prefs.cas_skip_admin eq 'y' && $user eq 'admin')) and $prefs.change_password neq 'n'}
        <input type="password" name="{$field.ins_id}" id="{$field.ins_id|escape}" class="form-control">
        <br><i>Leave empty if password is to remain unchanged</i>
    {/if}
{elseif $field.options_map.type eq 'language'}
    <select name="{$field.ins_id}" id="{$field.ins_id|escape}" class="form-control">
        {section name=ix loop=$languages}
            <option value="{$languages[ix].value|escape}" {if $field.value eq $languages[ix].value}selected="selected"{/if}>
                {$languages[ix].name}
            </option>
        {/section}
        <option value=''{if !$field.value} selected="selected"{/if}>{tr}Site default{/tr}</option>
    </select>
{elseif $field.options_map.type eq 'country'}
    <select name="{$field.ins_id}" id="{$field.ins_id|escape}" class="form-control">
        <option value="Other"{if $field.value eq "Other"} selected="selected"{/if}>
            {tr}Other{/tr}
        </option>
        {foreach from=$context.flags item=flag key=fval}{strip}
            {if $fval ne "Other"}
                <option value="{$fval|escape}"{if $field.value eq $fval} selected="selected"{/if}>
                    {$flag|stringfix}
                </option>
            {/if}
        {/strip}{/foreach}
    </select>
{elseif $field.options_map.type eq 'display_timezone'}
    <select name="{$field.ins_id}" id="{$field.ins_id|escape}" class="form-control">
        <option value=""{if empty($field.value)} selected="selected"{/if} style="font-style:italic;">
            {tr}Detect user time zone if browser allows, otherwise site default{/tr}
        </option>
        <option value="Site" style="font-style:italic;border-bottom:1px dashed #666;"{if $field.value eq 'Site'} selected="selected"{/if}>
            {tr}Site default{/tr}
        </option>
        {foreach key=tz item=tzinfo from=$context.timezones}
            {math equation="floor(x / (3600000))" x=$tzinfo.offset assign=offset}
            {math equation="(x - (y*3600000)) / 60000" y=$offset x=$tzinfo.offset assign=offset_min format="%02d"}
            <option value="{$tz|escape}"{if $field.value eq $tz} selected="selected"{/if}>
                {$tz|escape} (UTC{if $offset >= 0}+{/if}{$offset}h{if $offset_min gt 0}{$offset_min}{/if})
            </option>
        {/foreach}
    </select>
{elseif $field.options_map.type eq 'gender'}
    <div class="form-check">
        <input type="radio" name="{$field.ins_id}" id="gender_male" value="Male" {if $field.value eq 'Male'}checked="checked"{/if}>
        <label for="gender_male">
            {tr}Male{/tr}
    </label>
    </div>
    <div class="form-check">
        <input type="radio" name="{$field.ins_id}" id="gender_female" value="Female" {if $field.value eq 'Female'}checked="checked"{/if}>
        <label for="gender_female">
             {tr}Female{/tr}
        </label>
    </div>
    <div class="form-check">
        <input type="radio" name="{$field.ins_id}" id="gender_hidden" value="Hidden" {if $field.value eq 'Hidden'}checked="checked"{/if}>
        <label for="gender_hidden">
             {tr}Hidden{/tr}
        </label>
    </div>
{elseif $field.options_map.type eq 'location'}
    <div class="col-md-12 mb-5" style="height: 250px;" data-geo-center="{defaultmapcenter}" data-target-field="location">
        <div class="map-container" style="height: 250px;" data-geo-center="{defaultmapcenter}" data-target-field="location"></div>
    </div>
    <input type="hidden" name="{$field.ins_id}" id="location" value="{$field.value|escape}">
{elseif $field.options_map.type eq 'avatar'}
    {if $value}
        <img id='avtimg' src="{$value}" alt="{tr}Profile picture{/tr}">
    {/if}
    <input type="hidden" name="MAX_FILE_SIZE" value="10000000">
    <input name="{$field.ins_id}" id="{$field.ins_id|escape}" type="file" accept=".gif,.jpg,.jpeg,.png,.webp,.avif">
    <div class="form-text">
        {if $prefs.user_store_file_gallery_picture neq 'y'}{tr}File (only .gif, .jpg and .png images approximately 45px × 45px){/tr}{else}{tr}File (only .gif, .jpg and .png images){/tr}{/if}:
    </div>
{else}
    <input type="text" name="{$field.ins_id}" id="{$field.ins_id|escape}" value="{$field.value|escape}" class="form-control">
{/if}
