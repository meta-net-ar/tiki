{title admpage="articles" help="Articles"}{tr}Submissions{/tr}{/title}
<div class="t_navbar mb-4">
    {button href="tiki-edit_submission.php" class="btn btn-primary" _icon_name="create" _text="{tr}New Submission{/tr}"}
    <button form="deleteexpired_form" type="submit" class="btn btn-danger" title="{tr}Deletes expired submissions 1000 at a time to avoid timeouts{/tr}" onclick="confirmPopup('{tr}Are you sure you want to permanently remove all expired submitted articles?{/tr}')">
        {icon name='delete' _menu_text='y' _menu_icon='y' alt="{tr}Delete Expired Submissions{/tr}"}
    </button>
    <form id="deleteexpired_form" action="tiki-list_submissions.php" method="post">
        {ticket}
        <input type="hidden" name="deleteexpired" value="y">
    </form>
    {if $tiki_p_read_article eq 'y'}
        {button href="tiki-list_articles.php" class="btn btn-info mt-3" _icon_name="list" _text="{tr}List Articles{/tr}"}
    {/if}
</div>

{if $listpages or ($find ne '') or ($types ne '') or ($topics ne '') or ($lang ne '') or ($categId ne '')}
    <div class="row mx-0">
        <div class="col-md-6">
            {include file='find.tpl' find_show_languages='y' find_show_num_rows='y'}
        </div>
    </div>
{/if}

