{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title help="Trackers" url="tiki-tracker_overview.php?trackerId=`$trackerId`"}{tr}Tracker Configuration Overview:{/tr} {$tracker_info.name|escape}{/title}
{/block}

{block name="navigation"}
    <div class="t_navbar mb-4">
        {include file="tracker_actions.tpl"}
    </div>
{/block}

{block name="content"}

{* ─────────────────────────────────────────────────────────────────
   SECTION 1 — ITEM PERMISSIONS
   ───────────────────────────────────────────────────────────────── *}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{icon name="permission"} {tr}Item Permissions{/tr}</h5>
        {if $tiki_p_admin_trackers eq 'y'}
            <a class="btn btn-sm btn-outline-secondary"
               href="tiki-objectpermissions.php?objectName={$trackerId|escape:'url'}&amp;objectType=tracker&amp;permType=trackers&amp;objectId={$trackerId|escape:'url'}">
                {icon name="settings"} {tr}Manage permissions{/tr}
            </a>
        {/if}
    </div>
    <div class="card-body">

        {* Permission source notice *}
        {if $perms_from eq 'object'}
            <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                {icon name="warning"}&nbsp;
                {tr}Individual permissions are set for this tracker and <strong>override global permissions</strong>.{/tr}
            </div>
            <div class="table-responsive">
                <table class="table table-condensed table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{tr}Permission{/tr}</th>
                            <th>{tr}Groups granted{/tr}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$tracker_perm_definitions key=perm_name item=perm_label}
                            {if isset($object_permissions[$perm_name])}
                                <tr>
                                    <td>{$perm_label|escape}</td>
                                    <td>
                                        {foreach from=$object_permissions[$perm_name] item=grp name=grploop}
                                            <span class="badge bg-primary me-1">{$grp|escape}</span>
                                        {/foreach}
                                    </td>
                                </tr>
                            {/if}
                        {/foreach}
                        {if $object_permissions|@count eq 0}
                            <tr>
                                <td colspan="2" class="text-muted fst-italic">
                                    {tr}No individual permissions configured yet. Click "Manage permissions" to add them.{/tr}
                                </td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        {elseif $perms_from eq 'category'}
            <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                {icon name="category"}&nbsp;
                {tr}This tracker inherits permissions from its <strong>category</strong>. No individual permissions are set.{/tr}
            </div>
        {else}
            <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                {icon name="permission"}&nbsp;
                {tr}No individual permissions are set. <strong>Global permissions</strong> apply to all groups.{/tr}
            </div>
        {/if}

        {* Special ownership/writer rules from tracker options *}
        <h6 class="mt-4 mb-2 text-muted text-uppercase small fw-semibold">{tr}Ownership &amp; Writer Rules{/tr}</h6>
        <ul class="list-group list-group-flush">
            {foreach from=$special_rules item=rule}
                <li class="list-group-item d-flex align-items-center px-0 py-1 border-0">
                    {if $rule.value eq 'y'}
                        <span class="text-success me-2">{icon name="check"}</span>
                    {else}
                        <span class="text-secondary me-2">{icon name="remove"}</span>
                    {/if}
                    <span class="{if $rule.value eq 'y'}fw-semibold{else}text-muted{/if}">{$rule.label|escape}</span>
                </li>
            {/foreach}
        </ul>

    </div>
</div>

