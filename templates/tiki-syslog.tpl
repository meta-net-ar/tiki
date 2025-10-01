{title help="System Log"}{tr}Tiki Logs{/tr}{/title}

<div class="t_navbar mb-4">
    <a href="tiki-sqllog.php" class="btn btn-link" title="{tr}Log SQL{/tr}">{icon name="pencil"} {tr}Log SQL{/tr}</a>
    {if $prefs.feature_actionlog == 'y'}
        <a href="tiki-admin_actionlog.php#Report" class="btn btn-link" title="{tr}Export through Action Log{/tr}">{icon name="upload"} {tr}Export through Action Log{/tr}</a>
    {/if}
{*    {button class="btn btn-primary" _text="{tr}Log SQL{/tr}" href="tiki-sqllog.php"}
    {button class="btn btn-primary" _text="{tr}Export through Action Log{/tr}" href="tiki-admin_actionlog.php#Report"}
*}</div>

<form method="get" action="tiki-syslog.php" class="mb-4">
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" for="months">{tr}Clean logs older than{/tr}</label>
        <div class="col-sm-8 d-flex flex-row flex-wrap align-items-center m-1">
        <div class="d-flex align-items-center">
            <input type="number" name="months" min="1" step="1" required class="form-control me-2" placeholder="{tr}Enter number of months{/tr}">
            <span class="ms-2">{tr}months{/tr}</span>
        </div>
            <input type="submit" class="btn btn-primary btn-sm ms-4 mt-1" value="{tr}Clean{/tr}" name="clean">
        </div>
    </div>
</form>

{include file='find.tpl'}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}

<div class="table-responsive syslog-table">
    <table class="table">
        <tr>
            <th>{self_link _sort_arg="sort_mode" _sort_field="actionid"}{tr}Id{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="action"}{tr}Type{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="lastModif"}{tr}Time{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="user"}{tr}User{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="comment"}{tr}Message{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="ip"}{tr}IP{/tr}{/self_link}</th>
            <th>{self_link _sort_arg="sort_mode" _sort_field="client"}{tr}Client{/tr}{/self_link}</th>
            <th>Actions</th>
        </tr>

        {section name=ix loop=$list}
            <tr>
                <td class="id">{$list[ix].actionId}</td>
                <td class="text">{$list[ix].action|escape}</td>
                <td class="date"><span title="{$list[ix].lastModif|tiki_long_datetime}">{$list[ix].lastModif|tiki_date_format:"%Y-%m-%d %H:%M:%S"}</span></td>
                <td class="username">{$list[ix].user|userlink}</td>
                <td class="text"><textarea class="form-control" readonly="readonly">{$list[ix].comment|escape:'html'}</textarea></td>
                <td class="text">{$list[ix].ip|escape:"html"}</td>
                <td class="text"><span title="{$list[ix].client|escape:'html'}">{$list[ix].client|truncate:30:"..."|escape:'html'}</span></td>
                <td class="text">
                    {if $list[ix].action == 'profile apply' && strpos($list[ix].log, 'reverted') === false}
                        <form method="post" action="tiki-syslog.php" onsubmit="return confirm('{tr}Are you sure you want to revert{/tr} &QUOT;{$list[ix].action|escape}&QUOT;?');">
                            {ticket}
                            <input type="hidden" name="page" value="profiles">
                            <input type="hidden" name="actionId" value="{$list[ix].actionId}">
                            <input type="submit" class="btn btn-secondary" name="revert" value="{tr}Revert{/tr}">
                        </form>
                    {elseif !empty($list[ix].log)}
                        <a href='#' {popup  text="<pre style=\"width: 700px;\">{$list[ix].log_pretty|escape:'html'}</pre>" trigger="click"} title="{tr}Log Details{/tr}">{icon name='information' alt="{tr}More Information{/tr}" title=''}</a>
                    {else}
                        &nbsp;
                    {/if}
                </td>
                <td>
                    {if $list[ix].action == 'profile apply' && strpos($list[ix].log, 'reverted') === false}
                        <form method="post" action="tiki-syslog.php" onsubmit="return confirm('{tr}Are you sure you want to revert{/tr} &QUOT;{$list[ix].action|escape}&QUOT;?');">
                            {ticket}
                            <input type="hidden" name="page" value="profiles">
                            <input type="hidden" name="actionId" value="{$list[ix].actionId}">
                            <input type="submit" class="btn btn-secondary" name="revert" value="{tr}Revert{/tr}">
                        </form>
                    {/if}
                </td>
            </tr>
        {/section}
    </table>
</div>

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
