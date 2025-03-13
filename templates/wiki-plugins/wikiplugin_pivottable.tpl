{jq}
    var pivotData{{$pivottable.index}} = {{$pivottable.data|json_encode}};
    $('#output_{{$pivottable.id}}').each(function () {
        var pivotLocale = $.pivotUtilities.locales[{{$lang|json_encode}}];
        $.each($.pivotUtilities.subtotal_renderers, function(name, renderer) {
            $.pivotUtilities.subtotal_renderers[name] = function(pvtData, opts) {
                var result = renderer(pvtData, opts);
                $(result).find('th.pvtRowLabel.pvtRowSubtotal').each(function(i, el) {
                    if (! $(el).text()) {
                        $(el).text('{tr}Subtotal{/tr}');
                    }
                });
                return result;
            }
        });
        var renderers = $.extend(pivotLocale ? pivotLocale.renderers : $.pivotUtilities.renderers, $.pivotUtilities.plotly_renderers, $.pivotUtilities.subtotal_renderers);
        var opts = {
            renderers: renderers,
            rendererOptions: {
                pivotId: {{$pivottable.id|json_encode}},
                highlight: {{$pivottable.highlight|json_encode}},
                highlightChartType: {{$pivottable.highlightChartType|json_encode}},
                localeStrings: {
                    vs: "{tr}vs{/tr}",
                    by: "{tr}by{/tr}",
                    and: "{tr}and{/tr}",
                    all: "{tr}All{/tr}",
                    mine: "{tr}Mine{/tr}",
                    group: "{tr}Group{/tr}"
                },
                c3: {
                    size: {
                        width: {{$pivottable.width|json_encode}},
                        height: {{$pivottable.height|json_encode}}
                    }
                },
                xAxisLabel: {{$pivottable.xAxisLabel|json_encode}},
                yAxisLabel: {{$pivottable.yAxisLabel|json_encode}},
                chartTitle: {{$pivottable.chartTitle|json_encode}},
                chartHoverBar: {{$pivottable.chartHoverBar|json_encode}},
                dataCallback: {{$pivottable.dataCallback|json_encode}}
            },
            derivedAttributes: { {{$pivottable.derivedAttributes|join:','}} },
            cols: {{$pivottable.tcolumns|json_encode}}, rows: {{$pivottable.trows|json_encode}},
            rendererName: {{$pivottable.rendererName|json_encode}},
            dataClass: $.pivotUtilities.SubtotalPivotData,
            aggregatorName: pivotLocale && pivotLocale.aggregators[{{$pivottable.aggregatorName|json_encode}}] ? {{$pivottable.aggregatorName|json_encode}} : null,
            vals: {{$pivottable.vals|json_encode}},
            inclusions: {{$pivottable.inclusions}},
            colOrder: {{$pivottable.colOrder|json_encode}},
            rowOrder: {{$pivottable.rowOrder|json_encode}},

            sorters: function(attr) {
                if($.inArray(attr, {{$pivottable.dateFields|json_encode}}) > -1) {
                    return function(a, b) {
                        return ( Date.parse(a) || 0 ) - ( Date.parse(b) || 0 );
                    }
                }
                var attributesOrder = {{$pivottable.attributesOrder|json_encode}};
                if (attributesOrder[attr]) {
                    return $.pivotUtilities.sortAs(attributesOrder[attr]);
                }
            },
            onRefresh: function() {
                stickyTableHeaders({{$pivottable.id|json_encode}}, {{$pivottable.allowStickyHeaders|json_encode}});
            },

            {{if !empty($pivottable.heatmapParams)}}
            rendererOptions: {
                heatmap: {
                    colorScaleGenerator: function(values) {
                        return Plotly.d3.scale.linear()
                            .domain({{$pivottable.heatmapParams.domain|json_encode}})
                            .range({{$pivottable.heatmapParams.colors|json_encode}});
                    }
                }
            },
            {{/if}}

            highlightMine: {{$pivottable.highlightMine|json_encode}},
            highlightGroup: {{$pivottable.highlightGroup|json_encode}},
            highlightRequest: {{$pivottable.highlightRequest|json_encode}},
            highlightChartType: {{$pivottable.highlightChartType|json_encode}},
            xAxisLabel: {{$pivottable.xAxisLabel|json_encode}},
            yAxisLabel: {{$pivottable.yAxisLabel|json_encode}},
            chartTitle: {{$pivottable.chartTitle|json_encode}},
            chartHoverBar: {{$pivottable.chartHoverBar|json_encode}}
        };
        if( {{$pivottable.menuLimit|json_encode}} ) {
            opts.menuLimit = {{$pivottable.menuLimit|json_encode}};
        }
        if( {{$pivottable.aggregateDetails|json_encode}} ) {
            var clickCB = function(e, value, filters, pivotData){
                var details = [];
                var formatted = "";
                pivotData.forEachMatchingRecord(filters, function(record){
                    if (record.pivotLink) {
                        details.push(record.pivotLink);
                    }
                });
                if ({{$pivottable.aggregateDetailsCallback|json_encode}} && window["{{$pivottable.aggregateDetailsCallback}}"]) {
                    formatted = {{$pivottable.aggregateDetailsCallback}}(e, value, filters, pivotData, details);
                } else {
                    formatted = details.join("<br>\n");
                }
                feedback(formatted, 'info', true);
            }
            opts.aggregateDetails = {{$pivottable.aggregateDetails|json_encode}};
            opts.aggregateDetailsFormat = {{$pivottable.aggregateDetailsFormat|json_encode}};
            opts.aggregateDetailsCallback = {{$pivottable.aggregateDetailsCallback|json_encode}};
            opts.rendererOptions.table = {
                clickCallback: clickCB,
                eventHandlers: {
                    "click": clickCB
                }
            };
        }

        $("#output_{{$pivottable.id}}").pivotUI(pivotData{{$pivottable.index}}, opts, false, {{$lang|json_encode}});

        $("#pivotEditBtn_{{$pivottable.id}}").on("click", function(){
            showControls("#output_{{$pivottable.id}}",{{$pivottable.id|json_encode}});
        });

        $("#restore_{{$pivottable.id}}").on("click", function(){
            $("#output_{{$pivottable.id}}").pivotUI(pivotData{{$pivottable.index}},opts,true,{{$lang|json_encode}});
            $("#output_{{$pivottable.id}}_opControls").fadeOut();
        });

        $("#save_{{$pivottable.id}}").on("click", function(){
            saveConfig("#output_{{$pivottable.id}}", "{{$pivottable.page}}", {{$pivottable.index|json_encode}}, {{$pivottable.dataSource|json_encode}}, {{$pivottable.fieldsArr|json_encode}}, {{$pivottable.defaults|json_encode}});
        });

        createEditBtn({{$pivottable.id|json_encode}});
        stickyTableHeaders({{$pivottable.id|json_encode}}, {{$pivottable.allowStickyHeaders|json_encode}})
    });
    //adding bind call for pdf creation
    $('a.generate-pdf').on("click", function(){
        storeSortTable('#container_{{$pivottable.id}}',$('#container_{{$pivottable.id}}').find(".pvtRendererArea"))
    });
{/jq}

