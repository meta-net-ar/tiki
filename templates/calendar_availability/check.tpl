{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<div class="table-responsive">
    <table class="table">
    <thead>
        <tr>
            <td>{tr}Participant{/tr}</td>
            {foreach $periods as $time}
                <td>{$time|tiki_short_datetime}</td>
            {/foreach}
        </tr>
    </thead>
    {foreach $availability as $user => $list}
    <tr>
        <td>{$user|escape}</td>
        {foreach $list as $time => $busy}
            <td {if $busy}style="background-color: #ccc"{/if}>
                {if $busy}
                    {tr}{$busy}{/tr}
                {/if}
            </td>
        {/foreach}
    </tr>
    {/foreach}
    </table>
</div>
{/block}
