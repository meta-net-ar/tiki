{if ! $data && $editPerm}
<button id="add-signature-{$index}" class="add-signature btn btn-primary" data-index="{$index}" data-editable="{$editPerm}">
    {tr}Add signature{/tr}{if $name} ({$name}){/if}
</button>

<div id="signature_{$index}" data-index="{$index}" class="signature-container" style="{if !$data}display:none{/if}">
    <form>
        <input name="page_{$index}" type="hidden" value="{$pageName}">
        <input name="csrf_{$index}" type="hidden" value="{$ticket}">
    </form>
    <div class="wrapper">
        <canvas class="signature-pad"></canvas>
    </div>
    <div class="buttons row">
        <div class="text-start col-sm">
            <button class="cancel-signature btn btn-link">{tr}Cancel{/tr}</button>
        </div>
        <div class="text-end col-sm">
            <button class="clear btn btn-link">{tr}Clear{/tr}</button>
            <button class="save btn btn-primary">{tr}Save{/tr}</button>
        </div>
    </div>
</div>
{/if}

{if $data}
<div id="signature_{$index}" style="text-align:{$align}">
    <img src="{$data}" style="width:{$width};height:{$height}" alt="">
</div>
{/if}
