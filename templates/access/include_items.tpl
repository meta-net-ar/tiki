<div class="mb-3 row mx-0">
    <h5 class="w-100">{$customMsg|escape}</h5>
    {if isset($extra["warning"])}
        <div class="alert alert-warning" role="alert">
            {$extra["warning"]|escape}
        </div>
    {/if}
    {include file="access/render_list.tpl" list=$items}
</div>
