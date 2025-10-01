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

    {* Generate table if view permissions granted and if count of attached files > 0 *}
    {if ($tiki_p_wiki_view_attachments == 'y' || $tiki_p_wiki_admin_attachments == 'y') && count($files) > 0}
        <fieldset>
            <legend>{tr}Attached files{/tr}</legend>
            {include file='list_file_gallery_content.tpl'}
        </fieldset>
    {/if}

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
                        <label class="col-sm-2 col-form-label" for="attach-comment">{tr}Comment{/tr}</label>
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
</div>
{/if}
