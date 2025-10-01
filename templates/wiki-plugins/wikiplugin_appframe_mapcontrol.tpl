<a id="{$mapcontrol.id|escape}" href="#" title="{$mapcontrol.label|escape}" role="button">{icon _id=$mapcontrol.icon title=$mapcontrol.label class=$mapcontrol.class}</a>
{jq}
$('#appframe .map-container').on('initialized', function () {
    var container = this
        , link = '#{{$mapcontrol.id|escape}}'
        , vlayer
        , mode
        , controls = []
        , func
        , drawStyle = {
            fillColor: "#6699cc",
            strokeColor: "#6699cc",
            pointRadius: 5,
            fillOpacity: ".3",
            strokeDashstyle: "solid"
        }
        ;

    {{if !empty($mapcontrol.function)}}
        func = function () {
            {{$mapcontrol.function}};
            return false;
        };
    {{elseif $mapcontrol.mode}}
        mode = {{$mapcontrol.mode|json_encode}};
    {{else}}
        vlayer = container.vectors;
        {{if !empty($mapcontrol.control)}}
            controls.push({{$mapcontrol.control}});
        {{/if}}

        mode = {{$mapcontrol.label|json_encode}};
        container.modeManager.addMode({
            name: {{$mapcontrol.label|json_encode}},
            controls: controls
        });
    {{/if}}

    container.modeManager.register('activate', mode, function () {
        $(link).addClass('active');
    });
    container.modeManager.register('deactivate', mode, function () {
        $(link).removeClass('active');
    });

    if (func) {
        $(link).on("click", func);
    } else {
        $(link).on("click", function () {
            container.modeManager.switchTo(mode);
            return false;
        });
    }
});
{/jq}
