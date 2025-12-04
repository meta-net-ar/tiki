{if !empty($container_class)}<div class="{$container_class}">{/if}
<div id="articles-container">
{section name=ix loop=$listpages}
    {capture name=href}{strip}
        {if empty($urlparam)}
            {if $useLinktoURL eq 'n' or empty($listpages[ix].linkto)}
                {$listpages[ix].articleId|sefurl:article}
            {else}
                {$listpages[ix].linkto}
            {/if}
        {else}
            {$listpages[ix].articleId|sefurl:article:with_next}{$urlparam}
        {/if}
    {/strip}{/capture}
    <div class="articletitle mt-0 mx-0 mb-1">
        <span class="newsitem">
            <a href="{$smarty.capture.href}">{$listpages[ix].title|escape}</a>
        </span>
        <br>
    </div>
{/section}
</div>
{if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
    <el-infinite-scroll container-id="articles-container" offset="{$offset}" step="{$maxArticles}" total-count="{$count}"></el-infinite-scroll>
{/if}
{if !empty($listpages) && (isset($usePagination) and $usePagination ne 'n') && ($prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y')}
    {pagination_links count=$count step=$maxArticles offset=$offset}{if isset($urlnext)}{$urlnext}{/if}{/pagination_links}
{/if}
{if !empty($container_class)}</div>{/if}
