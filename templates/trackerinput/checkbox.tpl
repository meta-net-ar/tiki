<div class="form-check form-check-inline">
    {if $field.value eq 'y' or $field.value eq 'on' or strtolower($field.value) eq 'yes' or $field.defaultvalue eq 'y'}
        <input type="hidden" name="{$field.ins_id}_old" value="1">
    {/if}
    {* The actual checkbox needs to be last in the page for Tracker Field Rules to find it *}
    <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="{$field.ins_id}"{if $field.value eq 'y' or $field.value eq 'on' or strtolower($field.value) eq 'yes' or $field.defaultvalue eq 'y'} checked="checked"{/if}>
</div>
