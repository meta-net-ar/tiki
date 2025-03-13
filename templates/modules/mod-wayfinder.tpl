{* mod-wayfinder.tpl *}
{strip}
{tikimodule error=$module_params.error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip|default:null decorations=$module_params.decorations|default:null nobox=$module_params.nobox|default:null notitle=$module_params.notitle|default:null type=$module_type}
<nav class="wayfinder">
{if $structure eq 'y'}
    {if count($showstructs) gt 1}
      <span class="icon icon-map far fa-map"></span> {tr}All Structures which contain this page:{/tr}
      <ul class="wayfinder-indepth">
      {section name=struct loop=$showstructs}
        <li><a href="tiki-index.php?page={$page|escape:url}&amp;structure={$showstructs[struct].pageName|escape:url}">{*{icon name="chevron-circle-up"}&nbsp;*}
          {if $showstructs[struct].page_alias}
            {$showstructs[struct].page_alias}
          {else}
            {$showstructs[struct].pageName|escape}
          {/if}
      </a></li>
      {/section}
      </ul>
    {/if}
    {if $parent_info}
      {if $parent_info.page_alias}{assign var=link_title value=$parent_info.page_alias}
      {else}{assign var=link_title value=$parent_info.pageName}
      {/if}
       <div class="wayfinder wayfinder-up">
      <a class="wayfinder-up" href="{sefurl page=$parent_info.pageName structure=$home_info.pageName page_ref_id=$parent_info.page_ref_id}" title="{tr}Parent page{/tr}">
        <span class="link-direction">{icon name="arrow-circle-up"} {tr}Parent Page:{/tr} </span>
        <span class="link-title">{$link_title}</span> <span class="link-summary">{$parent_info.description|escape}</span></a>
         </div>
    {/if}
    {if $nextsibling_info and $nextsibling_info.page_ref_id}
      {if $nextsibling_info.page_alias}{assign var=nextsibling_title value=$nextsibling_info.page_alias}
      {else}{assign var=nextsibling_title value=$nextsibling_info.pageName}{/if}
       <div class="wayfinder wayfinder-next">
      <a class="wayfinder-next" href="{sefurl page=$nextsibling_info.pageName structure=$home_info.pageName page_ref_id=$nextsibling_info.page_ref_id}" title="{tr}Next page in this series{/tr}">
        <span class="link-direction">{icon name="arrow-circle-right"} {tr}Next Page:{/tr} </span>
        <span class="link-title">{$nextsibling_title}</span> <span class="link-summary">{$nextsibling_info.description|escape}</span></a>
         </div>
    {/if}
    {if $prevsibling_info and $prevsibling_info.page_ref_id}
        {if $prevsibling_info.page_alias}{assign var=prevsibling_title value=$prevsibling_info.page_alias}
        {else}{assign var=prevsibling_title value=$prevsibling_info.pageName}
        {/if}
         <div class="wayfinder wayfinder-previous">
        <a class="wayfinder-previous" href="{sefurl page=$prevsibling_info.pageName structure=$home_info.pageName page_ref_id=$prevsibling_info.page_ref_id}" title="{tr}Previous page in this series{/tr}">
          <span class="link-direction">{icon name="arrow-circle-left"} {tr}Previous Page:{/tr} </span>
          <span class="link-title">{$prevsibling_title}</span> <span class="link-summary">{$prevsibling_info.description|escape}</span></a>
           </div>
      {/if}
    <div class="indepth">
        {if $children}
          {icon name="plus-circle"} {tr}Children pages:{/tr}
          <ul class="wayfinder-indepth">
            {foreach from=$children item=child}
                <li><a href="{sefurl page=$child.pageName structure=$home_info.pageName page_ref_id=$child.page_ref_id}"> {*{icon name="angle-double-right"} *}{$child.pageName}</a></li>
           {/foreach}
          </ul>
        {/if}

      </div>

{else}
    {if $showstructs && count($showstructs) neq 0}
      <span class="icon icon-map far fa-map"></span> {tr}Show this page as part of a Structure:{/tr}
      <ul class="wayfinder-indepth">
      {section name=struct loop=$showstructs}
        <li><a href="tiki-index.php?page={$page|escape:url}&amp;structure={$showstructs[struct].pageName|escape:url}">{*{icon name="chevron-circle-up"} *}
          {if $showstructs[struct].page_alias}
            {$showstructs[struct].page_alias}
          {else}
            {$showstructs[struct].pageName|escape}
          {/if}
      </a></li>
      {/section}
      </ul>
    {else}
    <div>{tr}This page is not part of a Structure.{/tr}</div>
    {/if}

{/if}
</nav>
{/tikimodule}
{/strip}
