{activityframe activity=$activity heading="{tr _0=$activity.user|userlink}User %0 modified a page{/tr}"}
    <p>
        <span class="lead">{object_link type=$activity.type id=$activity.object}</span><br>
        {if !empty($activity.edit_comment)}<span class="description text-sm">{tr}Edit comment:{/tr} {$activity.edit_comment|escape}</span>{/if}
    </p>
    <small>{tr}View changes:{/tr} <a href="tiki-pagehistory.php?page={$activity.object}&oldver={$activity.old_version|escape}&newver={$activity.version|escape}">{tr}history{/tr}</a></small>
    {if is_array($activity.aggregate)}
    <small>{$activity.aggregate.user|userlink}</small>
    {/if}
{/activityframe}
