{strip}
    {if $prefs.feature_page_title eq 'y' and (not isset($hide_page_header) or not $hide_page_header)}
        <h1>
            <a href="tiki-backlinks.php?page={$page|escape:url}" title="{tr}backlinks to{/tr} {$page|escape}">{$page|escape}</a>
        </h1>
    {/if}
    {if isset($is_slideshow) && !$is_slideshow eq 'y'}
        <div class="wikitext">
    {/if}
{/strip}{$parsed}
{if isset($is_slideshow) && !$is_slideshow eq 'y'}
    </div>
{/if}
{if !isset($smarty.request.clean)}
    {if isset($prefs.wiki_authors_style) && $prefs.wiki_authors_style eq 'business'}
        <footer class="editdate">
            {tr}Last edited by{/tr} {$lastUser}
            {section name=author loop=$contributors}
                {if !empty($smarty.section.author.first)}, {tr}based on work by{/tr}
                {else}
                    {if !$smarty.section.author.last},
                    {else} {tr}and{/tr}
                    {/if}
                {/if}
                {$contributors[author]}
            {/section}.<br>
            {if $version == 1}
                {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0 : Initial version{/tr}.
            {else}
                {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0{/tr}.
            {/if}
        </footer>
    {elseif isset($prefs.wiki_authors_style) && $prefs.wiki_authors_style eq 'collaborative'}
        <footer class="editdate">
            {tr}Contributors to this page:{/tr} {$lastUser}
            {section name=author loop=$contributors}
            {if !$smarty.section.author.last},
            {else} {tr}and{/tr}
            {/if}
            {$contributors[author]}
            {/section}.<br>
            {if $version == 1}
                {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0 : Initial version{/tr}.
            {else}
                {tr _0=$lastModif|tiki_long_datetime}Page last modified on %0{/tr}.
            {/if}
        </footer>
    {elseif isset($prefs.wiki_authors_style) && $prefs.wiki_authors_style eq 'none'}
    {else}
        <footer class="editdate">
            {tr}Created by:{/tr} {$creator}
            {if $version == 1}
                {tr _0=$lastModif|tiki_long_datetime _1=$lastUser|userlink}Last Modification: %0 by %1 : Initial version{/tr}
            {else}
                {tr _0=$lastModif|tiki_long_datetime _1=$lastUser|userlink}Last Modification: %0 by %1{/tr}
            {/if}
        </footer>
    {/if}

    {if (!$prefs.page_bar_position or $prefs.page_bar_position eq 'bottom' or $prefs.page_bar_position eq 'both') and $machine_translate_to_lang == ''}
        {include file='tiki-page_bar.tpl'}
    {/if}
{/if}
{if isset($is_slideshow) && $is_slideshow eq 'y'}
<style>[hidden] {ldelim}display: block !important;{rdelim}</style>
{/if}
