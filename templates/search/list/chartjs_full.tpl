{*
    Built-in PluginList output template for the unified reporting engine
    rendering an aggregation result as a fully customisable Chart.js
    chart (other libraries can plug in by registering a JS adapter -
    see lib/jquery_tiki/tiki-aggregations.js).

    The server emits two raw JSON payloads in the DOM and stops there.
    All chart-library-specific knowledge lives on the JS side.

    Authoring contract:

      Use the long-form chunk syntax (uppercase name + parens, like
      {LIST()}...{LIST}) so the body is captured. The body IS the chart
      options object - drop a full Chart.js config in there verbatim,
      with three small DSL additions the JS adapter understands:

        - "labels"           bucket field path used for x-axis / pie
                             labels (defaults to "key"; use a joined-
                             field path like "vendor.title" for
                             resolved labels).
        - "exclude"          bucket filter for the synthetic Stage 5
                             rows: "all" (default), "normal",
                             "no-others" or "no-total".
        - datasets[].metric  bucket metric name to read into that
                             dataset's data array.

      Everything else passes straight through to Chart.js (type,
      scales, plugins, tooltip callbacks, mixed-chart per-dataset
      `type`, dual-axis `yAxisID`, ...).

    Example:

      {LIST()}
        {filter type="trackeritem"}
        {filter content="*" field="tracker_id" exact="42"}

        {group field="vendor_id" name="vendor" size="5"
               others="y"  others_label="Others"
               total="y"   total_label="Grand total"
               palette="Tableau10" others_color="#bbbbbb"}
          {metric name="rev"   op="sum"   field="amount"}
          {metric name="deals" op="count"}
          {metric name="pct"   op="formula" expr="rev / NULLIF(SUM(rev) OVER (), 0) * 100"}
          {metric name="cum"   op="formula" expr="SUM(rev) OVER (ORDER BY __sortkey__ ROWS UNBOUNDED PRECEDING)"}
        {join name="vendor" tracker="17" on="vendor" select="title"}

        {OUTPUT(template="chartjs_full")}
          {CHART(agg="vendor" id="vendors-mix" width="900" height="450")}
          {
            "type": "bar",
            "labels": "vendor.title",
            "exclude": "no-total",
            "datasets": [
              {"metric":"rev","label":"Revenue","type":"bar",
               "yAxisID":"y","backgroundColor":"#1f77b4"},
              {"metric":"deals","label":"Deals","type":"line",
               "yAxisID":"y1","borderColor":"#ff7f0e","borderWidth":2}
            ],
            "options": {
              "scales": {
                "y":  {"beginAtZero": true},
                "y1": {"position": "right",
                       "grid": {"drawOnChartArea": false}}
              },
              "plugins": {"legend": {"position": "bottom"}}
            }
          }
          {CHART}
        {OUTPUT}
      {LIST}

    Supported {CHART(...)} chunk arguments (DOM/wrapper only - all
    Chart.js options live in the body):

      agg       REQUIRED. Aggregation name to render.
      id        DOM id; auto-generated when omitted.
      width     Pixel width on the wrapper.
      height    Pixel height on the wrapper.
      class     Extra CSS class on the wrapper.
      renderer  Adapter name. Default: "chartjs". Set to e.g. "plotly"
                once a plotly adapter is registered.
*}
{$headerlib->add_jsfile('lib/jquery_tiki/tiki-aggregations.js')}
{$headerlib->add_js_module('
  import { Chart, registerables } from "chartjs";
  if (! window.Chart) { Chart.register(...registerables); window.Chart = Chart; }
')}
{if not empty($chart)}
    {if isset($chart.agg)}
        {$charts = [$chart]}
    {else}
        {$charts = $chart}
    {/if}

    <div class="tiki-agg-renderers">
    {$cIndex = 0}
    {foreach $charts as $c}
        {$aggKey = isset($c.agg) ? $c.agg : ''}
        {$aggref = isset($aggregations[$aggKey]) ? $aggregations[$aggKey] : null}
        {if empty($aggKey) or empty($aggref) or empty($aggref.buckets)}
            <div class="alert alert-info">{tr}No data to render.{/tr}</div>
            {continue}
        {/if}

        {$idAttr   = isset($c.id)       ? $c.id       : 'tiki-agg-'|cat:$id|cat:'_'|cat:$cIndex}
        {$wAttr    = isset($c.width)    ? $c.width    : ''}
        {$hAttr    = isset($c.height)   ? $c.height   : ''}
        {$cssClass = isset($c.class)    ? $c.class    : ''}
        {$renderer = isset($c.renderer) ? $c.renderer : 'chartjs'}
        {$specBody = isset($c._body)    ? trim($c._body) : '{}'}

        <div class="tiki-agg-render{if $cssClass} {$cssClass|escape}{/if}"
             id="{$idAttr|escape}"
             data-tiki-agg-render="{$renderer|escape}"
             {if $wAttr or $hAttr}style="{if $wAttr}width:{$wAttr|escape}px;{/if}{if $hAttr}height:{$hAttr|escape}px;{/if}"{/if}>
            <script type="application/json" class="tiki-agg-spec">{$specBody}</script>
            <script type="application/json" class="tiki-agg-data">{$aggref|json_encode}</script>
            <canvas{if $wAttr} width="{$wAttr|escape}"{/if}{if $hAttr} height="{$hAttr|escape}"{/if}></canvas>
        </div>
        {$cIndex = $cIndex + 1}
    {/foreach}
    </div>
{/if}
