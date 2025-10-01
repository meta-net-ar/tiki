{title help="Token Access"}{tr}Admin Tokens{/tr}{/title}

{tabset name="tabs_admtokens"}
    {tab name="{tr}List tokens{/tr}"}
        <h2>{tr}List tokens{/tr}</h2>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th> {icon name="link" ititle='{tr}Copy link{/tr}' alt='{tr}Copy link{/tr}'}</th>
                        <th>{tr}Id{/tr}</th>
                        <th>{tr}Entry{/tr}</th>
                        <th>{tr}Email{/tr}</th>
                        <th>{tr}Timeout{/tr}</th>
                        <th>{tr}Token{/tr}</th>
                        <th>{tr}Creation{/tr}</th>
                        <th>{tr}Hits left{/tr}</th>
                        <th>{tr}Max hits{/tr}</th>
                        <th>{tr}Parameters{/tr}</th>
                        <th>{tr}Groups{/tr}</th>
                        <th>{tr}Create Temp User{/tr}</th>
                        <th>{tr}Temp User Prefix{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $tokens as $token}
                        <tr>
                            <td class="action">
                                {actions}
                                {strip}
                                    <action>
                                        <a href="{$token.token_url}">
                                            {icon name='eye' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                        </a>
                                    </action>
                                    <action>
                                        {self_link tokenId=$token.tokenId action='delete' _menu_text='n' _menu_icon='y' _icon_name='remove' _title='{tr}Delete{/tr}' _text='{tr}Delete{/tr}'}
                                        {/self_link}
                                    </action>
                                {/strip}
                                {/actions}
                            </td>
                            <td>
                                <span id="token-{$token.tokenId}" class="visually-hidden">{$token.full_url}</span>
                                <a href="javascript:void(0)" class="copy" data-clipboard-action="copy" data-clipboard-target="#token-{$token.tokenId}">
                                    {icon name="copy" ititle='{tr}Copy URL{/tr}' alt='{tr}Copy URL{/tr}' iclass="text-primary"}
                                </a>
                            </td>
                            <td>{$token.tokenId}</td>
                            <td>{$token.entry}</td>
                            <td>{$token.email}</td>
                            <td>{if !empty($token.expires)}{$token.expires|tiki_short_datetime}{else}{tr}none{/tr}{/if}</td>
                            <td style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{$token.token}">
                                {$token.token}
                            </td>
                            <td>{$token.creation|tiki_short_datetime}</td>
                            <td>{$token.hits}</td>
                            <td>{$token.maxhits}</td>
                            <td>
                                {foreach $token.parameters as $key => $value}
                                    {if is_array($value)}
                                        {$key}=[{$value|join:","}]<br>
                                    {else}
                                        {$key}={$value}<br>
                                    {/if}
                                {/foreach}
                            </td>
                            <td>{$token.groups}</td>
                            <td>{$token.createUser}</td>
                            <td>{$token.userPrefix}</td>
                        </tr>
                    {foreachelse}
                    {norecords _colspan=10}
                {/foreach}
                </tbody>
            </table>
        </div>
    {/tab}
    {tab name="{tr}Add new token{/tr}"}
        <h2>{tr}Add new token{/tr}</h2>

        {if $tokenCreated}
            {remarksbox type="note" title="{tr}Note{/tr}"}
                {tr}Token successfully created.{/tr}
            {/remarksbox}
        {/if}

        <form action="tiki-admin_tokens.php" method="post">
            <input type="hidden" name="action" value="add">
            <div class="tiki-form-group row">
                <label class="col-sm-4 col-form-label" for="entry">{tr}Full URL{/tr}</label>
                <div class="col-sm-7 offset-sm-1">
                    <input type="text" id='entry' name='entry' class="form-control" required>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-sm-4 col-form-label" for="timeout">{tr}Timeout in seconds (-1 for unlimited){/tr}</label>
                <div class="col-sm-7 offset-sm-1">
                    <input type="number" id='timeout' name='timeout' class="form-control">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-sm-4 col-form-label" for="maxhits">{tr}Maximum number of hits (-1 for unlimited){/tr}</label>
                <div class="col-sm-7 offset-sm-1">
                    <input type="number" id='maxhits' name='maxhits' class="form-control">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-sm-4 col-form-label" for="groups">{tr}Groups{/tr}</label>
                <div class="col-sm-7 offset-sm-1">
                    <select name="groups[]" id="groups" class="form-control" multiple="multiple" required>
                        {foreach item=groupName from=$groups}
                            <option value="{$groupName|escape}">{$groupName|escape}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-sm-4 col-form-label"></label>
                <div class="col-sm-7 offset-sm-1">
                    <input type="submit" class="btn btn-primary btn-sm" value="{tr}Add{/tr}">
                </div>
            </div>
        </form>
    {/tab}
{/tabset}
