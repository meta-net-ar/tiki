{if isset($pagePermissions)}
    {* Hidden modal button that appears in modules or menus *}
    <button type="button" class="btn btn-outline-secondary btn-sm opacity-75 toggle-permissions-modal" data-bs-toggle="modal" data-bs-target="#permissionsModal">
        {icon name="lock"} {tr}View Permissions{/tr}
    </button>

    {* Permissions Modal - Hidden by default *}
    <div class="modal fade" id="permissionsModal" tabindex="-1" role="dialog" aria-labelledby="permissionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permissionsModalLabel">
                        {icon name="lock" class="me-2"} {tr}Permissions{/tr}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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

                            <div class="permission-group mb-3">
                                <h6 class="mb-2 text-primary fw-bold d-flex align-items-center">
                                    {icon name=$groupIcon class="me-2"}
                                    <span>{$group}</span>
                                </h6>
                                <div class="permission-items ps-4">
                                    <ul class="list-unstyled mb-0">
                                        {foreach from=$perms key=perm item=groups}
                                            {* Count users and groups *}
                                            {assign var="userCount" value=0}
                                            {assign var="groupCount" value=0}
                                            {foreach from=$groups item=entity}
                                                {if $entity|strpos:"group:" === 0}
                                                    {assign var="groupCount" value=$groupCount+1}
                                                {else}
                                                    {assign var="userCount" value=$userCount+1}
                                                {/if}
                                            {/foreach}

                                            <li class="permission-item mb-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="permission-text flex-grow-1">
                                                        {if isset($pagePermissions.permDescriptions.$group.$perm)}
                                                            {$pagePermissions.permDescriptions.$group.$perm}
                                                        {else}
                                                            {$perm|replace:"_":" "|ucwords}
                                                        {/if}
                                                    </div>
                                                    <div class="permission-counts text-nowrap ms-2">
                                                        {if $userCount > 0}
                                                            <span class="badge bg-info text-dark me-1" title="{tr}Users{/tr}">
                                                                {icon name="person"} {$userCount}
                                                            </span>
                                                        {/if}
                                                        {if $groupCount > 0}
                                                            <span class="badge bg-secondary text-white" title="{tr}Groups{/tr}">
                                                                {icon name="people"} {$groupCount}
                                                            </span>
                                                        {/if}
                                                    </div>
                                                </div>
                                                <small class="text-muted d-block mt-1" title="{tr}Assigned to:{/tr}">
                                                    {$groups|join:", "}
                                                </small>
                                            </li>
                                        {/foreach}
                                    </ul>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{tr}Close{/tr}</button>
                </div>
            </div>
        </div>
    </div>
{/if}
