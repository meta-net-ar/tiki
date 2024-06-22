{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <div id="tracker_selector" class="rounded bg-white position-absolute shadow px-2 pb-3">
        <h6 class="py-3 text-muted border-bottom">
            {tr}Move to tracker item{/tr}
            <span class="close-tracker-selector float-end">
                <a href="#">&times;</a>
            </span>
        </h6>
        {foreach from=$fields_data item=field_data}
            <a href="#" class="object_selector_trigger" data-tracker="{$field_data['tracker_id']}" data-field="{$field_data['field_id']}">{$field_data['name']}</a>

            {object_selector _id="file_tracker_{$field_data['tracker_id']}" _name=tracker_item_selector type=trackeritem tracker_id=$field_data['tracker_id']}
        {/foreach}
    </div>
{/block}

{jq}
    $('#tracker_selector').on('click', '.close-tracker-selector', function (e) {
        $('#tracker_selector').hide();
    });

    $('#tracker_selector').on('click', '.object_selector_trigger', function (e) {
        var $basic_selector = $(this).next().find('.basic-selector');
        if (!$basic_selector.hasClass('d-none')) {
            $basic_selector.addClass('d-none');
        } else {
            $basic_selector.removeClass('d-none mb-3');
        }
    });

    $(document).on('ready.object_selector', function (event, container) {
        $(container).find('.basic-selector').addClass('d-none');
    });

    if (jqueryTiki.select2) {
        $('#tracker_selector .object-selector').find('.form-select').tiki('select2');
    }
{/jq}
