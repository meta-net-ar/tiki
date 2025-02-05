{if !empty($field.error)}
<div class="error">{$field.error}</div>
{/if}
{if !$field.id}
    {remarksbox type="info" title="{tr}Item must be created first{/tr}" close="n"}
        <p>{tr}You will be able to manage Tiki instances here once you create the item.{/tr}</p>
    {/remarksbox}
{else}
    {if !empty($field.instances)}
        {foreach $field.instances as $instance}
            <div class="tikimanager-instance">
                <label>Type:</label> {$instance->type|escape}<br/>
                <label>Name:</label> {$instance->name|escape}<br/>
                <label>Branch:</label> {$instance->branch}<br/>
                <label>Revision:</label> {$instance->revision}<br/>
                <div class="btn-group" role="group">
                    <a class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-bs-hover="dropdown" href="#" aria-expanded="false">Actions</a>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="{$instance->weburl}" target="_blank">
                            <span class="icon icon-file-archive-open fas fa-folder-open "></span> Open
                        </a>
                        {if in_array('access', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=access instanceId=$instance->id}">
                                {icon name=tags _menu_text='y' _menu_icon='y' alt="{tr}Access{/tr}"}
                            </a>
                        {/if}
                        {if in_array('profile_apply', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=apply instanceId=$instance->id}">
                                {icon name=user _menu_text='y' _menu_icon='y' alt="{tr}Apply profile{/tr}"}
                            </a>
                        {/if}
                        {if in_array('backup', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=backup instanceId=$instance->id}">
                                {icon name=download _menu_text='y' _menu_icon='y' alt="{tr}Backup{/tr}"}
                            </a>
                        {/if}
                        {if in_array('check', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=verify instanceId=$instance->id}">
                                {icon name=check _menu_text='y' _menu_icon='y' alt="{tr}Verify{/tr}"}
                            </a>
                        {/if}
                        {if in_array('checkout', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=checkout instanceId=$instance->id}">
                                {icon name=logout _menu_text='y' _menu_icon='y' alt="{tr}Checkout{/tr}"}
                            </a>
                        {/if}
                        {if in_array('clone', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=clone instanceId=$instance->id}">
                                {icon name=copy _menu_text='y' _menu_icon='y' alt="{tr}Clone{/tr}"}
                            </a>
                        {/if}
                        {if in_array('console', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=console instanceId=$instance->id}">
                                {icon name=pen _menu_text='y' _menu_icon='y' alt="{tr}Console Command{/tr}"}
                            </a>
                        {/if}
                        {if in_array('delete', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager_field action=delete instanceId=$instance->id itemId=$field.id fieldId=$field.fieldId}">
                                {icon name=times _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                            </a>
                        {/if}
                        {if in_array('detect', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=detect instanceId=$instance->id}">
                                {icon name=info _menu_text='y' _menu_icon='y' alt="{tr}Detect{/tr}"}
                            </a>
                        {/if}
                        {if in_array('edit', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=edit instanceId=$instance->id}">
                                {icon name=edit _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                            </a>
                        {/if}
                        {if in_array('fixpermissions', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=fix instanceId=$instance->id modal=1}">
                                {icon name=wrench _menu_text='y' _menu_icon='y' alt="{tr}Fix{/tr}"}
                            </a>
                        {/if}
                        {if in_array('maintenance', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=maintenance mode=off instanceId=$instance->id}">
                                {icon name=hammer _menu_text='y' _menu_icon='y' alt="{tr}Maintenance Off{/tr}"}
                            </a>
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=maintenance mode=on instanceId=$instance->id}">
                                {icon name=hammer _menu_text='y' _menu_icon='y' alt="{tr}Maintenance On{/tr}"}
                            </a>
                        {/if}
                        {if in_array('patch_list', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager_patch action=index instanceId=$instance->id}">
                                {icon name=tools _menu_text='y' _menu_icon='y' alt="{tr}Patches{/tr}"}
                            </a>
                        {/if}
                        {if in_array('revert', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=revert action=index instanceId=$instance->id}">
                                {icon name=refresh _menu_text='y' _menu_icon='y' alt="{tr}Revert{/tr}"}
                            </a>
                        {/if}
                        {if in_array('update', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=update instanceId=$instance->id}">
                                {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update{/tr}"}
                            </a>
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=update instanceId=$instance->id mode=bg}">
                                {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Update (background){/tr}"}
                            </a>
                        {/if}
                        {if in_array('upgrade', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=upgrade instanceId=$instance->id}">
                                {icon name=import _menu_text='y' _menu_icon='y' alt="{tr}Upgrade{/tr}"}
                            </a>
                        {/if}
                        {if in_array('watch', $field.available_actions)}
                            <a class="dropdown-item" href="{bootstrap_modal controller=manager action=watch instanceId=$instance->id}">
                                {icon name=eye _menu_text='y' _menu_icon='y' alt="{tr}Watch{/tr}"}
                            </a>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    {/if}
    {if in_array('create', $field.available_actions) and !$field.has_created_one}
        {if !empty($field.source)}{assign var=action value=create_source}{else}{assign var=action value=create}{/if}
        <div class="btn-group">
            <a class="btn btn-primary btn-sm dropdown-toggle" id="createInstance" data-bs-toggle="dropdown" data-bs-hover="dropdown" aria-expanded="false" href="#">
                {icon name=create _menu_text='y' _menu_icon='y' alt="{tr}Create new instance{/tr}"}
            </a>
            <div class="dropdown-menu" aria-labelledby="createInstance">
                {foreach $field.versions as $version}
                    <a class="dropdown-item" href="{bootstrap_modal controller=manager_field action=$action itemId=$field.id fieldId=$field.fieldId version=$version}">
                        {$version}
                    </a>
                {/foreach}
            </div>
        </div>
    {/if}
{/if}
