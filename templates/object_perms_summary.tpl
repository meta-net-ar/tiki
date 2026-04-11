<div class="card border {if $permsType eq 'object'}border-warning{else}border-light{/if}">
    <div class="card-body py-3">
        {capture name=permType}{$objectType}s{/capture}
        <div class="d-flex align-items-center">
            <div class="flex-grow-1">
                {if $permsType eq 'category'}
                    <div class="d-flex align-items-center text-info">
                        {icon name="permission" class="me-2"}
                        <span>{tr}No individual permissions, category permissions apply{/tr}</span>
                    </div>
                {elseif $permsType eq 'object'}
                    <div class="d-flex align-items-center text-warning">
                        {icon name="permission" class="me-2"}
                        <span>{tr}There are individual permissions set for this object{/tr}</span>
                    </div>
                {else}
                    <div class="d-flex align-items-center text-muted">
                        {icon name="permission" class="me-2"}
                        <span>{tr}No individual permissions. Global permissions apply.{/tr}</span>
                    </div>
                {/if}
            </div>
            <div class="flex-shrink-0">
                <a title="{tr}Manage Permissions{/tr}"
                   class="btn {if $permsType eq 'object'}btn-warning{else}btn-outline-secondary{/if} btn-sm"
                   href="tiki-objectpermissions.php?objectName={$name|escape:"url"}&amp;objectType={$objectType}&amp;permType={$smarty.capture.permType}&amp;objectId={$objectId}">
                    {icon name="edit" class="me-1"}
                    {tr}Manage{/tr}
                </a>
            </div>
        </div>
    </div>
</div>
