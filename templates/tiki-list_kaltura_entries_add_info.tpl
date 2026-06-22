{capture name=add_info}{strip}
    <table class="table-condensed">
        {if !empty($item->description)}
            <tr>
                <th class="text-end">{tr}Description{/tr}</th>
                <td>{$item->description}</td>
            </tr>
        {/if}
        <tr>
            <th class="text-end">{tr}Status{/tr}</th>
            <td>{$statusAsString[$item->status]}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Media Id{/tr}</th>
            <td><pre style="margin:0">{$item->id}</pre></td>
        </tr>
        <tr>
            <th class="text-end">{tr}Media Type{/tr}</th>
            <td>{$item->mediaType}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Duration{/tr}</th>
            <td>{$item->duration}s</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Views{/tr}</th>
            <td>{$item->views}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Plays{/tr}</th>
            <td>{$item->plays}</td>
        </tr>
        <tr>
            <th class="text-end">{tr}Wiki plugin code{/tr}</th>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2"><pre style="margin:0;font-size:1.1em;">{ldelim}kaltura id="{$item->id}"{rdelim}</pre></td>
        </tr>
    </table>
{/strip}{/capture}
