<b>List of returned objects</b>
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
                        Item #{$result.object_id}
                    {/if}
                </a>

            {* 3. Fallback when no URL can be generated *}
            {else}
                {if ! empty($result.title)}
                    {$result.title|escape}
                {elseif ! empty($result.object_id)}
                    Item #{$result.object_id}
                {else}
                    Unknown Item
                {/if}
            {/if}
        </li>
    {/foreach}
</ol>

{wiki}
__Various variables__ (see [https://doc.tiki.org/PluginList-advanced-output-control-block#Accessible_variables:])
||
__variable__ | __value__ | __meaning__
$results | ''see below'' | containing the result set. Each results contain all values provided by the search query along with those requested manually.
$count | {$count} | the total result count
$maxRecords | {$maxRecords} | the amount of results per page
$offset | {$offset} | the result offset
$offsetplusone | {$offsetplusone} | basically $offset + 1 , so that you can say "Showing results 1 to ...."
$offsetplusmaxRecords | {$offsetplusmaxRecords} | basically $maxRecords + $offset , so you can say "Showing results 1 to 25"
$results-&gt;getEstimate() | {$results->getEstimate()} | which is the estimate of the total number of results possible, which could exceed $count , which is limited by the max Lucene search results to return set in Admin...Search
||
{/wiki}

<b>Loop on contents</b> of <code>$results</code>:
<br><code>{literal}{foreach from=$results item=result}&lt;pre&gt;{$result|@debug_print_var}&lt;/pre&gt;&lt;hr&gt;{/foreach}{/literal}</code>
{foreach from=$results item=result}
<pre>
{$result|@debug_print_tree}
</pre>
{/foreach}
