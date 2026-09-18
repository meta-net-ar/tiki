{if isset($pagePermissions)}
    {tikimodule error=$module_params.error title=$tpl_module_title name="permissions" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle style=$module_params.style}
        {include file='object/permissions_origin.tpl' permissions=$pagePermissions}
        <a class="btn btn-link" href="{bootstrap_modal controller=object action=permissions type=$pagePermissions.type object=$pagePermissions.object}" role="button">
            {icon name="permission"}
            {tr}See details{/tr}
            <span class="badge bg-secondary">{$pagePermissions.count|escape}</span>
        </a>
    {/tikimodule}
{/if}
