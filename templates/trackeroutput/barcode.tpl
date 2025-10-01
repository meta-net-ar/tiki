{if $data.image_output eq '' }
    <div><span class="alert alert-warning d-block" style="width: fit-content;">Empty barcode</span></div>
{else if $data.image_output neq '___ERROR___' }
    <div>{$data.image_output}</div>
    <div>{$data.value}</div>
{else if $data.image_output eq '___ERROR___'}
    <div><span class="alert alert-danger d-block" style="width: fit-content;">Error generating barcode</span></div>
{/if}
