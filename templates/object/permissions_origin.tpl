{if $permissions.from}
    <p>
        {if $permissions.from eq 'global'}
            {tr _0=$permissions.type_label}Global permissions applied to this object (%0){/tr}
        {elseif $permissions.from eq 'category'}
            {tr _0=$permissions.type_label}Category permissions (overriding the global permissions) applied to this object (%0){/tr}
        {elseif $permissions.from eq 'parent category'}
            {tr _0=$permissions.type_label}Category permissions of the parent (overriding the global permissions) applied to this object (%0){/tr}
        {elseif $permissions.from eq 'object'}
            {tr _0=$permissions.type_label}Object permissions (overriding the global and category permissions) applied to this object (%0){/tr}
        {elseif $permissions.from eq 'parent object'}
            {tr _0=$permissions.type_label}Object permissions of the parent (overriding the global and category permissions) applied to this object (%0){/tr}
        {/if}
        {$permissions.edit_link}
    </p>
{/if}
