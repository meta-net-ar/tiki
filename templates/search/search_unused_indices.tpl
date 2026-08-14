<div id="tiki_unused_indexes_banner">
    {if ! empty($unusedIndices)}
        {remarksbox close="n" type="note" title="{tr}Unused Indexes Found{/tr}"}
            {if ! empty($unusedIndices['indices'])}
                <p>{tr}You have the following unused indexes:{/tr}</p>
                <ul>
                    {foreach from=$unusedIndices['indices'] item=index}
                        <li>{$index}</li>
                    {/foreach}
                </ul>
                <p>{tr}If you don't need them (for debugging), run the following command:{/tr}</p>
                <ul>
                    <li><code>php console.php index:cleanup</code> {tr}(Delete unused indexes){/tr}</li>
                    <li><code>php console.php index:cleanup --dry-run</code> {tr}(List unused indexes without deleting){/tr}</li>
                    <li><code>php console.php index:cleanup --all</code> {tr}(Delete all indexes, ignoring prefix){/tr}</li>
                    <li><code>php console.php index:cleanup --all --dry-run</code> {tr}(List all indexes without deleting){/tr}</li>
                    <li><code>php console.php index:cleanup -i "index_name"</code> {tr}(Remove a specific index){/tr}</li>
                </ul>
            {else if ! empty($unusedIndices['error'])}
                <p>{tr}Error:{/tr}</p>
                <p>{$unusedIndices['error']}</p>
            {/if}
        {/remarksbox}
    {/if}
</div>
