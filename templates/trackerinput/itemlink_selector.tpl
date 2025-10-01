<div class="item-link">
    {object_selector _id=$data.selector_id _simplevalue=$field.value _simplename=$field.ins_id _placeholder=$data.placeholder type="trackeritem" tracker_id=$field.options_map.trackerId tracker_status=$data.status _format=$data.format _sort=$data.sort}
    {if $field.options_map.addItems and $data.createTrackerItems}
        <a class="btn btn-primary insert-tracker-item" href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId}" role="button">{tr}{$field.options_map.addItems|escape}{/tr}</a>
        {jq}
        $('#item{{$item.itemId}}{{$field.ins_id|escape}}')
            .closest('.item-link')
            .find('.insert-tracker-item')
            .clickModal({
                success: function (data) {
                    $('#item{{$item.itemId}}{{$field.ins_id|escape}}')
                        .object_selector('set', "trackeritem:" + data.itemId, data.itemTitle);
                    $.closeModal();
                }
            });
        {/jq}
    {/if}
</div>
