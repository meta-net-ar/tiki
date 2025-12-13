{* This is intended as an example of how to present Elasticsearch facets, known now as aggregations.

Version 2  - use chartjs params natively in the plugins
-------------------------------------------------------
So anything not starting with an underscore will be passed directly to the chartjs plugin in the dataset.
See https://www.chartjs.org/docs for the full list (it's extensive!)

Example wiki page "chart" contents:

{LIST(}
  {filter field="tracker_id" content="1"}
  {facet name="tracker_field_testUser"}
  {facet name="deep_categories_under_1"}
  {OUTPUT(template="templates/examples/search/facet_charts2.tpl")}
    {chart _type="pie" _class="col-sm-4" label="{tr}Users{/tr}" backgroundColor="orange:yellow:red:purple:grey:blue:green:pink:black" borderColor="black"}
    {chart _type="line" _class="col-sm-8" label="{tr}Countries{/tr}" borderColor="orange" hoverColor="pink" lineTension="0"}
  {OUTPUT}
{LIST}

ChartJS options can be added in JSON format only so far (sorry), but single quotes need to be used instead of double, e.g.

    {chart _type="pie" _options="{'plugins':{'labels':{'render':'value'}},'responsive':true,'animation':{'animateRotate':false}}"}
    (this example uses an extra plugin from https://github.com/emn178/chartjs-plugin-labels)

Debugging:
    Add _debug="1" to the first chart adds var_dump output on most useful parameters and variables, e.g.
    {chart _type="pie" label="{tr}Test{/tr}" backgroundColor="red:grey:pink:black" _debug="1"}
 *}

{* if only one chart plugin is used it arrives on it's own, not in an array *}
{if not isset($chart[0])}{$chart = [$chart]}{/if}

{if not empty($chart[0]['_debug'])}
    <pre style="display: none;" class="results-dump">{$results|var_dump}</pre>
    <pre style="display: none;" class="facets-dump">{$facets|var_dump}</pre>
    <pre style="display: none;" class="charts-dump">{$chart|var_dump}</pre>
{/if}

{if not empty($facets)}

    {if empty($container)}
        {$containerClass = 'row'}
    {else}
        {$containerClass = $container.class}
    {/if}
    {$i = -1}
    {$moreDatasets = []}
    <div class="{$containerClass}">
        {foreach $facets as $facet}
            {$i = $i + 1}
            {if count($facet.options) gt 0}
                {if not isset($chart[$i]._class)}{$chart[$i]._class = 'col-sm-12'}{/if}
                {if not isset($chart[$i]._type)}{$chart[$i]._type = 'bar'}{/if}
                {if not isset($chart[$i]._id)}{$chart[$i]._id = 'chart_'|cat:$i}{/if}
                {if not isset($chart[$i]._size)}
                    {$chart[$i]._size = ['','']}
                {else}
                    {$chart[$i]._size = $chart[$i]._size|split:':'}
                {/if}

                {$thisDataset = []}
                {foreach $chart[$i] as $param => $value}
                    {if $param[0] neq '_'}
                        {if strpos($value, ':') neq false}
                            {$value = $value|split:':'}
                        {/if}
                        {$thisDataset[$param] = $value}
                    {/if}
                {/foreach}

                {$values = []}
                {$labels = []}
                {foreach $facet.options as $value => $label}
                    {if isset($label)}
                        {if strpos($label, 'trackeritem:0') !== false}
                            {continue}
                        {/if}
                        {$replaced = $label|escape|regex_replace:'/(.*?)\s+\((\d+)\)/':'$1~$2'}
                        {if $replaced}
                            {$matches = $replaced|split:'~'}
                            {$labels[] = $matches[0]}
                            {$values[] = $matches[1]}
                        {/if}
                    {/if}
                {/foreach}

                {$thisDataset['data'] = $values}

                {if not empty($chart[$i]._target)}
                    {if empty($moreDatasets[$chart[$i]._target])}{$moreDatasets[$chart[$i]._target] = []}{/if}
                    {$moreDatasets[$chart[$i]._target][] = $thisDataset}
                    {continue}
                {/if}
                {$datasets = [$thisDataset]}
                {if not empty($moreDatasets[$chart[$i]._id])}
                    {foreach $moreDatasets[$chart[$i]._id] as $dset}
                        {$datasets[] = $dset}
                    {/foreach}
                    {$moreDatasets[$chart[$i]._target] = []}
                {/if}
                {$data = ['data' => ['labels' => $labels,'datasets' => $datasets]]}

                {if not empty($chart[0]['_debug'])}<pre style="display: none;" class="data-dump">{$data|var_dump}</pre>{/if}

                {if not isset($chart[$i]._options)}
                    {$options = ['responsive' => true, 'maintainAspectRatio' => false]}{* some handy defaults *}
                {else}
                    {*convert ' to " as we're coming from inside a wiki plugin param value *}
                    {$options = $chart[$i]._options|replace:'\'': '"'|json_decode:false}
                {/if}
                {if not empty($chart[0]['_debug'])}<pre style="display: none;" class="data-options-options-dump">{$options|var_dump}</pre>{/if}

                {$data.options = $options}

                <div class="{$chart[$i]._class|escape}">
                    <label class="h3">
                        {if not empty($chart[$i].title)}
                            {$chart[$i].title|escape}
                        {else}
                            {$facet.label|replace:' (Tree)':''|tr_if|escape}
                        {/if}
                    </label>
                    {if not empty($chart[0]['_debug'])}<pre style="display: none;" class="data-options-dump">{$data|var_dump}</pre>{/if}

                    {wikiplugin _name='chartjs' type=$chart[$i]._type id=$chart[$i]._id width=$chart[$i]._size[0] height=$chart[$i]._size[1] debug=1}
                        {$data|json_encode}
                    {/wikiplugin}
                </div>
            {/if}
        {/foreach}
    </div>
{else}
    {if $prefs.unified_engine neq 'elastic' and $prefs.unified_engine neq 'manticore'}
        {remarksbox type="error" title="{tr}Search engine not supported{/tr}"}
            {tr}Only Elasticsearch and Manticore Search engines support facets, not {$prefs.unified_engine}{/tr}
        {/remarksbox}
    {/if}
{/if}
