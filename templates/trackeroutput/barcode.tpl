{if $data.image_output eq ''}
    <div><span class="alert alert-warning d-block" style="width: fit-content;">{tr}Empty barcode{/tr}</span></div>
{elseif $data.image_output neq '___ERROR___'}
    <div>{$data.image_output}</div>
    <div>{$data.value}</div>
{elseif $data.image_output eq '___ERROR___'}
    <div><span class="alert alert-danger d-block" style="width: fit-content;">{tr}Error generating barcode{/tr}</span></div>
{/if}
