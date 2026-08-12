{* Template for Tracker Configuration History (item-161718) *}
{* Renders via service controller: tracker / config_history *}

{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{tr}Tracker Config History{/tr}{/title}
{/block}

{block name="navigation"}
    <div class="t_navbar mb-4">
        {button href="tiki-admin_tracker_fields.php?trackerId=$trackerId" _icon_name="arrow-left" _text="{tr}Return to field administration{/tr}" _class="btn btn-info me-2"}
        {if $fieldId}
            {button href="{service controller=tracker action=config_history trackerId=$trackerId}" _icon_name="history" _text="{tr}View Tracker-Level History{/tr}" _class="btn btn-outline-info"}
        {/if}
        {include file="tracker_actions.tpl"}
    </div>
{/block}

{block name="content"}
<div class="tracker-config-history">
    <h2 class="mb-3">{$title|escape}</h2>

    {if empty($history)}
        <div class="alert alert-info">
            {tr}No configuration changes have been recorded yet.{/tr}
        </div>
    {else}
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th class="text-nowrap">{tr}Date{/tr}</th>
                        <th class="text-nowrap">{tr}User{/tr}</th>
                        <th class="text-nowrap">{tr}Property{/tr}</th>
                        <th class="text-nowrap">{tr}Old{/tr}</th>
                        <th class="text-nowrap">{tr}New{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $history as $entry}
                        {if $entry.hasChanges}
                            {foreach $entry.changes as $change}
                                <tr>
                                    <td class="date text-nowrap"><strong>{$entry.lastModif|tiki_short_datetime}</strong></td>
                                    <td class="username"><strong>{$entry.user|userlink}</strong></td>
                                    <td class="fw-semibold text-nowrap align-middle">{$change.label|escape}</td>
                                    <td class="diff-old text-danger font-monospace small" style="background-color: #f8d7da; white-space:pre-wrap;">- {$change.from|escape}</td>
                                    <td class="diff-new text-success font-monospace small" style="background-color: #d1e7dd; white-space:pre-wrap;">+ {$change.to|escape}</td>
                                </tr>
                            {/foreach}
                        {/if}
                    {/foreach}
                </tbody>
            </table>
        </div>

        {* Pagination *}
        {if $total > 25}
            <nav aria-label="{tr}Config History Pagination{/tr}">
                <ul class="pagination">
                    {if $offset > 0}
                        <li class="page-item">
                            <a class="page-link" href="{service controller=tracker action=config_history trackerId=$trackerId fieldId=$fieldId offset=$offset - 25}">
                                &laquo; {tr}Previous{/tr}
                            </a>
                        </li>
                    {/if}
                    {if $offset + 25 < $total}
                        <li class="page-item">
                            <a class="page-link" href="{service controller=tracker action=config_history trackerId=$trackerId fieldId=$fieldId offset=$offset + 25}">
                                {tr}Next{/tr} &raquo;
                            </a>
                        </li>
                    {/if}
                </ul>
            </nav>
        {/if}
    {/if}
</div>
{/block}