<style type="text/css">
    #output_{$pivottable.id} .pvtVals,.pvtAxisContainer, .pvtUnused,.pvtRenderer, .pvtAxisContainer {
        display:none;

    }
</style>

<div id="container_{$pivottable.id}">
    <div id="output_{$pivottable.id}"></div>
    <div id="output_{$pivottable.id}_opControls" style="display:none">
        <input id="save_{$pivottable.id}" type="button" value="{tr}Save Changes{/tr}" class="btn btn-primary ui-button ui-corner-all ui-widget" />
        <input class="btn btn-secondary ui-button ui-corner-all ui-widget" id="restore_{$pivottable.id}" type="button" value="{tr}Cancel Edit{/tr}" />
    </div>
    {if !empty($pivottable.showControls)}
        <div id="pivotControls_{$pivottable.id}" style="display:none;position:relative;">
            <input type="button" id="pivotEditBtn_{$pivottable.id}" value="{tr}Edit Pivot Table{/tr}" class="btn btn-primary ui-button ui-corner-all ui-widget" />
        </div>
    {/if}
    <img id="png_container_{$pivottable.id}" style="display:none">
</div>

<div id="pivotdetails_modal"></div>

<input type="hidden" id="pivottable-ticket" name="ticket" value="{ticket mode=get}">
