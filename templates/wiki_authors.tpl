<div class="wiki-authors">
<hr>
{if $wiki_authors_style eq 'business'}
    {tr}Last edited by{/tr} {$lastUser|userlink}
    {section name=author loop=$contributors}
        {if !empty($smarty.section.author.first)}
            , {tr}based on work by{/tr}
        {else}
            {if !$smarty.section.author.last}
                ,
            {else}
                {tr}and{/tr}
            {/if}
        {/if}
        {$contributors[author]|userlink}
    {/section}.
    <br>
    {if $prefs.wiki_show_version eq 'y'}
        {if $info['version'] == 1}
            {tr _0=$lastModif|tiki_long_datetime _1=$lastVersion}Page last modified on %0. (Version %1) : Initial version{/tr}
        {else}
            {tr _0=$lastModif|tiki_long_datetime _1=$lastVersion}Page last modified on %0. (Version %1){/tr}
        {/if}
    {else}
        {if $info['version'] == 1}
            {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0 : Initial version{/tr}
        {else}
            {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0{/tr}
        {/if}
    {/if}
    {if $revision_approval_info}
        <br>
        {tr _0=$revision_approval_info.user|userlink _1=$revision_approval_info.lastModif|tiki_long_datetime}Page approved by %0 on %1{/tr}
    {/if}
{elseif $wiki_authors_style eq 'collaborative'}
    {tr}Contributors to this page:{/tr} {$lastUser|userlink}
    {section name=author loop=$contributors}
        {if !$smarty.section.author.last}
            ,
        {else}
            {tr}and{/tr}
        {/if}
        {$contributors[author]|userlink}
    {/section}.
    <br>
    {tr _0=$lastModif|tiki_long_datetime _1=$lastUser|userlink}Page last modified on %0 by %1{/tr}.
    {if $prefs.wiki_show_version eq 'y'}
        ({tr}Version{/tr} {$lastVersion})
    {/if}

    {if $revision_approval_info}
        <br>
        {tr _0=$revision_approval_info.user|userlink _1=$revision_approval_info.lastModif|tiki_long_datetime}Page approved by %0 on %1{/tr}
    {/if}

{elseif $wiki_authors_style eq 'lastmodif'}
    {if $info['version'] == 1}
        {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0 : Initial version{/tr}
    {else}
        {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0{/tr}
    {/if}
{else}
    {tr _0=$creator|userlink}Created by %0{/tr}.
    {tr _0=$lastModif|tiki_long_datetime _1=$lastUser|userlink}Last Modification: %0 by %1{/tr}.
    {if $prefs.wiki_show_version eq 'y'}
        ({tr}Version{/tr} {$lastVersion})
    {/if}

    {if $revision_approval_info}
        <br>
        {tr _0=$revision_approval_info.user|userlink _1=$revision_approval_info.lastModif|tiki_long_datetime}Page approved by %0 on %1{/tr}
    {/if}
{/if}
</div>
