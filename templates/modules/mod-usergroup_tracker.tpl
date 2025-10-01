{if $user}
{if !isset($tpl_module_title)}{$tpl_module_title="{tr}Login{/tr}"}{/if}
{tikimodule error=$module_params.error title=$tpl_module_title name="login_infos" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

<div><a class="linkmodule" href="tiki-logout.php">{tr}Log out{/tr}</a></div>

{if $prefs.userTracker eq 'y'}
<div>{tr}User information:{/tr} </div>
<div>
&nbsp;&nbsp;<a href="tiki-view_tracker_item.php?view=+user" class="linkmodule">{$user|escape}</a>
</div>
{/if}


{if $prefs.groupTracker eq 'y'}
<div>{tr}Group information:{/tr}</div>
<div class="card-body">
{if $user_groups && count($user_groups) > 0}
    {foreach from=$user_groups item=group}
        &nbsp;&nbsp;<a href="tiki-view_tracker_item.php?view=+group&group={$group|escape}" class="linkmodule">{$group|escape}</a><br>
    {/foreach}
{else}
    &nbsp;&nbsp;<span class="text-muted">{tr}No groups available{/tr}</span>
{/if}
</div>
{/if}

{/tikimodule}
{/if}

{if $prefs.groupTracker eq 'n' and $prefs.userTracker eq 'n'}
<a href="tiki-admin.php?page=login" class="linkmodule">{tr}You need to activate user and/or group trackers.{/tr}</a>
{/if}
