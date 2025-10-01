{strip}
<span class="lock_block">
    <a class="lock_button" id="lock_{$data.instance}" data-type="{$data.type}" data-object="{$data.object}" data-is_locked="{$data.is_locked}"
            {if !empty($data.is_locked)} title="{tr _0=$data.lockedby|username}Locked by %0{/tr}"{/if} href="#" role="button">
        {if !empty($data.is_locked)}
            {icon name='lock'}
        {else}
            {icon name='unlock'}
        {/if}
    </a>
    {if not $data.object}
        <input type='hidden' name='locked' value=''>
    {/if}
</span>

{if !empty($data.can_change)}
    {jq}
        $("#lock_{{$data.instance}}").on("click", function () { objectLockToggle(this); });
    {/jq}
{else}
    {jq}
        $("#lock_{{$data.instance}}").css("cursor", "default");
    {/jq}
{/if}
{/strip}
