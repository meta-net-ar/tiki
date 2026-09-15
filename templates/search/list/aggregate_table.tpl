{*
    Built-in template for PluginList aggregation reports.

    Renders the first server-side aggregation in $aggregations as a flat
    HTML table. For nested groups, each group's name becomes a column and
    rows are produced for the deepest-level buckets ("cross-join" style).
    Metrics from each level appear as their own columns.

    Use as:
        {OUTPUT(template="aggregate_table")}
            {tableparams title="My Report" hidecount="y" id="myTable" class="extra-class"}
        {OUTPUT}

    Optional table-level configuration via a {tableparams} tag inside
    the {OUTPUT} body (same syntax as templates/search/list/table.tpl):
        - title:     heading shown above the table
        - class:     extra CSS class on the <table>
        - id:        HTML id attribute on the <table>
        - hidecount: "y" to hide the # (doc_count) column

    Virtual columns the post-processor injects into bucket.metrics
    are honoured automatically:
        - __bucket_kind__   bucket-normal | bucket-others | bucket-total
                            -> applied as a CSS class on the <tr>; site CSS
                            can target tr.bucket-others / tr.bucket-total
                            for distinct styling (italic, bold, top border).
        - __bucket_color__  CSS color string -> rendered as a small swatch
                            next to the first group cell.
    Both are stripped from the metric column list so they never show as
    data columns.

    For richer customization (per-column formatters, in-cell bars, conditional
    classes, totals, charts) use a custom Smarty template instead and read
    $aggregations directly.
*}
{$aggHiddenMetrics = ['__bucket_kind__', '__bucket_color__']}
{$aggName = ''}
{foreach $aggregations as $name => $agg}{if $aggName === ''}{$aggName = $name}{$rootAgg = $agg}{/if}{/foreach}
{if empty($aggName) || empty($rootAgg) || ! $rootAgg.is_bucket}
    <div class="alert alert-info">{tr}No aggregation results to display.{/tr}</div>
{else}
    {*
        Build the flat row list and the column descriptor by walking the
        bucket tree once. Each entry of $aggColumns is [name, kind] where
        kind is 'group' or 'metric'.
    *}
    {$aggRows = []}
    {$aggColumns = []}
    {$walkStack = [['agg' => $rootAgg, 'path' => []]]}
    {* Smarty doesn't support recursion easily; do an iterative walk *}
    {function name=walkBuckets agg=null path=[] columnsRef=[] rowsRef=[]}{/function}

    {* iterate top-level buckets first, capturing all metric column names from any first bucket *}
    {if not empty($rootAgg.buckets)}
        {$first = $rootAgg.buckets[0]}
        {$aggColumns[] = ['name' => $aggName, 'kind' => 'group', 'label' => isset($rootAgg.label) ? $rootAgg.label : $aggName]}
        {foreach $first.metrics as $mName => $mValue}
            {if not in_array($mName, $aggHiddenMetrics)}
                {$aggColumns[] = ['name' => $mName, 'kind' => 'metric', 'label' => isset($rootAgg.metric_labels[$mName]) ? $rootAgg.metric_labels[$mName] : $mName]}
            {/if}
        {/foreach}
        {if empty($tableparams.hidecount) || $tableparams.hidecount neq 'y'}
            {$aggColumns[] = ['name' => 'doc_count', 'kind' => 'metric', 'label' => '#']}
        {/if}

        {* check if first bucket has nested children to expand *}
        {$nestedNames = []}
        {foreach $first.children as $cName => $cAgg}
            {$nestedNames[] = $cName}
            {if not empty($cAgg.buckets)}
                {$firstChild = $cAgg.buckets[0]}
                {$aggColumns[] = ['name' => $cName, 'kind' => 'group', 'label' => $cName]}
                {foreach $firstChild.metrics as $mName => $mValue}
                    {if not in_array($mName, $aggHiddenMetrics)}
                        {$aggColumns[] = ['name' => $cName|cat:'.'|cat:$mName, 'kind' => 'metric', 'label' => $cName|cat:'.'|cat:$mName]}
                    {/if}
                {/foreach}
            {/if}
        {/foreach}

        {* now produce the rows: cross-join with first level of nested children if present *}
        {foreach $rootAgg.buckets as $bucket}
            {if empty($nestedNames)}
                {$row = [
                    '_keys' => [$aggName => $bucket.key],
                    '_kind' => isset($bucket.metrics.__bucket_kind__) ? $bucket.metrics.__bucket_kind__ : 'bucket-normal',
                    '_color' => isset($bucket.metrics.__bucket_color__) ? $bucket.metrics.__bucket_color__ : '',
                ]}
                {foreach $bucket.metrics as $mName => $mValue}
                    {if not in_array($mName, $aggHiddenMetrics)}
                        {$row[$mName] = $mValue}
                    {/if}
                {/foreach}
                {$row['doc_count'] = $bucket.doc_count}
                {$aggRows[] = $row}
            {else}
                {foreach $bucket.children as $cName => $cAgg}
                    {if not empty($cAgg.buckets)}
                        {foreach $cAgg.buckets as $childBucket}
                            {$row = [
                                '_keys' => [$aggName => $bucket.key, $cName => $childBucket.key],
                                '_kind' => isset($childBucket.metrics.__bucket_kind__) ? $childBucket.metrics.__bucket_kind__ : 'bucket-normal',
                                '_color' => isset($childBucket.metrics.__bucket_color__) ? $childBucket.metrics.__bucket_color__ : '',
                            ]}
                            {foreach $bucket.metrics as $mName => $mValue}
                                {if not in_array($mName, $aggHiddenMetrics)}
                                    {$row[$mName] = $mValue}
                                {/if}
                            {/foreach}
                            {$row['doc_count'] = $bucket.doc_count}
                            {foreach $childBucket.metrics as $mName => $mValue}
                                {if not in_array($mName, $aggHiddenMetrics)}
                                    {$row[$cName|cat:'.'|cat:$mName] = $mValue}
                                {/if}
                            {/foreach}
                            {$aggRows[] = $row}
                        {/foreach}
                    {/if}
                {/foreach}
            {/if}
        {/foreach}
    {/if}

    {if isset($tableparams.title)}
        <div class="list-table-heading">{wiki}{$tableparams.title|escape}{/wiki}</div>
    {/if}
    <div class="table-responsive">
        <table {if not empty($tableparams.id)}id="{$tableparams.id|escape}" {/if}class="table normal table-hover table-striped{if not empty($tableparams.class)} {$tableparams.class|escape}{/if}">
            <thead>
                <tr>
                    {foreach $aggColumns as $col}
                        <th class="{if $col.kind eq 'metric'}text-end{/if}">{$col.label|escape}</th>
                    {/foreach}
                </tr>
            </thead>
            <tbody>
                {foreach $aggRows as $row}
                    <tr class="{$row._kind|escape}">
                        {$firstGroupRendered = false}
                        {foreach $aggColumns as $col}
                            {if $col.kind eq 'group'}
                                <td>
                                    {if not $firstGroupRendered and not empty($row._color)}
                                        <span class="bucket-color-swatch" style="display:inline-block;width:0.75em;height:0.75em;background:{$row._color|escape};margin-right:0.4em;vertical-align:middle;border-radius:2px;"></span>
                                        {$firstGroupRendered = true}
                                    {/if}
                                    {if isset($row._keys[$col.name])}{$row._keys[$col.name]|escape}{/if}
                                </td>
                            {else}
                                <td class="text-end">{if isset($row[$col.name])}{if is_numeric($row[$col.name])}{$row[$col.name]|number_format:0:".":","|escape}{else}{$row[$col.name]|escape}{/if}{/if}</td>
                            {/if}
                        {/foreach}
                    </tr>
                {/foreach}
                {if empty($aggRows)}
                    <tr><td colspan="{count($aggColumns)}" class="text-muted text-center">{tr}No data{/tr}</td></tr>
                {/if}
            </tbody>
        </table>
    </div>
{/if}
