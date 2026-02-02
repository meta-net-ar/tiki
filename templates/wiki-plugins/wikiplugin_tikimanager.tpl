<div id="tikimanager_container_{$id}">
    {if in_array('create', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=create}">{icon name=create} {tr}New Instance{/tr}</a>
    {/if}
    {if in_array('info', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=info}">{icon name=info} {tr}Info{/tr}</a>
    {/if}
    {if in_array('check', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=requirements}">{icon name=check} {tr}Check Requirements{/tr}</a>
    {/if}
    {if in_array('tiki_versions', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=tiki_versions}">{icon name=list} {tr}Tiki Versions{/tr}</a>
    {/if}
    {if in_array('test_send_email', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=test_send_email}">{icon name=envelope} {tr}Test Send Email{/tr}</a>
    {/if}
    {if in_array('setup_clone', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=setup_clone}">{icon name=copy} {tr}Setup instance to clone{/tr}</a>
    {/if}
    {if in_array('setup_watch', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=setup_watch}">{icon name="clock-o"} {tr}Setup Watch{/tr}</a>
    {/if}
    {if in_array('manager_backup', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=manager_backup}">{icon name=download} {tr}Setup Backup{/tr}</a>
    {/if}

    {if in_array('clear_cache', $available_actions)}
        <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=clear_cache}" title="{tr}Clear Tiki Manager cache. This can be useful for testing and debugging during development, or if your server is short on disk space and you need a temporary relief.{/tr}">
            {icon name=trash} {tr}Clear Cache{/tr}
        </a>
    {/if}
    {if in_array('manager_update', $available_actions)}
    <a class="btn btn-light m-1" role="button" href="{bootstrap_modal controller=manager action=manager_update}">{icon name=import} {tr}Setup Update{/tr}</a>
    {/if}
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
                        {if in_array('access', $available_actions)}
                        <action>
                            <a href="{bootstrap_modal controller=manager action=access instanceId=$instance->id}" onclick="$('[data-toggle=popover]').popover('hide');">
                                {icon name=tags _menu_text='y' _menu_icon='y' alt="{tr}Access{/tr}"}
                            </a>
                        </action>
                        {/if}
                        {if in_array('profile_apply', $available_actions)}
                        <action>
                            <a href="{bootstrap_modal controller=manager action=apply instanceId=$instance->id}" onclick="$('[data-toggle=popover]').popover('hide');">
                                {icon name=user _menu_text='y' _menu_icon='y' alt="{tr}Apply profile{/tr}"}
                            </a>
                        </action>
                        {/if}
                        {if in_array('backup', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=backup instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=download _menu_text='y' _menu_icon='y' alt="{tr}Backup{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('check', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=verify instanceId=$instance->id}">
                                    {icon name=check _menu_text='y' _menu_icon='y' alt="{tr}Verify{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('checkout', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=checkout instanceId=$instance->id}">
                                    {icon name=logout _menu_text='y' _menu_icon='y' alt="{tr}Checkout{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('clone', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=clone instanceId=$instance->id}">
                                    {icon name=copy _menu_text='y' _menu_icon='y' alt="{tr}Clone{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('console', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=console instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=pen _menu_text='y' _menu_icon='y' alt="{tr}Console Command{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('delete', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=delete instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('detect', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=detect instanceId=$instance->id}">
                                    {icon name=info _menu_text='y' _menu_icon='y' alt="{tr}Detect{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('edit', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=edit instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('fixpermissions', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=fix instanceId=$instance->id modal=1}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=wrench _menu_text='y' _menu_icon='y' alt="{tr}Fix{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('maintenance', $available_actions)}
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
                        {/if}
                        {if in_array('patch_list', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager_patch action=index instanceId=$instance->id}" onclick="$('[data-toggle=popover]').popover('hide');">
                                    {icon name=tools _menu_text='y' _menu_icon='y' alt="{tr}Patches{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('revert', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=revert instanceId=$instance->id}">
                                    {icon name=refresh _menu_text='y' _menu_icon='y' alt="{tr}Revert{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('update', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=update instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('upgrade', $available_actions)}
                            <action>
                                <a href="{bootstrap_modal controller=manager action=upgrade instanceId=$instance->id}" onclick="$('[data-bs-toggle=popover]').popover('hide');">
                                    {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Upgrade{/tr}"}
                                </a>
                            </action>
                        {/if}
                        {if in_array('watch', $available_actions)}
                            <action>
                                <a href="{service controller=manager action=watch instanceId=$instance->id}">
                                    {icon name=eye _menu_text='y' _menu_icon='y' alt="{tr}Watch{/tr}"}
                                </a>
                            </action>
                        {/if}
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
</div>
