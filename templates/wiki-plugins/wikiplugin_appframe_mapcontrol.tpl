<a id="{$mapcontrol.id|escape}" href="#" title="{$mapcontrol.label|escape}" role="button">{icon _id=$mapcontrol.icon title=$mapcontrol.label class=$mapcontrol.class}</a>
{jq}
$('#appframe .map-container').on('initialized', function () {
    const container = this,
        link = '#{{$mapcontrol.id|escape}}',
        drawStyle = {
            fillColor: "#6699cc",
            strokeColor: "#6699cc",
            pointRadius: 5,
            fillOpacity: ".3",
            strokeDashstyle: "solid"
        };
    let vlayer, mode, controls = [], func;


    {{if !empty($mapcontrol.function)}}
        func = function () {
            {{$mapcontrol.function}};
            return false;
        };
    {{elseif $mapcontrol.mode}}
        mode = "{{$mapcontrol.mode|escape}}";
    {{else}}
        vlayer = container.vectors;
        {{if !empty($mapcontrol.control)}}
            controls.push({{$mapcontrol.control}});
        {{/if}}

        controls.forEach((control) => {
            control.setActive(false);
            container.map.addInteraction(control);
        });
        container.modeManager.addMode({
            name: {{$mapcontrol.name|json_encode}},
            title: "{{$mapcontrol.label|escape}}",
            controls: controls
        });
        container.setupLayerEvents(vlayer);
    {{/if}}

    container.modeManager.register('activate', {{$mapcontrol.name|json_encode}}, function () {
        $(link).addClass('active');
    });
    container.modeManager.register('deactivate', {{$mapcontrol.name|json_encode}}, function () {
        $(link).removeClass('active');
    });

    if (func) {
        $(link).on("click", func);
    } else {
        $(link).on("click", function () {
            container.modeManager.switchTo({{$mapcontrol.name|json_encode}});
            return false;
        });
    }
});
{/jq}
