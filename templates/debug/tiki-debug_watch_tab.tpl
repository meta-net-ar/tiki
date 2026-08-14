<table id="watchlist">
    <caption> {tr}Watchlist{/tr} </caption>
    <tr>
        <th>{tr}Variable{/tr}</th>
        <th>{tr}Value{/tr}</th>
    </tr>

    {section name=i loop=$watchlist}
        <tr>
            <td class="{cycle advance=false}"{if $smarty.section.i.index == 0} id="firstrow"{/if}>
                <code>{$watchlist[i].var}</code>
            </td>
            <td{if $smarty.section.i.index == 0} id="firstrow"{/if}>
                <pre>{$watchlist[i].value|escape:"html"|wordwrap:60:"\n":true|replace:"\n":"<br>"}</pre>
            </td>
        </tr>
    {/section}
</table>
