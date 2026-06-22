{if !empty($tracker_input.trackerId)}
{tikimodule error=$module_params.error title=$tpl_module_title name="tracker_input" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    <form class="mod-tracker-input simple" method="get" action="{service controller=tracker action=insert_item}" data-location="{$tracker_input.location|escape}" data-location-mode="{$tracker_input.locationMode|escape}" data-streetview="{$tracker_input.streetview|escape}" data-success="{$tracker_input.success|json_encode|escape}" data-modal-size="modal-lg">
        {foreach from=$tracker_input.textInput key=token item=label}
            <label class="form-label">
                {$label|escape}
            </label>
            <input class="form-control" type="text" id="forced-($token|escape)" name="forced~{$token|escape}"/>

        {/foreach}
        <div class="submit">
            <input type="hidden" name="trackerId" value="{$tracker_input.trackerId|escape}"/>
            <input type="hidden" name="controller" value="tracker"/>
            <input type="hidden" name="action" value="insert_item"/>
            <input type="submit" name="create" value="{$tracker_input.submit|escape}" class="btn btn-primary btn-sm" />
            {foreach from=$tracker_input.hiddenInput key=f item=v}
                <input id="{$f|escape}" type="hidden" name="forced~{$f|escape}" value="{$v|escape}"/>
            {/foreach}
        </div>
    </form>

    {jq}
    function hasEmptyField(form, scope) {
        let hasEmpty = false;
        $(scope, form).each(function () {
            if ($(this).val() === '') {
                hasEmpty = true;
            }
        });

        return hasEmpty;
    }
    $(".mod-tracker-input").removeClass("mod-tracker-input").on("submit", function (e) {
        e.preventDefault();
        const form = this;
        if (hasEmptyField(form, ":input:not(:submit)")) {
            $(":submit", form).showError("{tr}Missing values{/tr}");
            return false;
        }

        const actionUrl = $(form).attr("action");
        const serialized = $(form).serialize();
        let fullUrl = actionUrl;
        if (serialized) {
            fullUrl += (actionUrl.indexOf("?") === -1 ? "?" : "&") + serialized;
        }
        
        $.clickModal({
            title: $(":submit", form).val(),
            success: function (data) {
                $(form).trigger("insert", [ data ]);
                {{if !empty($tracker_input.insertMode)}}
                    $(form).closest(".tab, #appframe, body").find(".map-container")[0].modeManager.switchTo("{{$tracker_input.insertMode|escape}}");
                {{/if}}

                $(".modal").one("hidden.bs.modal", function () {
                    $(form).trigger("cancel");
                    {{if !empty($tracker_input.insertMode)}}
                        $(form).closest(".tab, #appframe, body").find(".map-container")[0].modeManager.switchTo("{{$tracker_input.insertMode|escape}}");
                    {{/if}}
                });
                
                $.closeModal();
            }
        }, fullUrl).call(this, e);
    }).each(function () {
        const form = this,
            location = $(this).data("location"),
            locationMode = $(this).data("location-mode"),
            streetview = $(this).data("streetview"),
            success = $(this).data("success");

        if (success.operation === "redirect") {
            $(form).on("insert", function (e, data) {
                let url = success.argument;

                data.fields.itemId = data.itemId;
                data.fields.status = data.status;
                $.each(data.fields, function (k, v) {
                    url = url.replace("@" + k + "@", encodeURIComponent(v));
                });

                document.location.href = url;
            });
        }

        let container = $(form).closest(".tab, #appframe, body").find(".map-container")[0];
        $(":submit", form).hide();
        $(container).one("initialized", function () {
            let control, button, modeManager, newMode;
            modeManager = container.modeManager;

            if (locationMode === "marker") {
                control = $(container).setupMapSelection({
                    field: $("#" + location),
                    click: function () {
                        $(form).trigger("submit");
                    }
                });
                if (control) {
                    control.setActive(false);
                    modeManager.addMode({name: newMode = "{{$tpl_module_title}}", controls: [control]});
                }
            }

            button = $("<input type=\"submit\" class=\"btn btn-primary btn-sm\" />")
                .val($(":submit", form).val())
                .on("click", function () {
                    if (newMode) {
                        modeManager.switchTo(newMode);
                        return false;
                    }
                    const coords = $(container).getMapCenter();
                    $("#" + location).val(coords + "," + parseInt(container.map.getView().getZoom()))
                })
                .appendTo(form);

            $(":text", form).on("keyup", function (e) {
                if (hasEmptyField(form, ":text")){
                    button.attr("disabled", "disabled");
                }else {
                    button.removeAttr("disabled");
                }

                if (e.which === 13) {
                    button.trigger("click");
                }
            }).trigger("keyup");

            $(form).on("insert", function () {
                $(container).removeMapSelection();
                $(container).trigger("changed");
            });
            $(form).on("cancel", function () {
                $(container).removeMapSelection();
            });

            if (streetview && container.streetview) {
                container.streetview.addButton("{tr}Add Marker{/tr}", function (canvas) {
                    const url = canvas.getImageUrl(), position = canvas.getPosition();
                    $.ajax({
                        type: "POST",
                        url: $.service("file", "remote"),
                        dataType: "json",
                        data: {
                            galleryId: "{{$tracker_input.galleryId|escape}}",
                            url: url,
                            reference: 1
                        },
                        success: function (data) {
                            const input = $("<input type=\"hidden\" name=\"forced~\"" + streetview + "\"/>")
                                .val(data.fileId)
                                .appendTo(form);
                            $("#" + location).val(position);

                            $(form).trigger("submit");
                            input.remove();
                        },
                        complete: function () {
                            $.closeModal();
                        }
                    });
                });
            }
        });
    });
    {/jq}
{/tikimodule}
{else}
{tr}Permission denied{/tr}
{/if}
