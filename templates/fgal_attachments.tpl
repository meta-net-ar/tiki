<a id="attachments"></a>
{if $tiki_p_wiki_view_attachments == 'y' || $tiki_p_wiki_admin_attachments == 'y' || $tiki_p_wiki_attach_files == 'y'}

    <div
        {if isset($pagemd5) and $pagemd5}
            {$cookie_key="show_attzone$pagemd5"}
            id="attzone{$pagemd5}"
        {else}
            {$cookie_key="show_attzone"}
            id="attzone"
        {/if}
        {if (isset($smarty.session.tiki_cookie_jar.$cookie_key) and $smarty.session.tiki_cookie_jar.$cookie_key eq 'y')
            or (!isset($smarty.session.tiki_cookie_jar.$cookie_key) and $prefs.w_displayed_default eq 'y')}
            style="display:block;"
        {else}
            style="display:none;"
        {/if}
    >

    {if ($tiki_p_wiki_attach_files eq 'y' or $tiki_p_wiki_admin_attachments eq 'y')
        and (empty($attach_box) or $attach_box ne 'n')}
        <div class="file-upload card bg-body-tertiary">
            <div class="card-body">
                <form enctype="multipart/form-data" action="tiki-index.php?page={$page|escape:"url"}" method="post">
                    {ticket}
                    {if $page_ref_id}
                        <input type="hidden" name="page_ref_id" value="{$page_ref_id}">
                    {/if}
                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label" for="attach-upload">{tr}Upload file{/tr}</label>
                        <div class="col-sm-10">
                            <input size="16" name="userfile[0]" type="file" class="form-control" id="attach-upload">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-sm-2 col-form-label" for="attach-comment">{tr}Description{/tr}</label>
                        <div class="col-sm-8">
                            <input class="form-control" type="text" name="s_f_attachments-comment" maxlength="250" id="attach-comment" placeholder="{tr}File upload comment{/tr}...">
                        </div>
                        <div class="col-sm-2">
                            <input
                                type="submit"
                                class="btn btn-primary"
                                name="s_f_attachments-upload"
                                value="{tr}Attach{/tr}"
                            >
                            <input type="hidden" name="s_f_attachments-page" value="{$page|escape}">
                            {ticket}
                        </div>
                    </div>
                </form>
            </div>
        </div>
    {/if}

    {* Generate table if view permissions granted and if count of attached files > 0 *}
    {if ($tiki_p_wiki_view_attachments == 'y' || $tiki_p_wiki_admin_attachments == 'y') && count($files) > 0}
        <fieldset>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <legend class="mb-0">{tr}Attached files{/tr}</legend>
                <div class="btn-group">
                    <a class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#" title="{tr}Views mode{/tr}" role="button">
                        {tr}Views mode{/tr}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        {if $view neq 'browse'}
                            <li>
                                <a class="dropdown-item" href="{query _type='relative' view='browse' fileId='NULL' offset='NULL'}#attachments">{icon name="view"} {tr}Browse{/tr}</a>
                            </li>
                        {/if}
                        {if $view neq 'list'}
                            <li>
                                <a class="dropdown-item" href="{query _type='relative' view='list' fileId='NULL' offset='NULL'}#attachments">{icon name="list"} {tr}List{/tr}</a>
                            </li>
                        {/if}
                        {if $view neq 'page' and $count gt 0}
                            <li>
                                <a class="dropdown-item" href="{query _type='relative' view='page' offset='0'}#attachments">{icon name="textfile"} {tr}Page{/tr}</a>
                            </li>
                        {/if}
                    </ul>
                </div>
            </div>

            {if $view eq 'page'}
                <div class="pageview mb-3">
                    <form id="size-form-attachments" class="form d-flex flex-row flex-wrap align-items-end gap-1" action="">
                        {ticket}
                        <input type="hidden" name="s_f_attachments-view" value="page">
                        <input type="hidden" name="s_f_attachments-offset" value="{$offset}">
                        <input type="hidden" name="s_f_attachments-maxRecords" value="1">
                        <div class="row col-sm-4">
                            <label for="maxWidthAttachment" class="col-form-label">{tr}Maximum width{/tr}</label>
                            <div class="input-group col-sm-2">
                                <input id="maxWidthAttachment" class="form-control" type="number" name="s_f_attachments-maxWidth" value="{$maxWidth}">
                                <span class="input-group-text">{tr}pixels{/tr}</span>
                            </div>
                        </div>
                        <input type="submit" class="wikiaction btn btn-primary" name="setSize" value="{tr}Submit{/tr}">
                    </form>
                </div>
                {pagination_links count=$count step=$maxRecords offset=$offset offset_arg="s_f_attachments-offset"}
                    {query _type='relative' view='page' maxWidth=$maxWidth maxRecords=$maxRecords}
                {/pagination_links}
                <div class="mt-3">
                    {$show_infos='y'}
                    {include file='fgal_view_page.tpl'}
                </div>
            {elseif $view eq 'browse'}
                {$show_infos='y'}
                {include file='browse_file_gallery.tpl'}
            {else}
                {$show_infos='n'}
                {if $count > $maxRecords}
                    <div class="clearboth mb-2">
                        {pagination_links count=$count step=$maxRecords offset=$offset offset_arg="s_f_attachments-offset"}{/pagination_links}
                    </div>
                {/if}
                {include file='list_file_gallery_content.tpl'}
                {if $count > $maxRecords}
                    <div class="clearboth mt-2">
                        {pagination_links count=$count step=$maxRecords offset=$offset offset_arg="s_f_attachments-offset"}{/pagination_links}
                    </div>
                {/if}
            {/if}
        </fieldset>
    {/if}
</div>
{/if}
