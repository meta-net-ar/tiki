<input type="hidden" name="{$field.ins_id}">
{foreach from=$field.groups item=val name=ix}
    <div>
        {$val|escape}
    </div>
{/foreach}
