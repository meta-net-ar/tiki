<b>{tr}List of returned objects{/tr}</b>
<ol>
    {foreach from=$results item=result}
        <li>
            {* 1. Use explicit URL from PluginList if available *}
            {if ! empty($result.url)}
                <a href="{$result.url|escape}">
                    {$result.title|escape}
                </a>

            {* 2. Generate URL based on object type + object id *}
            {elseif ! empty($result.object_type) && ! empty($result.object_id)}
                <a href="{sefurl type=$result.object_type objectId=$result.object_id}">
                    {if ! empty($result.title)}
                        {$result.title|escape}
                    {else}
                        {tr}Item{/tr} #{$result.object_id}
                    {/if}
                </a>

            {* 3. Fallback when no URL can be generated *}
            {else}
                {if ! empty($result.title)}
                    {$result.title|escape}
                {elseif ! empty($result.object_id)}
                    {tr}Item{/tr} #{$result.object_id}
                {else}
                    {tr}Unknown Item{/tr}
                {/if}
            {/if}
        </li>
    {/foreach}
</ol>

<b>{tr}Various variables{/tr}</b>
<p>
    {tr}See{/tr}
    <a href="https://doc.tiki.org/PluginList-advanced-output-control-block#Accessible_variables" target="_blank" rel="noopener">
        https://doc.tiki.org/PluginList-advanced-output-control-block#Accessible_variables
    </a>
</p>
</br>
<table class="table table-striped table-sm">
    <thead>
        <tr>
            <th>{tr}Variable{/tr}</th>
            <th>{tr}Value{/tr}</th>
            <th>{tr}Meaning{/tr}</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>$results</code></td>
            <td><em>{tr}see below{/tr}</em></td>
            <td>{tr}Contains the result set. Each result contains all values provided by the search query along with those requested manually.{/tr}</td>
        </tr>
        <tr>
            <td><code>$count</code></td>
            <td>{$count}</td>
            <td>{tr}The total result count{/tr}</td>
        </tr>
        <tr>
            <td><code>$maxRecords</code></td>
            <td>{$maxRecords}</td>
            <td>{tr}The number of results per page{/tr}</td>
        </tr>
        <tr>
            <td><code>$offset</code></td>
            <td>{$offset}</td>
            <td>{tr}The result offset{/tr}</td>
        </tr>
        <tr>
            <td><code>$offsetplusone</code></td>
            <td>{$offsetplusone}</td>
            <td>{tr}Offset plus one, useful for messages like “Showing results 1 to …”{/tr}</td>
        </tr>
        <tr>
            <td><code>$offsetplusmaxRecords</code></td>
            <td>{$offsetplusmaxRecords}</td>
            <td>{tr}Maximum record count plus offset, e.g. “Showing results 1 to 25”{/tr}</td>
        </tr>
        <tr>
            <td><code>$results-&gt;getEstimate()</code></td>
            <td>{$results->getEstimate()}</td>
            <td>{tr}Estimated total number of results, which may exceed the displayed count due to Lucene limits{/tr}</td>
        </tr>
    </tbody>
</table>

<b>{tr _0='<code>$results</code>'}Loop on contents of %0{/tr}</b>
<br><code>{literal}{foreach from=$results item=result}&lt;pre&gt;{$result|@debug_print_var}&lt;/pre&gt;&lt;hr&gt;{/foreach}{/literal}</code>
{foreach from=$results item=result}
<pre>
{$result|@debug_print_tree}
</pre>
{/foreach}
