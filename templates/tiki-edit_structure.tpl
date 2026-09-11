{title url="tiki-edit_structure.php?page_ref_id=$page_ref_id"}{tr}Structure:{/tr} {$structure_name}{/title}

<div class="t_navbar mb-4">
    {button _class="btn btn-link" href="tiki-admin_structures.php" _text="{tr}Structures{/tr}"}
</div>

{if $remove eq 'y'}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}You will remove{/tr} '{$removePageName}' {if $page_removable == 'y'}{tr}and its subpages from the structure, now you have two options:{/tr}{else}{tr}and its subpages from the structure{/tr}{/if}
        <div class="text-center">
            <form style="display: inline;" action="tiki-edit_structure.php" method="post">
                {ticket}
                <input type="hidden" name="page_ref_id" value="{$structure_id}">
                <input type="hidden" name="rremove" value="{$removepage}">
                <input type="hidden" name="page" value="{$removePageName}">
                <button type="submit" class="btn btn-warning btn-sm" onclick="confirmPopup()">
                    {icon name="remove"} {tr}Remove from structure{/tr}
                </button>
            </form>
            {if $page_removable == 'y'}
                <form style="display: inline;" action="tiki-edit_structure.php" method="post">
                    {ticket}
                    <input type="hidden" name="page_ref_id" value="{$structure_id}">
                    <input type="hidden" name="sremove" value="{$removepage}">
                    <input type="hidden" name="page" value="{$removePageName}">
                    <button type="submit" class="btn btn-warning btn-sm" onclick="confirmPopup()">
                        {icon name="delete"} {tr}Remove from structure and remove page too{/tr}
                    </button>
                </form>
            {/if}
        </div>
    {/remarksbox}
{/if}

{if $alert_exists eq 'y'}
    <strong>{tr}The page already exists. The page that has been added to the structure is the existing one.{/tr}</strong>
    <br/>
{/if}

{if count($alert_in_st) > 0}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
    {tr}Note that the following pages are also part of another structure. Make sure that access permissions (if any) do not conflict:{/tr}
        {foreach from=$alert_in_st item=thest}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thest|escape:"url"}' target="_blank">{$thest}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_categorized) > 0}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}The following pages added have automatically been categorized with the same categories as the structure:{/tr}
        {foreach from=$alert_categorized item=thecat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thecat|escape:"url"}' target="_blank">{$thecat}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_to_remove_cats) > 0}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}The following pages have categories but the structure has none. You may wish to uncategorize them to be consistent:{/tr}
        {foreach from=$alert_to_remove_cats item=thecat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thecat|escape:"url"}' target="_blank">{$thecat}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_to_remove_extra_cats) > 0}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}The following pages are in categories that the structure is not in. You may wish to recategorize them in order to be consistent:{/tr}
        {foreach from=$alert_to_remove_extra_cats item=theextracat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$theextracat|escape:"url"}' target="_blank">{$theextracat}</a>
        {/foreach}
    {/remarksbox}
{/if}
<div class="styling-element">
<div class="admintoclevel" id="topnode_{$page_ref_id}">
    <h2>{tr}Structure Layout{/tr}</h2>
    {if $editable eq 'y'}
        <div class="row">
            <div class="col-12 col-sm-6">
                <form action="tiki-edit_structure.php?page_ref_id={$page_ref_id}" method="post" class="d-flex flex-row flex-wrap align-items-center" style="display: inline-block;">
                    {ticket}
                    <div class="tiki-form-group row">
                    <label for="pageAlias" class="col-sm-4 col-form-label">{tr}Alias:{/tr}</label>
                    <div class="col-sm-8">
                        <input type="hidden" name="page_ref_id" value="{$structure_id}">
                        <div class="input-group">
                            <input type="text" class="form-control form-control-sm" name="pageAlias" id="pageAlias" value="{$topPageAlias|escape}">
                            <input type="submit" class="btn btn-primary btn-sm" name="create" value="{tr}Update{/tr}">
                        </div>
                    </div>
                </div>
                </form>
            </div>

            {* Force next columns to break to new line *}
            <div class="w-100"></div>
            {* modified version of row from structures_toc-leaf.tpl *}
            <div class="col-12 col-sm-6">
                {if $prefs.lock_wiki_structures eq 'y'}
                    {lock type='wiki structure' object=$structure_name}
                {/if}
                {self_link _script='tiki-index.php' page=$structure_name structure=$structure_name _class="tips btn btn-link btn-sm" _title=":{tr}View{/tr}" _noauto="y"}
                    {icon name="view"}
                {/self_link}
                {if $tiki_p_admin_structures == 'y'}
                    {permission_link mode=icon objectType='wiki page' type='wiki structure' id=$pageName title=$pageName}
                {/if}
                {if $tiki_p_watch_structure eq 'y'}
                    {if !$page_info.watching}
                        <form action="tiki-edit_structure.php" method="post" style="display: inline-block;">
                            {ticket}
                            <input type="hidden" name="page_ref_id" value={$page_ref_id}>
                            <input type="hidden" name="watch_object" value={$page_ref_id}>
                            <input type="hidden" name="watch_action" value="add">
                            <button type="submit" name="page" value={$structure_name} data-bs-trigger="hover focus" data-bs-delay="500" data-bs-content="{tr}Monitor the structure{/tr}" class="tips btn btn-link btn-sm"  aria-label="{tr}Monitor the structure{/tr}">
                                {icon name="watch"}
                            </button>
                        </form>
                    {else}
                        {self_link page_ref_id=$page_ref_id watch_object=$page_ref_id watch_action=remove _class="tips btn btn-link btn-sm" _title=":{tr}Stop Monitoring the structure{/tr}"}
                            {icon name="stop-watching"}
                        {/self_link}
                    {/if}
                {/if}
                {if $editable eq 'y'}
                    {if $page_info.flag == 'L'}
                        {capture assign=title}{tr _0=$page_info.user}locked by %0{/tr}{/capture}
                        {icon name='lock' alt="{tr}Locked{/tr}" title=$title}
                    {else}
                        {if !empty($structure_name)}
                            {button _keepall='y' href='tiki-editpage.php' page=$structure_name _class='tips btn btn-link btn-sm' _title=':{tr}Edit page{/tr}' _icon_name='edit'}
                        {elseif !empty($pageName)}
                            {button _keepall='y' href='tiki-editpage.php' page=$pageName _class='tips btn btn-link btn-sm' _title=':{tr}Edit page{/tr}' _icon_name='edit'}
                        {/if}
                    {/if}
                    {if empty($page)}
                        {self_link _class="tips btn btn-link btn-sm add_new_child_page" _title=":{tr}Add new child page{/tr}"}
                            {icon name="add"}
                        {/self_link}
                    {/if}
                {/if}
            </div>
        </div>
    {/if}
