{if isset($list) && $list|count > 0}
    {if $list|count < 16}
        <ul class="list-items">
            {foreach $list as $name}
                <li class="mx-4">{$name}</li>
            {/foreach}
        </ul>
    {else}
        {foreach $list as $name}
            {$name}{if !$name@last}, {/if}
        {/foreach}
    {/if}
{/if}