{* ─────────────────────────────────────────────────────────────────
   SECTION 2 — FIELD PERMISSIONS
   ───────────────────────────────────────────────────────────────── *}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{icon name="th-list"} {tr}Field Permissions{/tr}</h5>
        {if $tiki_p_admin_trackers eq 'y'}
            <a class="btn btn-sm btn-outline-secondary" href="{$trackerId|sefurl:'trackerfields'}">
                {icon name="settings"} {tr}Manage fields{/tr}
            </a>
        {/if}
    </div>
    <div class="card-body">
        {if $restricted_fields}
            <p class="text-muted mb-3">
                {tr}The following fields have access restrictions. Fields not listed here are visible and editable by anyone who can access the tracker item.{/tr}
            </p>
            <div class="table-responsive">
                <table class="table table-condensed table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{tr}Field{/tr}</th>
                            <th>{tr}Type{/tr}</th>
                            <th>{tr}Visible by{/tr}</th>
                            <th>{tr}Editable by{/tr}</th>
                            <th>{tr}Access mode{/tr}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$restricted_fields item=field}
                            <tr>
                                <td>
                                    <strong>{$field.name|escape}</strong>
                                    <span class="text-muted small ms-1">#{$field.fieldId|escape}</span>
                                </td>
                                <td>
                                    <code>{$field.type|escape}</code>
                                </td>
                                <td>
                                    {if $field.visibleBy}
                                        {foreach from=$field.visibleBy item=grp}
                                            <span class="badge bg-info text-dark me-1">{$grp|escape}</span>
                                        {/foreach}
                                    {elseif $field.isHidden eq 'y' or $field.isHidden eq 'p'}
                                        <span class="badge bg-danger">{tr}Admin only{/tr}</span>
                                    {elseif $field.isHidden eq 'c'}
                                        <span class="badge bg-warning text-dark">{tr}Creator only{/tr}</span>
                                    {else}
                                        <span class="text-muted">{tr}All{/tr}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $field.editableBy}
                                        {foreach from=$field.editableBy item=grp}
                                            <span class="badge bg-info text-dark me-1">{$grp|escape}</span>
                                        {/foreach}
                                    {elseif $field.isHidden eq 'y' or $field.isHidden eq 'p'}
                                        <span class="badge bg-danger">{tr}Admin only{/tr}</span>
                                    {elseif $field.isHidden eq 'c'}
                                        <span class="badge bg-warning text-dark">{tr}Creator only{/tr}</span>
                                    {elseif $field.isHidden eq 'i' or $field.isHidden eq 'a'}
                                        <span class="badge bg-secondary">{tr}Insert only{/tr}</span>
                                    {else}
                                        <span class="text-muted">{tr}All{/tr}</span>
                                    {/if}
                                </td>
                                <td>
                                    {if $field.isHidden eq 'y'}
                                        <span class="badge bg-danger" title="{tr}Hidden from everyone except tracker administrators{/tr}">{tr}Admin only{/tr}</span>
                                    {elseif $field.isHidden eq 'c'}
                                        <span class="badge bg-warning text-dark" title="{tr}Visible and editable by the item creator only{/tr}">{tr}Creator only{/tr}</span>
                                    {elseif $field.isHidden eq 'i'}
                                        <span class="badge bg-secondary" title="{tr}Can be set on creation but cannot be changed afterward{/tr}">{tr}Immutable{/tr}</span>
                                    {elseif $field.isHidden eq 'a'}
                                        <span class="badge bg-info text-dark" title="{tr}Visible to all, but only tracker administrators can edit it after creation{/tr}">{tr}Admin edit after creation{/tr}</span>
                                    {elseif $field.isHidden eq 'p'}
                                        <span class="badge bg-dark" title="{tr}Always restricted to tracker administrators{/tr}">{tr}Protected{/tr}</span>
                                    {else}
                                        <span class="text-muted">—</span>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="text-muted mb-0">
                {icon name="check"} {tr}All fields are visible and editable by anyone who can access the tracker item. No field-level restrictions are configured.{/tr}
            </p>
        {/if}
    </div>
</div>

{* ─────────────────────────────────────────────────────────────────
   SECTION 3 — COMMENTS
   ───────────────────────────────────────────────────────────────── *}
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">{icon name="comment"} {tr}Comments{/tr}</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-condensed table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>{tr}Action{/tr}</th>
                        <th>{tr}Governed by{/tr}</th>
                        <th>{tr}Additional rules{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{tr}View comments{/tr}</td>
                        <td>
                            <code>tiki_p_tracker_view_comments</code>
                            {if $perms_from eq 'object' and isset($object_permissions['tiki_p_tracker_view_comments'])}
                                <br>
                                {foreach from=$object_permissions['tiki_p_tracker_view_comments'] item=grp}
                                    <span class="badge bg-primary me-1">{$grp|escape}</span>
                                {/foreach}
                            {elseif $perms_from eq 'object'}
                                <span class="text-muted small">{tr}(not explicitly set){/tr}</span>
                            {/if}
                        </td>
                        <td class="text-muted small">
                            {tr}Also granted when:{/tr}
                            <code>tiki_p_comment_tracker_items</code>{if $view_comment_additional}, {$view_comment_additional|escape}{/if}
                        </td>
                    </tr>
                    <tr>
                        <td>{tr}Post comments{/tr}</td>
                        <td>
                            <code>tiki_p_comment_tracker_items</code>
                            {if $perms_from eq 'object' and isset($object_permissions['tiki_p_comment_tracker_items'])}
                                <br>
                                {foreach from=$object_permissions['tiki_p_comment_tracker_items'] item=grp}
                                    <span class="badge bg-primary me-1">{$grp|escape}</span>
                                {/foreach}
                            {elseif $perms_from eq 'object'}
                                <span class="text-muted small">{tr}(not explicitly set){/tr}</span>
                            {/if}
                        </td>
                        <td class="text-muted small">
                            {tr}Also granted when:{/tr}
                            {$post_comment_additional|escape}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{* ─────────────────────────────────────────────────────────────────
   SECTION 4 — NOTIFICATIONS
   ───────────────────────────────────────────────────────────────── *}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{icon name="email"} {tr}Notifications{/tr}</h5>
        {if $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y'}
            <a class="btn btn-sm btn-outline-secondary"
               href="tiki-object_watches.php?objectId={$trackerId|escape:'url'}&amp;watch_event=tracker_modified&amp;objectType=tracker&amp;objectName={$tracker_info.name|escape:'url'}&amp;objectHref={'tiki-view_tracker.php?trackerId='|cat:$trackerId|escape:'url'}">
                {icon name="watch-group"} {tr}Manage group watches{/tr}
            </a>
        {/if}
    </div>
    <div class="card-body">

        {* User watches *}
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">{tr}User Watches{/tr}</h6>
        {if $user_watches}
            <div class="table-responsive mb-3">
                <table class="table table-condensed table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{tr}User{/tr}</th>
                            <th>{tr}Email{/tr}</th>
                            <th>{tr}Trigger{/tr}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$user_watches item=watch}
                            <tr>
                                <td>{$watch.user|escape}</td>
                                <td>{$watch.email|escape}</td>
                                <td><span class="badge bg-secondary">{tr}Any item change{/tr}</span></td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="text-muted mb-3">{tr}No individual users are watching this tracker.{/tr}</p>
        {/if}

        {* Group watches *}
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">{tr}Group Watches{/tr}</h6>
        {if $group_watches}
            <div class="mb-3">
                {foreach from=$group_watches item=watch}
                    <span class="badge bg-primary me-1">{$watch.group|escape}</span>
                {/foreach}
                <span class="text-muted small ms-1">— {tr}all members notified on any item change{/tr}</span>
            </div>
        {else}
            <p class="text-muted mb-3">{tr}No group watches are set on this tracker.{/tr}</p>
        {/if}

        {* Outbound (external) emails *}
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">{tr}Outbound Emails (from tracker settings){/tr}</h6>
        {if $outbound_emails}
            <div class="mb-3">
                {foreach from=$outbound_emails item=email}
                    <span class="badge bg-secondary me-1">{$email|escape}</span>
                {/foreach}
                <span class="text-muted small ms-1">— {tr}notified on every item change{/tr}</span>
            </div>
        {else}
            <p class="text-muted mb-3">{tr}No outbound email addresses configured.{/tr}</p>
        {/if}

        {* Field-triggered local notifications *}
        <h6 class="text-muted text-uppercase small fw-semibold mb-2">{tr}Field-Triggered Notifications{/tr}</h6>
        {if $notification_fields}
            <div class="table-responsive">
                <table class="table table-condensed table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{tr}Field{/tr}</th>
                            <th>{tr}Type{/tr}</th>
                            <th>{tr}Mode{/tr}</th>
                            <th>{tr}Trigger{/tr}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$notification_fields item=field}
                            <tr>
                                <td>
                                    <strong>{$field.name|escape}</strong>
                                    <span class="text-muted small ms-1">#{$field.fieldId|escape}</span>
                                </td>
                                <td>
                                    {if $field.type eq 'u'}
                                        <span class="badge bg-info text-dark">{tr}User selector{/tr}</span>
                                    {else}
                                        <span class="badge bg-info text-dark">{tr}Email{/tr}</span>
                                    {/if}
                                </td>
                                <td>{$field._notify_mode|escape}</td>
                                <td>{$field._notify_trigger|escape}</td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="text-muted mb-0">{tr}No fields are configured to send local notifications.{/tr}</p>
        {/if}

    </div>
