{* The heading and category path *}
{if $prefs.feature_breadcrumbs ne 'y'}
    {title help="Directory" url="tiki-directory_browse.php?parent=$parent"}
        {if $parent}
            {tr}Directory:{/tr} {$parent_name}
        {else}
            {tr}Directory{/tr}
        {/if}
    {/title}
{else}
    <div id="pageheader"> {breadcrumbs type="trail" loc="page" crumbs=$crumbs}
        {breadcrumbs type="pagetitle" loc="page" crumbs=$crumbs}
        {breadcrumbs type="desc" loc="page" crumbs=$crumbs}
    </div>
{/if}

{* The navigation bar *}
{include file='tiki-directory_bar.tpl'}

{* The category path *}
{if $prefs.feature_breadcrumbs ne 'y'} <a class="dirlink" href="tiki-directory_browse.php?parent=0">{tr}Top{/tr}</a>{if $parent > 0} >> {/if}{$path}{/if}
{if $parent_info}<div class="description form-text">{$parent_info.description|escape}</div>{/if}
{if !empty($items) && count($items) > 0}
    <div class="container my-3">
        <div class="d-flex justify-content-center">
            <form action="tiki-directory_search.php" method="post" class="d-flex align-items-center w-70">
                <input type="hidden" name="parent" value="{$parent|escape}">
                <label for="how" class="mb-0 mr-2">{tr}Find:{/tr}</label>
                <div class="mx-2"></div>
                <select name="how" id="how" class="form-select form-select-sm mx-2">
                    <option value="or">{tr}any{/tr}</option>
                    <option value="and">{tr}all{/tr}</option>
                </select>
                <input type="text" name="words" class="form-control form-control-sm mx-2 flex-grow-1">
                <select name="where" id="where" class="form-select form-select-sm mx-2">
                    <option value="all">{tr}in entire directory{/tr}</option>
                    <option value="cat">{tr}in current directory category{/tr}</option>
                </select>
                <div class="mx-2"></div>
                <input type="submit" class="btn btn-info btn-sm" value="{tr}Search{/tr}">
            </form>
        </div>
    </div>
{/if}

{if count($categs)}
    <h2>{tr}Directory Subcategories{/tr}</h2>
    <div class="dircategs">
        {* The table with the subcategories *}
        <table>
            <tr>
                {section name=numloop loop=$categs}
                    <td><a class="dirlink" href="tiki-directory_browse.php?parent={$categs[numloop].categId}">{$categs[numloop].name|escape}</a>
                        {if $categs[numloop].showCount eq 'y'}
                            ({$categs[numloop].sites})
                        {/if}
                        <br>
                        {* Now display subcats if any *}
                        {section name=ix loop=$categs[numloop].subcats}
                            {if $categs[numloop].childrenType ne 'd'} <a class="dirsublink" href="tiki-directory_browse.php?parent={$categs[numloop].subcats[ix].categId}">{$categs[numloop].subcats[ix].name}</a>
                            {else}
                                {$categs[numloop].subcats[ix].name}
                            {/if}
                            {if $categs[numloop].subcats[ix].showCount eq 'y'}
                                ({$categs[numloop].subcats[ix].sites})
                            {/if}
                        {/section}
                    </td>
                    {* see if we should go to the next row *}
                    {if not ($smarty.section.numloop.rownum mod $cols)}
                        {if not $smarty.section.numloop.last}
                            </tr>
                            <tr>
                        {/if}
                    {/if}
                    {if !empty($smarty.section.numloop.last)}
                        {* pad the cells not yet created *}
                        {math equation = "n - a % n" n=$cols a=$categs|@count assign="cells"}
                        {if $cells ne $cols}
                            {section name=pad loop=$cells}
                                <td>&nbsp;</td>
                            {/section}
                        {/if}
                    {/if}
                {/section}
            </tr>
        </table>
    </div>
{/if}

