{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    <h2>{tr}Instances{/tr}</h2>
    <div class="table-responsive">
        <table class="table">
            <tr>
                <th>{tr}ID{/tr}</th>
                <th>{tr}Type{/tr}</th>
                <th>{tr}Name{/tr}</th>
                <th>{tr}Branch{/tr}</th>
                <th>{tr}Revision{/tr}</th>
                <th>{tr}URL{/tr}</th>
                <th>{tr}Contact{/tr}</th>
                <th>{tr}Blank{/tr}</th>
                <th></th>
            </tr>
            {foreach $instances as $instance}
                <tr>
                    <td>{$instance->id}</td>
                    <td>{$instance->type|escape}</td>
                    <td>{$instance->name|escape}</td>
                    <td>{$instance->branch}</td>
                    <td>{$instance->revision}</td>
                    <td><a href="{$instance->weburl}" title="Visit website" target="_blank">{$instance->weburl}</a></td>
                    <td>{$instance->contact|escape}</td>
                    <td>{if $instance->app != null }{tr}False{/tr}{else}{tr}True{/tr}{/if}</td>
                    <td class="action">
                        {actions}{strip}
                            <action>
                                <a href="{service controller=manager action=access instanceId=$instance->id}">
                                    {icon name=tags _menu_text='y' _menu_icon='y' alt="{tr}Access{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=apply instanceId=$instance->id}">
                                    {icon name=user _menu_text='y' _menu_icon='y' alt="{tr}Apply profile{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=backup instanceId=$instance->id}">
                                    {icon name=download _menu_text='y' _menu_icon='y' alt="{tr}Backup{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=verify instanceId=$instance->id}">
                                    {icon name=check _menu_text='y' _menu_icon='y' alt="{tr}Verify{/tr}"}
                                </a>
                            </action>
                            {if $instance->vcs_type =='git'}
                            <action>
                                <a href="{service controller=manager action=checkout instanceId=$instance->id}">
                                    {icon name=logout _menu_text='y' _menu_icon='y' alt="{tr}Checkout{/tr}"}
                                </a>
                            </action>
                            {/if}
                            <action>
                                <a href="{service controller=manager action=clone instanceId=$instance->id}">
                                    {icon name=copy _menu_text='y' _menu_icon='y' alt="{tr}Clone{/tr}"}
                                </a>
                            </action>
                            {if $instance->app != null}
                            <action>
                                <a href="{service controller=manager action=console instanceId=$instance->id}">
                                    {icon name=pen _menu_text='y' _menu_icon='y' alt="{tr}Console Command{/tr}"}
                                </a>
                            </action>
                            {/if}
                            <action>
                                <a href="{service controller=manager action=temporaryuser instanceId=$instance->id}">
                                    {icon name=user _menu_text='y' _menu_icon='y' alt="{tr}Create Temporary User{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=delete instanceId=$instance->id}"
                                    aria-label="{tr}Delete{/tr}"
                                    onclick="confirmPopup('{tr}Are you sure you want to delete this instance?{/tr}', '{ticket mode=get}');">
                                    {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=detect instanceId=$instance->id}">
                                    {icon name=info _menu_text='y' _menu_icon='y' alt="{tr}Detect{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=edit instanceId=$instance->id}">
                                    {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=fix instanceId=$instance->id}">
                                    {icon name=wrench _menu_text='y' _menu_icon='y' alt="{tr}Fix{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=maintenance mode=off instanceId=$instance->id}">
                                    {icon name=hammer _menu_text='y' _menu_icon='y' alt="{tr}Maintenance Off{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=maintenance mode=on instanceId=$instance->id}">
                                    {icon name=hammer _menu_text='y' _menu_icon='y' alt="{tr}Maintenance On{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager_patch action=index instanceId=$instance->id}">
                                    {icon name=tools _menu_text='y' _menu_icon='y' alt="{tr}Patches{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=revert instanceId=$instance->id}">
                                    {icon name=refresh _menu_text='y' _menu_icon='y' alt="{tr}Revert{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=update instanceId=$instance->id}">
                                    {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=upgrade instanceId=$instance->id}">
                                    {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Upgrade{/tr}"}
                                </a>
                            </action>
                            <action>
                                <a href="{service controller=manager action=watch instanceId=$instance->id}">
                                    {icon name=eye _menu_text='y' _menu_icon='y' alt="{tr}Watch{/tr}"}
                                </a>
                            </action>
                        {/strip}{/actions}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="8">{tr}No instances defined.{/tr}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/block}