</div>

{* ─────────────────────────────────────────────────────────────────
   SECTION 5 — CONFIGURATION HISTORY
   ───────────────────────────────────────────────────────────────── *}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="mb-0">{icon name="history"} {tr}Configuration History{/tr}</h5>
        <a class="btn btn-sm btn-outline-secondary" href="{$config_history_full_url|escape}">
            {if $config_history_total gt 5}{tr}All{/tr} {$config_history_total} {tr}entries{/tr} &rarr;{else}{tr}Full log{/tr}{/if}
        </a>
    </div>
    <div class="card-body">

        {if $config_history}
            <div class="table-responsive">
                <table class="table table-condensed table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">{tr}Date{/tr}</th>
                            <th class="text-nowrap">{tr}User{/tr}</th>
                            <th>{tr}Property{/tr}</th>
                            <th>{tr}Old{/tr}</th>
                            <th>{tr}New{/tr}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$config_history item=entry}
                            {if $entry.action eq 'Created'}
                                <tr>
                                    <td class="text-nowrap"><strong>{$entry.lastModif|tiki_short_datetime}</strong></td>
                                    <td>{$entry.user|userlink}</td>
                                    <td colspan="3">
                                        <span class="badge bg-success me-1">{tr}Created{/tr}</span>
                                        <em class="text-muted small">{tr}Initial configuration recorded.{/tr}</em>
                                    </td>
                                </tr>
                            {elseif $entry.hasChanges}
                                {foreach from=$entry.changes item=change}
                                    <tr>
                                        <td class="text-nowrap small"><strong>{$entry.lastModif|tiki_short_datetime}</strong></td>
                                        <td class="small">{$entry.user|userlink}</td>
                                        <td class="fw-semibold small text-nowrap">{$change.label|escape}</td>
                                        <td class="font-monospace small text-danger" style="background-color:#f8d7da;white-space:pre-wrap;">- {$change.from|escape}</td>
                                        <td class="font-monospace small text-success" style="background-color:#d1e7dd;white-space:pre-wrap;">+ {$change.to|escape}</td>
                                    </tr>
                                {/foreach}
                            {/if}
                        {/foreach}
                    </tbody>
                </table>
            </div>
        {else}
            <p class="text-muted mb-0">{tr}No configuration changes have been recorded yet.{/tr}</p>
        {/if}

    </div>
</div>

{/block}
