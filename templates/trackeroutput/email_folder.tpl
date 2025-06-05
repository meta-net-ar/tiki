<div id="display_f{$field.fieldId|escape}" class="email-folder-field display_f{$field.fieldId|escape}">
    {if $tiki_p_use_webmail eq 'y'}
        <a href="{$data.compose_path}" class="btn btn-secondary btn-sm rounded-pill">{tr}Compose{/tr}</a>
    {/if}
    {if $data.count eq 0}
        {tr}Emails can be copied or moved here via the Webmail interface.{/tr}
    {elseif $field.options_map.useFolders}
        <div class="btn-group">
            {foreach from=$data.folders key=folder item=folderName}
                {if isset($data.emails[$folder]) and $data.emails[$folder]|count gt 0}
                    <button class="btn btn-outline-secondary btn-sm email-folder-switcher" data-folder="{$folder}">{$folderName} ({$data.emails[$folder]|count})</button>
                {/if}
            {/foreach}
            {if $field.options_map.useItemFolders}
                <button class="btn btn-outline-secondary btn-sm add-email-folder" data-field-id="{$field.fieldId|escape}" data-item-id="{$data.itemId}">{tr}Add Folder{/tr} {icon name="plus"}</button>
            {/if}
        </div>
        {foreach from=$data.folders key=folder item=folderName}
            {if isset($data.emails[$folder]) and $data.emails[$folder]|count gt 0}
                <div class="email-folder-contents folder-{$folder}" style="display: {if in_array($folder, $data.opened)}block{else}none{/if}">
                    {include file='trackeroutput/email_single_folder.tpl' emails=$data.emails[$folder]}
                </div>
            {/if}
        {/foreach}
        {jq}
            $(".email-folder-switcher").on('click', function(e){
                e.preventDefault();
                $(this).closest('.email-folder-field').find(".email-folder-contents.folder-"+$(this).data('folder')).toggle();
                return false;
            });
        {/jq}
    {else}
        {include file='trackeroutput/email_single_folder.tpl' emails=$data.emails.inbox}
    {/if}
</div>