{* The links *}
{if isset($categ_info.allowSites) and $categ_info.allowSites eq 'y'}
    <h2>{tr}Links{/tr}</h2>
    {if ! empty($items) && count($items) > 0}
        <div class="dirlistsites">
                <div class="container my-3">
                    <div class="d-flex justify-content-center">
                        <form method="post" action="tiki-directory_browse.php" class="d-flex align-items-center w-50">
                            <input type="hidden" name="parent" value="{$parent|escape}">

                            <label for="sort_mode" class="mb-0 mr-2 w-25">{tr}Sort by:{/tr}&nbsp;</label>
                            <select name="sort_mode" id="sort_mode" class="form-select form-select-sm mx-2 flex-grow-1">
                                <option value="name_desc" {if $sort_mode eq 'name_desc'}selected="selected"{/if}>{tr}Name (desc){/tr}</option>
                                <option value="name_asc" {if $sort_mode eq 'name_asc'}selected="selected"{/if}>{tr}Name (asc){/tr}</option>
                                <option value="hits_desc" {if $sort_mode eq 'hits_desc'}selected="selected"{/if}>{tr}Hits (desc){/tr}</option>
                                <option value="hits_asc" {if $sort_mode eq 'hits_asc'}selected="selected"{/if}>{tr}Hits (asc){/tr}</option>
                                <option value="created_desc" {if $sort_mode eq 'created_desc'}selected="selected"{/if}>{tr}Creation Date (desc){/tr}</option>
                                <option value="created_asc" {if $sort_mode eq 'created_asc'}selected="selected"{/if}>{tr}Creation date (asc){/tr}</option>
                                <option value="lastModif_desc" {if $sort_mode eq 'lastModif_desc'}selected="selected"{/if}>{tr}Last updated (desc){/tr}</option>
                                <option value="lastModif_asc" {if $sort_mode eq 'lastModif_asc'}selected="selected"{/if}>{tr}Last updated (asc){/tr}</option>
                            </select>
                            <div class="mx-2"></div>
                            <input type="submit" class="btn btn-primary btn-sm ml-2" name="xx" value="{tr}sort{/tr}">
                        </form>
                    </div>
                </div>
            {section name=ix loop=$items}
                <div class="dirsite">
                    {if $prefs.directory_country_flag eq 'y'} <img alt="flag" src="img/flags/{$items[ix].country}.png"> {/if}
                    <a class="dirsitelink" href="tiki-directory_redirect.php?siteId={$items[ix].siteId}" {if $prefs.directory_open_links eq 'n'}target='_blank'{/if}>{$items[ix].name|escape}</a>
                    {if $tiki_p_admin_directory_sites eq 'y'}
                        [<a class="dirsitelink" href="tiki-directory_admin_sites.php?parent={$parent}&amp;siteId={$items[ix].siteId}">{tr}Edit{/tr}</a>]
                    {/if}
                    {if $prefs.cachepages eq 'y'}
                        (<a class="dirsitelink" href="tiki-view_cache.php?url={$items[ix].url}" target="_blank">{tr}Cache{/tr}</a>)
                    {/if}
                    <div class="description form-text">{$items[ix].description}</div>
                    {$fsfs=1}
                    <span class="dirsitecats text-info">
                        {tr}Directory Categories:{/tr}
                        {section name=ii loop=$items[ix].cats}
                            {if $fsfs}
                                {$fsfs=0}{else},&nbsp;
                            {/if}
                            <a class="dirsublink" href="tiki-directory_browse.php?parent={$items[ix].cats[ii].categId}">{$items[ix].cats[ii].path|escape}</a>
                        {/section}
                    </span>
                    <br>
                    <span class="dirsitetrail text-info"> {tr}Added:{/tr} {$items[ix].created|tiki_short_date} {tr}Last updated:{/tr} {$items[ix].lastModif|tiki_short_date} {tr}Hits:{/tr} {$items[ix].hits} </span>
                </div>
            {/section}
        </div>
        {pagination_links count=$pages_count step=$prefs.directory_links_per_page offset=$offset}{/pagination_links}
    {elseif !empty($parent)}
        {tr}No records.{/tr}
    {/if}
{/if}

{if count($related)>0}
    <div class="dirrelated"> {tr}Related directory categories{/tr}
        <br>
        <br>
        {section name=ix loop=$related}
            <a class="dirlink" href="tiki-directory_browse.php?parent={$related[ix].relatedTo}">{$related[ix].path}</a>
            <br>
        {/section}
    </div>
{/if}

{include file='tiki-directory_footer.tpl'}
