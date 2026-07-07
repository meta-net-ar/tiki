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
                const container = $(this).closest('.tab, #appframe, body').find('.map-container').first()[0]
                    , $baseLayers = $("select[name=baseLayers]")
                    , $optionalLayers = $('.optionalLayers', tr(this)) /* e.g. tr('Editable') to be translatable via lang/../language.js */
                    ;

                if (! container) {
                    return;
                }

                $(this).show();

                $baseLayers.on("change", function () {
                    if (container.map) {
                        $(
                            "#" +
                            $(".layer-switcher-base-group", ".layer-switcher")
                            .find("label:contains('" + $(":selected", this).text() + "')")
                            .attr("for")
                        ).trigger("click")

                    }
                });

                const refreshLayers = function () {
                    $baseLayers.empty();
                    $optionalLayers.empty();
                    const layers = container.map.getLayers();
                    if (layers.getLength() === 1) {
                        layers = [layers];
                    }
                    layers.forEach((outerLayer, k) => {
                        // not in ol now?
                        // if (! outerLayer.displayInLayerSwitcher) {
                        //     return;
                        // }

                        let innerLayers;

                        if (typeof outerLayer.getLayers === "function") {
                            innerLayers = outerLayer.getLayers();
                        } else {
                            innerLayers = [ outerLayer ];
                        }
                        innerLayers.forEach(thisLayer => {
                            if (thisLayer.get("type") === "base") {
                                $baseLayers.append($('<option>')
                                    .attr('value', $baseLayers.find("option").length)
                                    .text(tr(thisLayer.get("title")))
                                    .prop('selected', thisLayer === container.map.baseLayer));
                            } else {
                                var label, checkbox;
                                $optionalLayers.append(label = $('<label>').text(thisLayer.get("title") ).prepend(
                                    checkbox = $('<input type="checkbox" class="form-check-input"/>')
                                        .prop('checked', thisLayer.isVisible())));
                                checkbox.on("change", function (e) {
                                    thisLayer.setVisible($(this).is(':checked'));
                                });
                            }
                        });
                    });
                    $baseLayers.trigger("change");
                };

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

            });
        });
    });
    {/jq}
{/tikimodule}
