{tikimodule error=$module_params.error title=$tpl_module_title name="map_layer_selector" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <form class="map-layer-selector" method="post" action="">
        {if !empty($controls.baselayer)}
            <select name="baseLayers">
            </select>
        {/if}

        {if !empty($controls.optionallayers)}
            <div class="optionalLayers">
            </div>
        {/if}
    </form>
    {jq}
    $('.map-layer-selector').hide();
    $(function () {
        $('.map-container').one('initialized', function () {
            $('.map-layer-selector').removeClass('map-layer-selector').each(function () {
                const container = $(this).closest('.tab, #appframe, body').find('.map-container').first()[0],
                    $baseLayers = $("select[name=baseLayers]", this),
                    $optionalLayers = $('.optionalLayers', this);

                if (! container) {
                    return;
                }

                $(this).show();

                $baseLayers.on("change", function () {
                    const layers = container.map.getLayers();
                    LayerSwitcher.setVisible_(container.map, layers.item($(this).val()));
                });

                const refreshLayers = function () {
                    $baseLayers.empty();
                    $optionalLayers.empty();

                    for (let layerName in container.layers) {

                        const thisLayer = container.layers[layerName];

                        // not in ol now?
                        // if (! outerLayer.displayInLayerSwitcher) {
                        //     return;
                        // }

                        if (thisLayer.get("type") === "base") {
                            $baseLayers.append($("<option>")
                                .attr('value', k)
                                .text(tr(thisLayer.get("title")))
                                .prop('selected', thisLayer === container.map.baseLayer));
                        } else {
                            $optionalLayers
                                .append($("<label>")
                                .text(thisLayer.get("title"))
                                .prepend(
                                    $("<input type=\"checkbox\" class=\"form-check-input\">")
                                        .prop("checked", thisLayer.isVisible())
                                        .on("change", function (e) {
                                            thisLayer.setVisible($(this).is(':checked'));
                                        })
                                    ));
                        }

                    };
                    $baseLayers.trigger("change");
                    // TODO need to get the Element Plus shadow object to update somehow
                    $(`#${$baseLayers.attr('element-plus-ref')}`).trigger("change");
                };

                if (! $(container).data("layer_selector_init")) {
                    refreshLayers();

                    container.map.getLayers().on("add", refreshLayers);
                    container.map.getLayers().on("remove", refreshLayers);

                    container.map.on("change:layerGroup", function () {
                        // In case the whole layer group changes
                        container.map.getLayers().on("add", refreshLayers);
                        container.map.getLayers().on("remove", refreshLayers);
                    });

                    // remove the standard layer switcher if present
                    container.map.getControls().forEach((control) => {
                        if (control && $(control.element ).is(".layer-switcher")) {
                            //container.map.removeControl(control);
                            //$(control.element ).hide();
                        }
                    });

                    $(container).data("layer_selector_init", true);
                }
            });
        });
    });
    {/jq}
{/tikimodule}
