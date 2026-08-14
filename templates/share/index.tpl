{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title help="Share"}{tr}Shared URLs{/tr}{/title}
{/block}

{if $tiki_p_remove_share == 'y'}
    {$checkboxes_on='y'}
{/if}

{block name="content"}
    <form name="checkform" id="checkform" method="post">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        {if isset($checkboxes_on) and $checkboxes_on eq 'y'}
                            <th class="auto">
                                <div class="form-check">
                                    {select_all checkbox_names='checked[]'}
                                </div>
                            </th>
                        {/if}
                        <th>{tr}ID{/tr}</th>
                        <th>{tr}Shared link{/tr}</th>
                        <th>{tr}Sender{/tr}</th>
                        <th>{tr}Receiver{/tr}</th>
                        <th>{tr}Shared Via{/tr}</th>
                        <th>{tr}Shared Date{/tr}</th>
                        <th>{tr}Timeout{/tr}</th>
                        <th>{tr}Hits left{/tr}</th>
                        <th>{tr}Max hits{/tr}</th>
                        <th>{tr}User{/tr}</th>
                        <th>{tr}Groups{/tr}</th>
                        <th>{tr}Actions{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    {if $tokens|@count == 0}
                        <tr>
                            <td colspan="13" class="text-center">{tr}No shared URLs found.{/tr}</td>
                        </tr>
                    {else}
                        {foreach $tokens as $token}
                            <tr>
                                {if isset($checkboxes_on) and $checkboxes_on eq 'y'}
                                    <td>
                                        <div>
                                            <input class="checkboxes" type="checkbox" name="checked[]" value="{$token.tokenId|escape}" {if isset($token.tokenId.checked) && $token.tokenId.checked eq 'y'}checked="checked"{/if} />
                                        </div>
                                    </td>
                                {/if}
                                <td>{$token.tokenId}</td>
                                <td><a href="{$token.url}" target="_blank">{$token.url}</a></td>
                                <td>{$token.data.sender}</td>
                                <td>{$token.data.userto|userlink}</td>
                                <td>{$token.data.destination}</td>
                                <td>{$token.creation|tiki_short_datetime}</td>
                                <td>{if !empty($token.expires)}{$token.expires|tiki_short_datetime}{else}{tr}none{/tr}{/if}</td>
                                <td>{$token.hits}</td>
                                <td>{$token.maxhits}</td>
                                <td>{$token.user|userlink}</td>
                                <td>{$token.groups}</td>
                                <td>
                                    {actions}
                                        {strip}
                                            <action>
                                                <a href="{$token.token_url}" target="_blank">
                                                    {icon name='eye' _menu_text='y' _menu_icon='y' alt="{tr}View{/tr}"}
                                                </a>
                                            </action>
                                            <action>
                                                <a href="{bootstrap_modal controller=share action=remove checked=$token.tokenId}">
                                                    {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                                </a>
                                            </action>
                                        {/strip}
                                    {/actions}
                                </td>
                            </tr>
                        {/foreach}
                    {/if}
                </tbody>
            </table>
        </div>
        {if $tokens|@count > 0 and $tiki_p_remove_share == 'y'}
            <div>
                <div class="col-lg-9 input-group">
                    <select name="action" class="form-select">
                        <option value="no_action">{tr}Select action to perform with checked{/tr}...</option>
                        <option value="remove">{tr}Remove{/tr}</option>
                    </select>
                    <button
                        type="submit"
                        form="checkform"
                        formaction="{bootstrap_modal controller=share}"
                        class="btn btn-primary"
                        onclick="confirmPopup()"
                    >
                        {tr}OK{/tr}
                    </button>
                </div>
            </div>
        {/if}
    </form>
    {pagination_links cant=$cant step=$prefs.maxRecords}{/pagination_links}
{/block}
