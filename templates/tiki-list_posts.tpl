{title help="Blogs"}{if isset($blogTitle)}{tr _0=$blogTitle}Blog: %0{/tr}{else}{tr}Blog Posts{/tr}{/if}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-edit_blog.php" _type="link" class="btn btn-link" _icon_name="add" _text="{tr}Create Blog{/tr}"}
    {button href="tiki-blog_post.php" _type="link" class="btn btn-link" _icon_name="create" _text="{tr}New Blog Post{/tr}"}
    {button href="tiki-list_blogs.php" _type="link" class="btn btn-link" _icon_name="list" _text="{tr}List Blogs{/tr}"}
</div>
{if $posts or ($find ne '')}
    {include file='find.tpl'}
{/if}

{if $posts and $tiki_p_blog_admin eq 'y'}
    <form name="checkboxes_on" method="post" action="tiki-list_posts.php" role="form" class="form">
        {ticket}
    {query _type='form_input'}
{/if}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table">
        <tr>
            {if $posts and $tiki_p_blog_admin eq 'y'}
                <td>  {* Changed from th to prevent ARIA empty header error *}
                    {select_all checkbox_names='checked[]'}
                </td>
            {/if}
            <th>
                <a href="tiki-list_posts.php?{if isset($blogId)}blogId={$blogId}&amp;{/if}offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_asc'}title_desc{else}title_asc{/if}">
                    {tr}Post Title{/tr}
                </a>
            </th>
            {if !isset($blogId)}
                <th>{tr}Blog Title{/tr}</th>
            {/if}
            <th>
                <a href="tiki-list_posts.php?{if isset($blogId)}blogId={$blogId}&amp;{/if}offset={$offset}&amp;sort_mode={if $sort_mode eq 'created_desc'}created_asc{else}created_desc{/if}">{tr}Created{/tr}</a>
            </th>
            <th class="text-end">{tr}Size{/tr} {tr}(bytes){/tr}</th>
            <th>
                <a href="tiki-list_posts.php?{if isset($blogId)}blogId={$blogId}&amp;{/if}offset={$offset}&amp;sort_mode={if $sort_mode eq 'user_desc'}user_asc{else}user_desc{/if}">{tr}Author{/tr}</a>
            </th>
            <td></td>  {* Changed from th to prevent ARIA empty header error *}
        </tr>


        {section name=changes loop=$posts}{assign var=id value=$posts[changes].postId}
            <tr>
                <td class="checkbox-cell"><input class="form-check-input" aria-label="{tr}Select{/tr}" type="checkbox" name="checked[]" value="{$id}"></td>
                <td class="text">{object_link type="blog post" id=$posts[changes].postId title=$posts[changes].title}</td>
                {if !isset($blogId)}
                    <td class="text">
                        <a class="blogname" href="tiki-list_posts.php?blogId={$posts[changes].blogId}" title="{$posts[changes].blogTitle|escape}">{$posts[changes].blogTitle|truncate:$prefs.blog_list_title_len:"...":true|escape}</a>
                    </td>
                {/if}
                <td class="date">&nbsp;{$posts[changes].created|tiki_short_date}&nbsp;</td>
                <td class="integer">{$posts[changes].size}</td>
                <td>&nbsp;{$posts[changes].user}&nbsp;</td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a href="tiki-blog_post.php?blogId={$posts[changes].blogId}&postId={$posts[changes].postId}">
                                    {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            {if $tiki_p_admin eq 'y' || $tiki_p_assign_perm_blog eq 'y'}
                                <action>
                                    {permission_link mode=text type="blog post" permType="blogs" id=$posts[changes].postId}
                                </action>
                            {/if}
                            <action>
                                <form action="tiki-list_posts.php" method="post">
                                    {ticket}
                                    {if isset($blogId)}
                                        <input type="hidden" name="blogId" value="{$blogId}">
                                    {/if}
                                    <input type="hidden" name="offset" value="{$offset}">
                                    <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                    <input type="hidden" name="remove" value="{$posts[changes].postId}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Delete this item{/tr}?')">
                                        {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {sectionelse}
            {norecords _colspan=7}
        {/section}
    </table>
</div>

{if $posts and $tiki_p_blog_admin eq 'y'}
        <div class="tiki-form-group row">
            <label for="remove" class="col-form-label">{tr}Perform action with selected{/tr}</label>
            <div class="input-group col-sm-4">
                <select name="remove" class="form-control text-danger">
                    <option value="y">{tr}Delete{/tr}</option>
                </select>
                <input type="submit" class="btn btn-primary" onclick="confirmPopup('{tr}Delete posts{/tr}')" name="remove" value="{tr}Ok{/tr}">
            </div>
        </div>
    </form>
{/if}

{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
