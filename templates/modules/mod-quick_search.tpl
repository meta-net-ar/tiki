{tikimodule error=$module_params.error title=$tpl_module_title name=$module_params.name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
<form class="mod_quick_search search-box" method="get" action="tiki-searchindex.php">
    <div class="mb-2">
        <label class="form-label" for="filter-content">{tr}Search Terms{/tr}</label>
        <input type="text" name="filter~content" id="filter-content" value="{$qs_prefill.content|escape}" class="form-control"/>
    </div>
    {if $qs_types}
        <div class="mb-2">
        <label class="form-label" for="filter-type">{tr}Type{/tr}</label>
            <select class="form-select" name="filter~type" id="filter-type">
                <option value="">{tr}Any type{/tr}</option>
                {foreach from=$qs_types item=label key=val}
                    <option value="{$val|escape}"{if $qs_prefill.type eq $val} selected="selected"{/if}>{$label|escape}</option>
                {/foreach}
            </select>
        </div>
    {elseif $qs_prefill}
        <input type="hidden" name="filter~type" value="{$qs_prefill.type|escape}"/>
        </form>
    {/if}

    {if $prefs.feature_categories eq 'y'}
        {if $qs_categories|@count == 1}
            <input type="hidden" name="filter~categories" value="{$qs_prefill.categories|escape}"/>
        {elseif $qs_categories|@count > 1}
            <div class="mb-2">
            <label class="form-label" for="filter-categories">{tr}Categories{/tr}</label>
                <select class="form-select" name="filter~categories" id="filter-categories">
                    <option value="{$qs_all_categories|escape}">{tr}Any{/tr}</option>
                    {foreach from=$qs_categories item=label key=categId}
                        <option value="{$categId|escape}"{if $qs_prefill.categories eq $categId} selected="selected"{/if}>{$label|escape}</option>
                    {/foreach}
                </select>
            </div>
        {/if}
    {/if}

    <div class="text-center">
        <input type="submit" class="btn btn-primary" value="{tr}Search{/tr}"/>
        <input type="hidden" name="save_query" value="{$moduleId|escape}"/>
    </div>
    <div class="results">
    </div>
</form>
{/tikimodule}
{jq}
$('.mod_quick_search:not(.done)').addClass('done').on("submit", function () {
    var query = $(this).serialize();
    var results = $('.results', this).empty();

    var submitButton = $(this).find('input[type="submit"]');
    submitButton.prop('disabled', true).val('{tr}Processing{/tr}...');

    $.getJSON($(this).attr('action'), query, function (data) {
        var ol = $('<ol/>');
        results.append(ol);

        if (data.result.length === 0) {
            results.append('<p>{tr}No results for query{/tr}.</p>');
        } else {
            $.each(data.result, function (k, item) {
                var li = $('<li/>');
                var link = item.link;

                var itemTypeSpan = $('<em/>', {
                    text: item.object_type
                });
                li.append(link, '<br>', itemTypeSpan);
                ol.append(li);
            });
        }
    });

    submitButton.prop('disabled', false).val('{tr}Search{/tr}');

    return false;
})
{{if !empty($qs_prefill.trigger)}}
    .trigger("submit")
{{/if}}
;
{/jq}
