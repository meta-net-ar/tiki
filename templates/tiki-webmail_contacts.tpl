<!DOCTYPE html>
<html lang="{$prefs.language}"{if Language::isRTL()} dir="rtl"{/if}>
    <head>
        {include file='header.tpl'}
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div id="tiki-clean" class="container">

            <h2>{tr}Contacts{/tr}</h2>
            <div class="row">
            <div class="col-md-12">
                {include file='find.tpl'}
                {initials_filter_links}
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>
                                <a href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'firstName_desc'}firstName_asc{else}firstName_desc{/if}">
                                    {tr}First Name{/tr}
                                </a>
                            </th>
                            <th>
                                <a href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'lastName_desc'}lastName_asc{else}lastName_desc{/if}">
                                    {tr}Last Name{/tr}
                                </a>
                            </th>
                            <th>
                                <a href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'email_desc'}email_asc{else}email_desc{/if}">
                                    {tr}Email{/tr}
                                </a>
                            </th>
                            <th>
                                <a href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;offset={$offset}&amp;sort_mode={if $sort_mode eq 'nickname_desc'}nickname_asc{else}nickname_desc{/if}">
                                    {tr}Nickname{/tr}
                                </a>
                            </th>
                        </tr>

                        {section name=user loop=$channels}
                            <tr>
                                <td class="text">{$channels[user].firstName}</td>
                                <td class="text">{$channels[user].lastName}</td>
                                <td class="email">
                                    <a class="link" href="#" role="button" onclick="var em = window.opener.document.getElementById('{$element}').value; if (em != '') window.opener.document.getElementById('{$element}').value = window.opener.document.getElementById('{$element}').value + ', {$channels[user].email}'; if (em == '') window.opener.document.getElementById('{$element}').value = window.opener.document.getElementById('{$element}').value + '{$channels[user].email}';">{* TODO: optimize the Javascript code *}
                                        {$channels[user].email}
                                    </a>
                                    [&nbsp;&nbsp;
                                    <form style="display: inline;" action="tiki-webmail_contacts.php" method="post">
                                        {ticket}
                                        <input type="hidden" name="element" value="{$element}">
                                        <input type="hidden" name="section" value="contacts">
                                        <input type="hidden" name="offset" value="{$offset}">
                                        <input type="hidden" name="sort_mode" value="{$sort_mode}">
                                        <input type="hidden" name="find" value="{$find}">
                                        <input type="hidden" name="initial" value="{$setInitial}">
                                        <input type="hidden" name="remove" value="{$channels[user].contactId}">
                                        <button id="deleteButton" type="button" onClick="show_alert(this.form);" class="btn btn-link px-0 pt-0 pb-0" title=":{tr}Delete{/tr}" aria-label="{tr}Delete{/tr}">
                                            {icon name='delete'}
                                        </button>
                                    </form>
                                    <script>
                                        function show_alert(form) {
                                            if(confirm("Are you sure you want to delete this contact?")) {
                                                form.submit();
                                            }
                                        }
                                    </script>
                                    &nbsp;&nbsp;]
                                </td>
                                <td class="text">{$channels[user].nickname}</td>
                            </tr>
                        {/section}
                    </table>
                </div>
                <div class="mx-auto">
                    {if $prev_offset >= 0}
                        [<a class="prevnext" href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;find={$find}&amp;offset={$prev_offset}&amp;sort_mode={$sort_mode}">
                            {tr}Prev{/tr}
                        </a>
                        ]&nbsp;
                    {/if}
                    {tr}Page:{/tr} {$actual_page}/{if $pages_count > 0}{$pages_count}{else}1{/if}
                    {if $next_offset >= 0}
                        &nbsp;[
                        <a class="prevnext" href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;find={$find}&amp;offset={$next_offset}&amp;sort_mode={$sort_mode}">
                            {tr}Next{/tr}
                        </a>]
                    {/if}
                    {if $prefs.direct_pagination eq 'y'}
                        <br>
                        {section loop=$pages_count name=foo}
                            {$selector_offset=$smarty.section.foo.index|times:$prefs.maxRecords}
                            <a class="prevnext" href="tiki-webmail_contacts.php?element={$element}&amp;section=contacts&amp;find={$find}&amp;offset={$selector_offset}&amp;sort_mode={$sort_mode}">
                                {$smarty.section.foo.index_next}
                            </a>&nbsp;
                        {/section}
                    {/if}
                </div>
            </div>
            </div>
        </div>
    </body>
</html>
