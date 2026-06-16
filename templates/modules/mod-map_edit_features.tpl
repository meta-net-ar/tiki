{if !empty($edit_features.field)}
{tikimodule error=$module_params.error title=$tpl_module_title name="map_edit_features" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <form class="map-edit-features" method="post" action="{service controller=tracker action=insert_item}">
        <div class="submit">
            {ticket}
            <input type="hidden" name="trackerId" value="{$edit_features.trackerId|escape}"/>
            <input type="hidden" name="controller" value="tracker"/>
            <input type="hidden" name="action" value="insert_item"/>
            <input class="feature-content" id="{$edit_features.field.fieldId|escape}" type="hidden" name="forced~{$edit_features.field.permName|escape}"/>
            {foreach from=$edit_features.hiddenInput key=f item=v}
                <input id="{$f|escape}" type="hidden" name="forced~{$f|escape}" value="{$v|escape}"/>
            {/foreach}
            <input type="submit" class="btn btn-primary btn-sm" name="create" value="{tr}Create{/tr}"/>
        </div>
    </form>
    {jq}
    $(function () {
        const $form = $(".map-edit-features").hide();
        let container, activeFeature;

        $form
            .removeClass("map-edit-features")
            .on("submit", function () {
                if (! activeFeature) {
                    return false;
                }

                var form = this;
                $.post($(form).attr("action"), $(form).serialize(), null, "json")
                    .done(function (data) {
                        $(form).trigger("insert", [data]);
                    })
                    .fail(function () {
                        $.openModal({
                            title: $(":submit", form).val(),
                            open: () => {
                                $(form).trigger("insert", [{}]);
                                $(".modal.fade").on("hidden.bs.modal", function () {
                                    $(form).trigger("cancel");
                                });
                            }
                        })
                    });

                return false;
            })
            .each(function () {
                container = $(this).closest(".tab, #appframe, body").find(".map-container").first().get(0);

                if (! container) {
                    return;
                }

                $(this).show();
            });

        {*  featuremodified: function (event) {
            if (event.feature === activeFeature) {
                saveFeature();
            }
        }

        {if not empty($edit_features.standardControls)}}
            TODO fix this for non cartograf applications
            modify = new OpenLayers.Control.ModifyFeature(vlayer, {
                mode: OpenLayers.Control.ModifyFeature.DRAG | OpenLayers.Control.ModifyFeature.RESHAPE,
            });
            toolbar = new OpenLayers.Control.EditingToolbar(vlayer);
            toolbar.addControls([modify]);

            container.modeManager.addMode({
                name: "Draw",
                controls: [ toolbar ]
            });
        {{/if}*}

        $form.on("insert", function (e, data) {
            var form = this;

            $(container).trigger("changed");
            container.vectors.getSource().removeFeature(activeFeature);
            activeFeature = null;

            {{if !empty($edit_features.editDetails)}}
            if (data.itemId) {
                $.openModal({
                    remote: $.service("tracker", "update_item", {trackerId: $(form.trackerId).val(), itemId: data.itemId}),
                    open: () => $(container).trigger("changed")
                });
            }
            {{/if}}

            {{if !empty($edit_features.insertMode)}}
                container.modeManager.switchTo({{$edit_features.insertMode|json_encode}});
            {{/if}}
        });

        $(document).on("drawend.tiki", function (event, olEvent) {
            if (olEvent.feature) {
                activeFeature = olEvent.feature;
                var format = new ol.format.GeoJSON;

                if (! activeFeature.get("intent") !== "marker") {
                    $form.find(".feature-content").val(format.writeFeature(activeFeature));
                    $form.trigger("submit");
                }
            }
        });
    });
    {/jq}
{/tikimodule}
{else}
    {remarksbox type=warning title="{tr}Module misconfigured{/tr}"}
        {tr}No acceptable field to store the feature was found in the specified tracker.{/tr}
    {/remarksbox}
{/if}