<form name="checkform" method="post">
{ticket}
    <input type="hidden" name="maxRecords" value="{$maxRecords|escape}">
    <div class="table-responsive"> {*the table-responsive class cuts off dropdown menus when chosen is selected*}
        <table class="table table-striped table-hover">
            {assign var=numbercol value=0}
            <tr>
                {if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
                    <th class="auto">
                        {if $listpages}
                            {select_all checkbox_names='checked[]'}
                        {/if}
                    </th>
                {/if}
                {if $prefs.art_list_title eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Title{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_topic eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'topicName_desc'}topicName_asc{else}topicName_desc{/if}">{tr}Topic{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_date eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'publishDate_desc'}publishDate_asc{else}publishDate_desc{/if}">{tr}Publish Date{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_expire eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'expireDate_desc'}expireDate_asc{else}expireDate_desc{/if}">{tr}Expiry Date{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_size eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th style="text-align:right;">
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'size_desc'}size_asc{else}size_desc{/if}">{tr}Size{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_img eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>{tr}Image{/tr}</th>
                {/if}
                {if $prefs.art_list_author eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'author_desc'}author_asc{else}author_desc{/if}">{tr}User{/tr}</a>
                    </th>
                {/if}
                {if $prefs.art_list_authorName eq 'y'}
                    {assign var=numbercol value=$numbercol+1}
                    <th>
                        <a href="tiki-list_submissions.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'authorName_desc'}authorName_asc{else}authorName_desc{/if}">{tr}Author{/tr}</a>
                    </th>
                {/if}
                {assign var=numbercol value=$numbercol+1}
                <th></th>
            </tr>

            {section name=changes loop=$listpages}
                <tr>
                    {if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
                        <td class="checkbox-cell">
                            <input class="form-check-input" aria-label="{tr}Select{/tr}" type="checkbox" name="checked[]" value="{$listpages[changes].subId|escape}" {if $listpages[changes].checked eq 'y'}checked="checked" {/if} required>
                        </td>
                    {/if}
                    {if $prefs.art_list_title eq 'y'}
                        <td class="text">
                            <a class="link" title="{$listpages[changes].title|escape}" href="tiki-edit_submission.php?subId={$listpages[changes].subId}">{$listpages[changes].title|truncate:$prefs.art_list_title_len:"...":true|escape}</a>
                        </td>
                    {/if}
                    {if $prefs.art_list_topic eq 'y'}
                        <td class="text">{$listpages[changes].topicName|escape}</td>
                    {/if}
                    {if $prefs.art_list_date eq 'y'}
                        <td class="date">{$listpages[changes].publishDate|tiki_short_date}</td>
                    {/if}
                    {if $prefs.art_list_expire eq 'y'}
                        <td class="date">{$listpages[changes].expireDate|tiki_short_date}</td>
                    {/if}
                    {if $prefs.art_list_size eq 'y'}
                        <td class="integer">{$listpages[changes].size|kbsize}</td>
                    {/if}
                    {if $prefs.art_list_img eq 'y'}
                        <td class="text">{$listpages[changes].hasImage}/{$listpages[changes].useImage}</td>
                    {/if}
                    {if $prefs.art_list_author eq 'y'}
                            <td class="text">{$listpages[changes].author|escape}</td>
                        {/if}
                    {if $prefs.art_list_authorName eq 'y'}
                            <td class="text">{$listpages[changes].authorName|escape}</td>
                    {/if}
                    <td class="action">
                        {actions}
                            {strip}
                                {if $tiki_p_edit_submission eq 'y' or ($listpages[changes].author eq $user and $user)}
                                    <action>
                                        <a href="tiki-edit_submission.php?subId={$listpages[changes].subId}">
                                            {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                        </a>
                                    </action>
                                {/if}
                                {if $tiki_p_approve_submission eq 'y'}
                                    <action>
                                        <form action="tiki-list_submissions.php" method="post" >
                                            {ticket}
                                            <button type="submit" name="approve" value="{$listpages[changes].subId}" class="tips btn btn-link btn-sm px-0 pt-0 pb-0" onclick="confirmPopup('{tr _0=$listpages[changes].subId}Are you sure you want to approve the submitted article with identifier %0?{/tr}')">
                                                {icon name="ok"}{tr} Approve{/tr}
                                            </button>
                                        </form>
                                    </action>
                                {/if}
                                {if $tiki_p_remove_submission eq 'y'}
                                    <action>
                                        <form action="tiki-list_submissions.php" method="post">
                                            {ticket}
                                            <input type="hidden" name="remove" value="{$listpages[changes].subId}">
                                            <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr _0=$listpages[changes].subId}Are you sure you want to permanently remove the submitted article with identifier %0?{/tr}')">
                                                {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                            </button>
                                        </form>
                                    </action>
                                {/if}
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {sectionelse}
                {assign var=numbercol value=$numbercol+1}
                {norecords _colspan=$numbercol}
            {/section}
            {if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'} 
                <tr>
                    <td colspan="{$numbercol+1}">
                        {if $listpages}
                            {if $tiki_p_remove_submission eq 'y'}
                                {button _text="{tr}Select Duplicates{/tr}" _onclick="checkDuplicateRows(this); return false;"}
                            {/if}
                        {/if}
                    </td>
                </tr>
            {/if}
        </table>
    </div>
    {if $tiki_p_remove_submission eq 'y' or $tiki_p_approve_submission eq 'y'}
        <div>
            <div class="col-lg-9 input-group">
                <select id="submit_mult_action" name="submit_mult">
                    <option value="">{tr}Select action to perform with checked...{/tr}</option>
                    {if $tiki_p_remove_submission eq 'y'}<option id="remove" value="remove_subs" >{tr}Remove{/tr}</option>{/if}
                    {if $tiki_p_approve_submission eq 'y'}<option id="approve" value="approve_subs" >{tr}Approve{/tr}</option>{/if}
                </select>
                <input id="submit_mult" type="submit" class="btn btn-warning" value="{tr}OK{/tr}">
            </div>
        </div>
    {/if}

    {pagination_links cant=$cant_pages step=$maxRecords offset=$offset}{/pagination_links}
</form>
{jq}
    var checkboxes = $('.checkboxes');
    checkboxes.on("change", function(){
        if($('.checkboxes:checked').length > 0) {
            checkboxes.removeAttr('required');
        } else {
            checkboxes.attr('required', 'required');
        }
    });

    $("#submit_mult").on("click", function(){
        if ($('.checkboxes:checked').length > 0) {
            var action = $('#submit_mult_action').val()
            if (action == "remove_subs") {
                confirmPopup("Are you sure you want to permanently remove these "+$('.checkboxes:checked').length+" submitted articles?");
            } else {
                confirmPopup("Are you sure you want to approve these "+$('.checkboxes:checked').length+" submitted articles?");
            }
        }
    });

{/jq}
