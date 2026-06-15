{*
Built-in PluginList output template for rendering an aggregation bucket
list as a Chart.js chart. Pairs with the aggregation DSL ({group}, {metric},
{join}) introduced in Tiki's unified reporting engine.

Example usage:

  {LIST()}
    {filter type="trackeritem"}
    {filter content="*" field="tracker_id" exact="42"}
    {group field="vendor_id" name="vendor" size="10" order="total_amount desc"}
    {metric name="total_amount" op="sum" field="amount"}
    {metric name="deal_count"   op="count"}
    {join name="vendor" tracker="17" on="vendor" select="title"}
    {OUTPUT(template="chartjs")}
      {chart
        type="bar"
        agg="vendor"
        value="total_amount,deal_count"
        label="vendor.title"
        title="Revenue per vendor"
        colors="#1f77b4,#ff7f0e,#2ca02c,#d62728,#9467bd"
        width="800"
        height="450"
      }
    {OUTPUT}
  {LIST}

Supported {chart ...} arguments:

  type        Chart.js chart type (bar, line, pie, doughnut, ...). Default: bar.
  agg         REQUIRED. Name of the bucket aggregation to plot.
  value       Comma-separated list of metric names to use as datasets. Each
              becomes one Chart.js dataset. Defaults to the bucket "doc_count".
  label       Bucket field used for x-axis / pie labels. Defaults to the
              bucket "key". Use the namespaced metric set by a {join} (e.g.
              "vendor.title") to substitute the joined column.
  title       Chart title; also used as the dataset label when only one
              dataset is rendered.
  colors      Comma- or colon-separated list of CSS colors used as
              backgroundColor (cycled across buckets for pie/doughnut, across
              datasets for bar/line).
  width       Pixel width.
  height      Pixel height.
  id          Optional DOM id; auto-generated when omitted.
  options     JSON string merged into the Chart.js options object.

Multiple {chart} blocks are supported and render side by side.
*}
{if not empty($chart)}
    {if isset($chart.type) or isset($chart.agg)}
        {* normalize single {chart} block to array form *}
        {$charts = [$chart]}
    {else}
        {$charts = $chart}
    {/if}

    <div class="tiki-chartjs-aggregations">
    {$cIndex = 0}
    {foreach $charts as $c}
        {$type   = isset($c.type)   ? $c.type   : 'bar'}
        {$aggKey = isset($c.agg)    ? $c.agg    : ''}
        {$labelF = isset($c.label)  ? $c.label  : ''}
        {$valueS = isset($c.value)  ? $c.value  : ''}
        {$titleS = isset($c.title)  ? $c.title  : ''}
        {$idAttr = isset($c.id)     ? $c.id     : 'tiki_chartjs_'|cat:$id|cat:'_'|cat:$cIndex}
        {$wAttr  = isset($c.width)  ? $c.width  : ''}
        {$hAttr  = isset($c.height) ? $c.height : ''}
        {$cssClass = isset($c.class) ? $c.class : ''}

        {if isset($c.colors)}
            {if strpos($c.colors, ':') !== false}
                {$colors = $c.colors|split:':'}
            {else}
                {$colors = $c.colors|split:','}
            {/if}
        {else}
            {$colors = ['#1f77b4','#ff7f0e','#2ca02c','#d62728','#9467bd','#8c564b','#e377c2','#7f7f7f','#bcbd22','#17becf']}
        {/if}

        {if $valueS}
            {$valueList = $valueS|split:','}
        {else}
            {$valueList = ['__doc_count__']}
        {/if}

        {if $aggKey and isset($aggregations[$aggKey]) and $aggregations[$aggKey].is_bucket}
            {$agg = $aggregations[$aggKey]}
            {$labels = []}
            {$buckets = $agg.buckets}

            {foreach $buckets as $b}
                {if $labelF and isset($b.metrics[$labelF]) and $b.metrics[$labelF] !== null and $b.metrics[$labelF] !== ''}
                    {$labels[] = $b.metrics[$labelF]}
                {else}
                    {$labels[] = $b.key}
                {/if}
            {/foreach}

            {$datasets = []}
            {$dsIndex = 0}
            {foreach $valueList as $rawValueName}
                {$valueName = $rawValueName|trim}
                {$data = []}
                {foreach $buckets as $b}
                    {if $valueName == '__doc_count__'}
                        {$data[] = $b.doc_count}
                    {elseif isset($b.metrics[$valueName])}
                        {$mv = $b.metrics[$valueName]}
                        {if is_array($mv) and isset($mv.value)}
                            {$data[] = $mv.value}
                        {else}
                            {$data[] = $mv}
                        {/if}
                    {else}
                        {$data[] = 0}
                    {/if}
                {/foreach}

                {if $type == 'pie' or $type == 'doughnut' or $type == 'polarArea'}
                    {* one dataset, color-per-bucket - prefer the palette
                       the post-processor stamped onto each bucket via
                       {group palette=}, falling back to colors= *}
                    {$bgColors = []}
                    {$i = 0}
                    {foreach $buckets as $b}
                        {if isset($b.metrics.__bucket_color__) and $b.metrics.__bucket_color__}
                            {$bgColors[] = $b.metrics.__bucket_color__}
                        {else}
                            {$bgColors[] = $colors[$i % count($colors)]}
                        {/if}
                        {$i = $i + 1}
                    {/foreach}
                    {$ds = ['data' => $data, 'backgroundColor' => $bgColors]}
                {else}
                    {if count($valueList) > 1}
                        {* Multi-dataset (stacked): one color per dataset/series,
                           consistent across all buckets. Bucket palette is for
                           distinguishing buckets, not series. *}
                        {$ds = ['data' => $data, 'backgroundColor' => $colors[$dsIndex % count($colors)]]}
                    {else}
                        {* Single dataset: use per-bucket palette colors when set *}
                        {$bgColors = []}
                        {$hasBucketColors = false}
                        {$i = 0}
                        {foreach $buckets as $b}
                            {if isset($b.metrics.__bucket_color__) and $b.metrics.__bucket_color__}
                                {$bgColors[] = $b.metrics.__bucket_color__}
                                {$hasBucketColors = true}
                            {else}
                                {$bgColors[] = $colors[$dsIndex % count($colors)]}
                            {/if}
                            {$i = $i + 1}
                        {/foreach}
                        {if $hasBucketColors}
                            {$ds = ['data' => $data, 'backgroundColor' => $bgColors]}
                        {else}
                            {$ds = ['data' => $data, 'backgroundColor' => $colors[$dsIndex % count($colors)]]}
                        {/if}
                    {/if}
                {/if}

                {if $valueName == '__doc_count__'}
                    {$ds.label = 'count'}
                {elseif isset($agg.metric_labels[$valueName])}
                    {$ds.label = $agg.metric_labels[$valueName]}
                {else}
                    {$ds.label = $valueName}
                {/if}

                {if count($valueList) == 1 and $titleS}
                    {$ds.label = $titleS}
                {/if}

                {$datasets[] = $ds}
                {$dsIndex = $dsIndex + 1}
            {/foreach}

            {$payload = [
                'data' => [
                    'labels'   => $labels,
                    'datasets' => $datasets,
                ],
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                    'plugins' => [
                        'title' => ['display' => ($titleS ? true : false), 'text' => $titleS],
                        'legend' => ['display' => true],
                    ],
                ],
            ]}

            {if isset($c.options) and $c.options}
                {$extra = json_decode($c.options, true)}
                {if is_array($extra)}
                    {foreach $extra as $ek => $ev}
                        {if is_array($ev) and isset($payload.options[$ek]) and is_array($payload.options[$ek])}
                            {foreach $ev as $ek2 => $ev2}
                                {if is_array($ev2) and isset($payload.options[$ek][$ek2]) and is_array($payload.options[$ek][$ek2])}
                                    {foreach $ev2 as $ek3 => $ev3}
                                        {$payload.options[$ek][$ek2][$ek3] = $ev3}
                                    {/foreach}
                                {else}
                                    {$payload.options[$ek][$ek2] = $ev2}
                                {/if}
                            {/foreach}
                        {else}
                            {$payload.options[$ek] = $ev}
                        {/if}
                    {/foreach}
                {/if}
            {/if}

            <div class="tiki-chartjs-aggregations-item {$cssClass|escape}" {if $wAttr or $hAttr}style="{if $wAttr}width:{$wAttr|escape}px;{/if}{if $hAttr}height:{$hAttr|escape}px;{/if}"{/if}>
                {wikiplugin _name='chartjs' type=$type id=$idAttr width=$wAttr height=$hAttr}
                    {$payload|json_encode}
                {/wikiplugin}
            </div>
        {else}
            <div class="alert alert-warning">{tr 0=$aggKey|escape}Unknown or empty aggregation "%0".{/tr}</div>
        {/if}

        {$cIndex = $cIndex + 1}
    {/foreach}
    </div>
{else}
    <div class="alert alert-warning">{tr}No {ldelim}chart ...{rdelim} block defined inside the {ldelim}OUTPUT{rdelim} body.{/tr}</div>
{/if}
