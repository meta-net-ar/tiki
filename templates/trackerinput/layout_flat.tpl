{if $status}
    <div class="tracker-field-group mb-3">
        <label for="trackerinput_status">{tr}Status{/tr}</label>
        <div id="trackerinput_status">
            {include 'trackerinput/status.tpl' status_types=$status_types status=$status}
        </div>
    </div>
{/if}
<style>
    .m-bottom {
        margin-bottom: 5px;
    }
</style>
{$jscal = 0}
{foreach from=$fields item=field}
    {if $field.visibleInEditMode eq 'y'}
        <div class="tracker-field-group mb-3">
            {if empty($field.options_map.labelasplaceholder)}
                <label for="trackerinput_{$field.fieldId|escape}" {if $field.type eq 'h'}class="h{$field.options_map.level}" {/if}>
                    {$field.name|tra|escape}
                    {if $field.isMandatory eq 'y'}
                        <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>
                    {/if}
                </label>
            {/if}
            <br>
            {assign var="maxItems" value=$prefs.tracker_item_select_feature*1}
            {if !empty($field.type) and $field.type == 'e' and $field.options|json_decode|count > 0}
                {assign var="feildOption" value=$field.options|json_decode}
            {/if}
            {if ((!empty($field.type)
                and ($field.type eq 'M'  or ($field.type eq 'r' and $field.options_map.selectMultipleValues eq 1))
                and $field.options_array|count > $maxItems))
                or (!empty($field.type) and $field.type eq 'e' and $feildOption->selectall eq 1)
                or (!empty($field.type) and $field.type eq 'e' and $feildOption->selectall eq 0 and $field.list|count > $maxItems)}
                <div {if $field.type eq 'r'}class="m-bottom"{/if}>
                    <input type="button" class="btn btn-primary btn-sm multi-toggle-selection" title="{tr}Select All{/tr}" name="toggle_selection" data-field-id="{$field.fieldId|escape}" data-field-type="{$field.type|escape}" data-mode="select" value="{tr}Select All{/tr}">
                </div>
            {/if}
            <div id="trackerinput_{$field.fieldId|escape}">
                {trackerinput field=$field item=$item}
                {if !empty($field.description) && $field.type ne 'S'}
                    {if $field.descriptionIsParsed eq 'y'}
                        <div class="description form-text">{wiki objectId=$field.fieldId objectType="trackerfield" fieldName="description"}{$field.description}{/wiki}</div>
                    {else}
                        <div class="description form-text">{$field.description|tra|escape}</div>
                    {/if}
                {/if}
            </div>
        </div>
    {/if}
    {if $field.type == 'j'}{$jscal = 1}{/if}
{/foreach}
{jq}$('label').on("click", function() {$('input, select, textarea', '#'+$(this).attr('for')).trigger("focus");});{/jq}
{jq}
{* If select all exist for categorize tracker item then remove it and override it with newly added toggle selection feature  *}
const selectAllBox = $('div[id^="trackerinput_"]').find($('input[name="switcher"]'));
if (selectAllBox.length > 0) {
    selectAllBox.parent().remove();
}

if ($('.multi-toggle-selection').length > 0) {
    $('.multi-toggle-selection').click(function() {
        const btn = $(this);
        const fieldId = btn.data('field-id');
        const fieldType = btn.data('field-type');
        const mode = btn.data('mode');
        const elmName = 'ins_' + fieldId;
        const trackerFieldContainer = $('#trackerinput_' + fieldId);
        const allCheckboxes = trackerFieldContainer.find('input[type="checkbox"][name="' + elmName + '"]');
        const selectField = trackerFieldContainer.find("select[name='" + elmName + "']");
        const isSelect2 = selectField.data('select2');
        if (mode === 'select') {
            if (allCheckboxes && allCheckboxes.length) {
                allCheckboxes.prop('checked', true);
            }
            if (selectField && selectField.length) {
                if (isSelect2) {
                    var allValues = selectField.find('option').map(function() { return this.value }).get();
                    selectField.val(allValues).trigger('change');
                } else {
                    selectField.find('option').prop('selected', true);
                }
            }
            btn.val("{tr}Invert Selection{/tr}").data('mode', 'invert');
            btn.attr('title', "{tr}Invert Selection{/tr}");
            btn.removeClass('btn-primary').addClass('btn-secondary');
        } else {
            if (allCheckboxes && allCheckboxes.length) {
                allCheckboxes.each(function(){
                    $(this).prop('checked', !$(this).prop('checked'));
                });
            }
            if (selectField && selectField.length) {
                if (isSelect2) {
                    var selectedValues = selectField.val() || [];
                    var allOptions = selectField.find('option').map(function() { return this.value }).get();
                    var invertedSelection = allOptions.filter(option => !selectedValues.includes(option));
                    selectField.val(invertedSelection).trigger('change');
                } else {
                    selectField.find('option').each(function() {
                        $(this).prop('selected', !$(this).prop('selected'));
                    });
                }
            }
            btn.val("{tr}Select All{/tr}").data('mode', 'select');
            btn.attr('title', "{tr}Select All{/tr}");
            btn.removeClass('btn-secondary').addClass('btn-primary');
        }
    });
}
{/jq}
{if $jscal}
    {js_insert_icon type="jscalendar"}
{/if}
