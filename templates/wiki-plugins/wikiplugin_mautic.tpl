{if $type eq 'contacts'}
    <div id="mautic_contact_container}">
        <div class="d-flex justify-content-between mb-3">
            <h2>{tr}Contacts{/tr}</h2>
            <div>
                {if in_array('create', $available_actions)}
                    <a role="button" class="btn btn-primary" href="#">{icon name=create} {tr}New Contact{/tr}</a>
                {/if}
                {if in_array('sync', $available_actions)}
                    <a role="button" class="btn btn-primary" href="#">{icon name=sync} {tr}Sync data{/tr}</a>
                {/if}
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover table-bordered">
                <tr>
                    <th>{tr}ID{/tr}</th>
                    <th>{tr}Email{/tr}</th>
                    <th>{tr}Fullname{/tr}</th>
                    <th>{tr}Company{/tr}</th>
                    <th>{tr}Domain Name{/tr}</th>
                    <th>{tr}Points{/tr}</th>
                    <th>{tr}Actions{/tr}</th>
                </tr>
                {foreach $contacts as $contact}
                <tr>
                    <td>{$contact->id}</td>
                    <td>{$contact->email|escape}</td>
                    <td>{$contact->fullname}</td>
                    <td>{$contact->company|escape}</td>
                    <td>{$contact->domain|escape}</td>
                    <td>{$contact->points}</td>
                    <td class="action">
                        {actions}{strip}
                            {if in_array('info', $available_actions)}
                                <action>
                                    <a href="#" role="button">
                                        {icon name=eye _menu_text='y' _menu_icon='y' alt="{tr}Info{/tr}"}
                                    </a>
                                </action>
                            {/if}
                        {/strip}{/actions}
                    </td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="7">{tr}No contacts found.{/tr}</td>
                </tr>
                {/foreach}
            </table>
        </div>
    </div>

{/if}
