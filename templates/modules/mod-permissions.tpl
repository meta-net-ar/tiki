{if isset($pagePermissions)}
    {tikimodule error=$module_params.error title=$tpl_module_title name="permissions" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle style=$module_params.style}
        {if $pagePermissions}
            <div class="mb-3">
                <div class="alert alert-info py-2 px-3 mb-2">
                    <small class="text-muted">{tr}Permissions applied from:{/tr}</small>
                    <div class="fw-bold">{$pagePermissions.from} level {$pagePermissions.edit_link}</div>
                </div>
                {if $pagePermissions.additional}
                    <div class="alert alert-warning py-2 px-3 mb-2">
                        <small>{$pagePermissions.additional}</small>
                    </div>
                {/if}
            </div>

            {foreach from=$pagePermissions.perms key=group item=perms name=permLoop}
                {assign var="groupId" value="perm-group-"|cat:$smarty.foreach.permLoop.index}
                {* Assign group-specific icons *}
                {assign var="groupIcon" value="group"}
                {if $group|lower|strpos:"wiki" !== false}
                    {assign var="groupIcon" value="edit"}
                {elseif $group|lower|strpos:"comment" !== false}
                    {assign var="groupIcon" value="comment"}
                {elseif $group|lower|strpos:"file" !== false}
                    {assign var="groupIcon" value="file"}
                {elseif $group|lower|strpos:"tiki" !== false}
                    {assign var="groupIcon" value="cog"}
                {elseif $group|lower|strpos:"blog" !== false}
                    {assign var="groupIcon" value="blog"}
                {elseif $group|lower|strpos:"forum" !== false}
                    {assign var="groupIcon" value="comments"}
                {elseif $group|lower|strpos:"tracker" !== false}
                    {assign var="groupIcon" value="database"}
                {elseif $group|lower|strpos:"calendar" !== false}
                    {assign var="groupIcon" value="calendar"}
                {elseif $group|lower|strpos:"gallery" !== false}
                    {assign var="groupIcon" value="image"}
                {elseif $group|lower|strpos:"article" !== false}
                    {assign var="groupIcon" value="newspaper"}
                {elseif $group|lower|strpos:"sheet" !== false}
                    {assign var="groupIcon" value="table"}
                {elseif $group|lower|strpos:"poll" !== false}
                    {assign var="groupIcon" value="poll"}
                {elseif $group|lower|strpos:"category" !== false}
                    {assign var="groupIcon" value="tag"}
                {elseif $group|lower|strpos:"admin" !== false}
                    {assign var="groupIcon" value="admin"}
                {elseif $group|lower|strpos:"user" !== false}
                    {assign var="groupIcon" value="user"}
                {/if}
                <div class="permission-group mb-2">
                    <div class="permission-group-header d-flex align-items-center justify-content-between mb-1 p-2 rounded border bg-light"
                         data-bs-toggle="collapse"
                         data-bs-target="#{$groupId}"
                         aria-expanded="true"
                         aria-controls="{$groupId}"
                         role="button"
                         style="cursor: pointer;">
                        <div class="d-flex align-items-center flex-grow-1 text-truncate">
                            <h6 class="mb-0 text-primary fw-bold d-flex align-items-center text-truncate">
                                {icon name=$groupIcon class="me-2 flex-shrink-0"}
                                <span class="text-truncate me-2">{$group}</span>
                                <span class="badge bg-primary text-white flex-shrink-0 permission-count">{$perms|count}</span>
                                {if $userGroupsText neq ""}
                                    <small class="text-muted ms-2 text-truncate" style="min-width: 0; max-width: 120px;">({$userGroupsText})</small>
                                {/if}
                            </h6>
                        </div>
                        <div class="toggle-icon">
                            {icon name="chevron-down" class="chevron-toggle"}
                        </div>
                    </div>
                    <div id="{$groupId}" class="collapse show">
                        <div class="permission-items p-1">
                            <ul class="list-unstyled mb-0">
                                {foreach from=$perms key=perm item=groups}
                                    <li class="permission-item mb-1">
                                        <span class="text-wrap d-flex align-items-center">
                                            <span class="me-2 text-muted flex-shrink-0 tikihelp"  data-tiki="tooltip" title="|{$groups|join:', '}">{icon name="info-circle"}</span>
                                            {if isset($pagePermissions.permDescriptions.$group.$perm)}
                                                <span class="permission-text">{$pagePermissions.permDescriptions.$group.$perm}</span>
                                            {else}
                                                <span class="permission-text">{$perm|replace:"_":" "|ucwords}</span>
                                            {/if}
                                        </span>
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                    </div>
                </div>
            {/foreach}
        {else}
            <div class="alert alert-secondary text-center py-3">
                <div class="text-muted">
                    {icon name="info-circle" class="me-2"}
                    {tr}No permissions to display{/tr}
                </div>
            </div>
        {/if}
    {/tikimodule}

    {* Add JavaScript for enhanced toggle functionality *}
    {jq}
        // Enhanced collapse toggle functionality for permissions
        $(document).ready(function() {
            $('.permission-group-header').each(function() {
                var $header = $(this);
                var targetId = $header.attr('data-bs-target');
                var $collapseElement = $(targetId);

                // Initialize Bootstrap collapse
                if ($collapseElement.length) {
                    $collapseElement.on('show.bs.collapse', function() {
                        $header.attr('aria-expanded', 'true');
                        $header.find('.chevron-toggle').removeClass('rotated');
                    });

                    $collapseElement.on('hide.bs.collapse', function() {
                        $header.attr('aria-expanded', 'false');
                        $header.find('.chevron-toggle').addClass('rotated');
                    });
                }
            });
        });
    {/jq}
{/if}
