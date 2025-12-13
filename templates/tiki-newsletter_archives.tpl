{title help="Newsletters"}{tr}Sent editions{/tr}{if $nl_info}: {$nl_info.name}{/if}{/title}

<div class="t_navbar mb-3">
    {if $tiki_p_subscribe_newsletters eq "y"}
        {button href="tiki-newsletters.php?nlId=$nlId&amp;info=1" class="btn btn-primary" _text="{tr}Subscribe{/tr}"}
    {/if}
    {if $tiki_p_list_newsletters eq "y"}
        <a role="link" href="tiki-newsletters.php" class="btn btn-link" title="{tr}List{/tr}">{icon name="list"} {tr}List Newsletters{/tr}</a>
    {/if}
    {if $tiki_p_send_newsletters eq "y"}
        <a role="link" href="tiki-send_newsletters.php?nlId={$nlId}" class="btn btn-link" title="{tr}Send{/tr}">{icon name="envelope"} {tr}Send Newsletters{/tr}</a>
    {/if}
    {if $tiki_p_admin_newsletters eq "y"}
        <a role="link" href="tiki-admin_newsletters.php" class="btn btn-link" title="{tr}Admin Newsletters{/tr}">{icon name="cog"} {tr}Admin Newsletters{/tr}</a>
    {/if}
</div>

<div id="newsletter_archives">
    {if $edition}
        <h3>{tr}Subject{/tr}</h3>
        <div class="alert alert-info newsletter_subject">{$edition.subject|escape}</div>

        <h3>{tr}HTML version{/tr}</h3>
        <div class="alert alert-info newsletter_content">{$edition.dataparsed}</div>

        {if $allowTxt eq 'y'}
            <h3>{tr}Text version{/tr}</h3>
            {if !empty($edition.datatxt)}<div class="alert alert-info newsletter_textdata" >{$info.datatxt|escape|nl2br}</div>{/if}
            {if $txt}<div class="alert alert-info newsletter_text">{$txt|escape|nl2br}</div>{/if}
        {/if}
        <div class="newsletter_trailer">
            {$sent=$edition.users}
            {tr _0=$sent}The newsletter was sent to %0 email addresses{/tr}<br>
            {$edition.sent|tiki_short_datetime}
        </div>
    {/if}

    {$view_editions='y'}
    {$cur='ed'}
    {$bak='dr'}
    {$sort_mode=$ed_sort_mode}
    {$sort_mode_bak='sent_desc'}
    {$offset=$ed_offset}
    {$offset_bak=0}
    {$find=$ed_find}
    {$find_bak=''}
    {include file='sent_newsletters.tpl'}

    {if $edition_errors}
        <h2>{tr}Errors:{/tr} {$edition_info.subject} / {$edition_info.sent|tiki_short_datetime}</h2>
        <a href="tiki-newsletter_archives.php?deleteError={$edition_info.editionId}" title="{tr}Delete errors{/tr}">{icon name='remove' alt="{tr}Remove{/tr}"}</a>
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <th>{tr}Email{/tr}</th>
                    <th>{tr}User{/tr}</th>
                    <th>{tr}Status{/tr}</th>
                </tr>

                {section name=ix loop=$edition_errors}
                    <tr>
                        <td class="email">{$edition_errors[ix].email}</td>
                        <td class="username">{$edition_errors[ix].login}</td>
                        <td class="text">{if $edition_errors[ix].error eq 'y'}{tr}Error{/tr}{else}{tr}Not sent{/tr}{/if}</td>
                    </tr>
                {/section}
            </table>
        </div>
    {/if}
</div>
