<nav class="categpath" aria-label="category breadcrumb">
    <ol class="breadcrumb"{if $prefs.site_crumb_seper} style="--bs-breadcrumb-divider: '{$prefs.site_crumb_seper|escape:'quotes'}'"{/if}>
        {foreach name=u key=k item=i from=$catp}
            <li class="breadcrumb-item pe-2{if $smarty.foreach.u.last} active{/if}"{if $smarty.foreach.u.last} aria-current="page"{/if}>
                {if $catpathShowLink and !$smarty.foreach.u.last}
                    <a class="categpath" href="{$k|sefurl:category:'':'':y:$i}" title="{tr}Browse Category{/tr}">
                        {$i|tr_if|escape|replace:' ':'&nbsp;'}
                    </a>
                {else}
                    {$i|tr_if|escape|replace:' ':'&nbsp;'}
                {/if}
            </li>
        {/foreach}
    </ol>
</nav>