</div>
<div>
    {self_link page_ref_id=$structure_id}
        {if $structure_id eq $page_ref_id}<strong>{/if}
        <span class="lead">{tr}Top{/tr}</span>
        {if $structure_id eq $page_ref_id}</strong>{/if}
    {/self_link}
</div>
<div class="save-structure-wrapper sticky-top bg-body py-2 border-bottom" style="display:none;">
    {button _text="{tr}Save{/tr}" _style="display:none;" _class="save_structure" _type="primary" _ajax="n" _auto_args="save_structure,page_ref_id"}
</div>
<div class="structure-container">
    {$nodelist}
</div>
<div class="save-structure-wrapper text-center mt-2" style="display:none;">
    {button _text="{tr}Save{/tr}" _style="display:none;" _class="save_structure" _type="primary" _ajax="n" _auto_args="save_structure,page_ref_id"}
</div>

{if $editable == 'y'}
    {remarksbox type="tip" title="{tr}Quick help{/tr}"}
        {tr}To add an existing page, search in "Add pages", drag the page into the structure, then click Save.{/tr}
        <br>
        {tr}Alternative: on any structure page, use "Type to search ... / Add Page / Child".{/tr}
    {/remarksbox}
    <form action="tiki-edit_structure.php" method="get">
        <div class="card">
            <div class="card-header">
                <strong>{tr}Add pages{/tr}</strong> <small>{tr}Use an existing page by dragging it into the structure above{/tr}</small>
            </div>
            <div class="card-body">
                <div>
                    <input type="hidden" name="page_ref_id" value="{$page_ref_id}">
                    <div class="tiki-form-group row">
                        <label class="sr-only" for="find_objects">{tr}Search{/tr}</label>
                        <div class="input-group">
                            <input type="text" name="find_objects" id="find_objects" value="{$find_objects|escape}" class="form-control form-control-sm" placeholder="{tr}Search{/tr}...">
                            <input type="submit" class="btn btn-primary btn-sm" value="{tr}Filter{/tr}" name="search_objects">
                            {autocomplete element='#find_objects' type='pagename'}
                        </div>
                    </div>
                    {if $prefs.feature_categories eq 'y'}
                        <div class="tiki-form-group row">
                            <select name="categId" class="form-control form-select-sm">
                                <option value='' {if $find_categId eq ''}selected="selected"{/if}>{tr}any category{/tr}</option>
                                {foreach $categories as $catix}
                                    <option value="{$catix.categId|escape}" {if !empty($find_categId) and $find_categId eq $catix.categId}selected="selected"{/if}>{tr}{$catix.categpath}{/tr}</option>
                                {/foreach}
                            </select>
                        </div>
                    {/if}
                </div>
                <ul id="page_list_container" class="list-group">
                    {if !empty($listpages.data)}
                        {foreach $listpages.data as $aPage}
                            <li class="list-group-item" data-page-name="{$aPage.pageName|escape}">
                                {$aPage.pageName|escape}
                            </li>
                        {/foreach}
                    {else}
                        <li class="list-group-item">{tr}No pages found{/tr}</li>
                    {/if}
                </ul>
                {if !empty($listpages.count) && $listpages.count > 0 && !empty($maxRecords) && $maxRecords > 0 && $listpages.count > $maxRecords}
                    {pagination_links count=$listpages.count step=$maxRecords offset=$offset}{/pagination_links}
                {/if}
            </div>
        </div>
    </form>
    {if $prefs.feature_categories eq 'y' && $prefs.feature_wiki_categorize_structure == 'y' && $all_editable == 'y'}
        <form action="tiki-edit_structure.php" method="post">
            <div class="card">
                <div class="card-header">
                    <strong>{tr}Categorize all pages in structure together{/tr}</strong>
                </div>
                <div class="card-body">
                    <input type="hidden" name="page_ref_id" value="{$page_ref_id}">
                    {include file='categorize.tpl'}
                </div>
                <div class="card-footer text-center">
                    <input type="submit" class="btn btn-primary" name="recategorize" value="{tr}Update{/tr}">
                    <input type="checkbox" class="form-check-input" name="cat_override" >{tr}Remove existing categories from ALL pages before recategorizing{/tr}
                </div>
            </div>
        </form>
    {/if}
    <div id="move_dialog" style="display: none;">
        <form action="tiki-edit_structure.php" method="post" class="no-ajax">
            {ticket}
            <input type="hidden" name="page_ref_id" value="{$page_ref_id}">
            <div class="clearfix" style="margin-bottom: 1em;">
                <label for="structure_id">{tr}Move to another structure:{/tr}</label>
                <select class="form-select" name="structure_id" id="structure_id"{if $structures|@count eq '1'} disabled="disabled"{/if}>
                    {section name=ix loop=$structures}
                        {if $structures[ix].page_ref_id ne $structure_id}
                            <option value="{$structures[ix].page_ref_id}">{$structures[ix].pageName}</option>
                        {/if}
                        {if $structures|@count eq '1'}
                            <option value="">{tr}None{/tr}</option>
                        {/if}
                    {/section}
                </select>
            </div>
            <label class="float-start" for="begin1">{tr}at the beginning{/tr}</label>
            <div class="float-start"><input type="radio" class="form-check-input" id="begin1" name="begin" value="1" checked="checked" {if $structures|@count eq '1'} disabled="disabled"{/if}></div>
            <label class="float-start" for="begin2">{tr}at the end{/tr}</label>
            <div class="float-start"><input type="radio" class="form-check-input" id="begin2" name="begin" value="0" {if $structures|@count eq '1'}disabled="disabled"{/if}></div>
            <hr>
            <div class="float-start input_submit_container submit">
                <input type="submit" class="btn btn-primary" name="move_to" value="{tr}Move{/tr}" {if $structures|@count eq '1'} disabled="disabled"{/if}>
            </div>
        </form>
    </div>
    <div id="newpage_dialog" style="display: none;">
        <form action="tiki-edit_structure.php" method="post" class="no-ajax">
            {ticket}
            <input type="hidden" name="page_ref_id" value="{$page_ref_id}">
            <div class="tiki-form-group">
                <label>{tr}Create Page{/tr}</label>
                <div>
                    <input type="text" name="name" id="name" class="form-control">
                    {autocomplete element='#name' type='pagename'}
                </div>
            </div>
            <div class="tiki-form-group row submit">
                <label class="col-sm-3 col-form-label"></label>
                <div class="col-sm-7">
                    <input type="submit" class="btn btn-primary" name="create" value="{tr}Update{/tr}">
                </div>
            </div>
        </form>
    </div>
{/if}{* end of if structure editable *}
</div>
