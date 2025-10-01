{strip}
<table class="table">
    {foreach item=prop key=propname from=$fgal_listing_conf}
        {if isset($item.key)}
            {$propkey=$item.key}
        {else}
            {$propkey="show_$propname"}
        {/if}
        {if isset($file.$propname)}
            {if $propname == 'share' && isset($file.share.data)}
                {$email = []}
                {foreach item=tmp_prop key=tmp_propname from=$file.share.data}
                    {$email[]=$tmp_prop.email}
                {/foreach}
                {$propval=$email|join:','}
            {else}
                {$propval=$file.$propname}
            {/if}
        {/if}
        {* Format property values *}
        {if $propname eq 'created' or $propname eq 'lastModif' or $propname eq 'lastDownload'}
            {$propval=$propval|tiki_long_date}
        {elseif $propname eq 'last_user' or $propname eq 'author' or $propname eq 'creator'}
            {$propval=$propval|username|replace:'&amp;':'&'}
        {elseif $propname eq 'size'}
            {$propval=$propval|kbsize:true}
        {elseif $propname eq 'description'}
            {$propval=$propval|nl2br}
        {elseif $propname eq 'parentId'}
            {$propval = $propval|sefurl:'filegallery'}
            {$propval = "<a href='$propval'>`$gal_info.name`</a>"}
        {elseif $propname eq 'ocr_state'}
            {if $propval === '1'}
                {$propval='{tr}Finished processing{/tr}'}
            {elseif $propval === '2'}
                {$propval='{tr}Currently processing{/tr}'}
            {elseif $propval === '3'}
                {$propval='{tr}Queued for processing{/tr}'}
            {elseif $propval === '4'}
                {$propval='{tr}Processing stalled{/tr}'}
            {else}
                {$propval='{tr}No scheduled processing{/tr}'}
            {/if}
        {/if}

        {if isset($gal_info.$propkey)
            and $propval neq ''
            and ($propname neq 'name' or $view eq 'page')
            and ($gal_info.$propkey eq 'a' or $gal_info.$propkey eq 'o'
                    or ($view eq 'page' and ($gal_info.$propkey neq 'n' or $propname eq 'name'))
                )
        }
            <tr>
                <td style="width:20%;">
                    <b>{$fgal_listing_conf.$propname.name}</b>
                </td>
                <td>
                    <span class="float-start">{$propval}</span>
                </td>
            </tr>
        {/if}
    {/foreach}
</table>
{/strip}
