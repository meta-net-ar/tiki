{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <div class="table-responsive">
        <table class="table table-striped">
            <tr>
                <th>{tr}Token{/tr}</th>
                <th>{tr}User{/tr}</th>
                <th>{tr}Valid until{/tr}</th>
                <th>{tr}Hits{/tr}</th>
                <th>{tr}Created{/tr}</th>
                <th>{tr}Modified{/tr}</th>
                <th>{tr}Edit{/tr}</th>
                <th>{tr}Delete{/tr}</th>
            </tr>
            {foreach $tokens as $token}
                <tr>
                    <td>
                        <span class="text-muted">{tr}Hidden after creation{/tr}</span>
                    </td>
                    <td>
                        {$token.user|escape}
                    </td>
                    <td>
                        {if !empty($token.expireAfter)}
                            {$token.expireAfter|tiki_short_datetime}
                        {/if}
                    </td>
                    <td>
                        {$token.hits}
                    </td>
                    <td>
                        {$token.created|tiki_short_datetime}
                    </td>
                    <td>
                        {$token.lastModif|tiki_short_datetime}
                    </td>
                    <td>
                        <a href="{bootstrap_modal controller=api_token action=edit tokenId=$token.tokenId size='modal-lg'}">
                            {icon name="pencil"}
                        </a>
                    </td>
                    <td>
                        <a href="{service controller=api_token action=delete tokenId=$token.tokenId}" class="btn btn-link text-danger js-remove-token">
                            {icon name='delete'}
                        </a>
                    </td>
                </tr>
            {foreachelse}
                {norecords _colspan=8}
            {/foreach}
        </table>
    </div>
    <p>
        <a class="btn btn-info" href="{bootstrap_modal controller=api_token action=new size='modal-lg'}" role="button">
            {icon name="create"} {tr}Create Token{/tr}
        </a>
    </p>
    {if $tokens}
        <form method="post" action="{service controller=api_token action=revoke_all}" class="d-inline">
            {ticket}
            <button type="submit" class="btn btn-danger" onclick="return confirm('{tr}Revoke every API and OAuth token? This cannot be undone.{/tr}')">
                {icon name="delete"} {tr}Revoke all API and OAuth tokens{/tr}
            </button>
        </form>
    {/if}
{/block}
