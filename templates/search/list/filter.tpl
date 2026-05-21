<a name="list_filter{$filterCounter}"></a>
<div class="list_filter" id="list_filter{$filterCounter}">
    <form action="{$filterUrl}#list_filter{$filterCounter}" method="get" id="list_filter{$filterCounter}_form">
        {foreach from=$filterHiddenParams key=k item=v}
            {if is_array($v)}
                {foreach from=$v key=vk item=vv}
                    <input type="hidden" name="{$k|escape}[{$vk|escape}]" value="{$vv|escape}">
                {/foreach}
            {else}
                <input type="hidden" name="{$k|escape}" value="{$v|escape}">
            {/if}
        {/foreach}
        <div class="row">
            {foreach from=$filterFields item=field}
                <div class="col-lg-6 col-12 mb-3">
                    <div class="list_filter_label">
                        <label for="{$field.id|escape}">{$field.name|tr_if}</label>
                        {if !empty($field.textInput)}
                            {if $field.type == 'f'}
                                <a href="#" role="button" class="tikihelp" title="{tr}Date selector : Apply a range of time between two dates{/tr}.">
                                    {icon name="information"}
                                </a>
                            {else}
                                <a href="#" role="button" class="tikihelp" title="{tr}Only full word matches shown by default: Use wildcards (*) to get partial matches also. E.g. searching for 'foo' will miss foobar in the results, but 'foo*' will include it{/tr}.">
                                    {icon name="information"}
                                </a>
                            {/if}
                        {/if}
                    </div>
                    <div class="list_filter_input">
                        {$field.renderedInput}
                    </div>
                </div>
            {/foreach}
        </div>
        <div class="row mb-3 justify-content-center">
            <div class="col-auto">
                <input class="button submit btn btn-primary" type="submit" name="filter" value="{tr}Filter{/tr}">
                <input class="button submit btn btn-secondary" type="button" name="reset_filter" value="{tr}Reset{/tr}" id="list_filter{$filterCounter}_reset">
            </div>
        </div>
    </form>
</div>

{jq}
$('#list_filter{{$filterCounter}}_reset').off('click').on('click', function() {
    const $form = $('#list_filter{{$filterCounter}}_form');
    $form.find(':input')
    .not(':hidden, :submit, :button, :reset')
    .each(function() {
        if ($(this).is(':checkbox, :radio')) {
            this.checked = false;
        } else {
            $(this).val('');
        }
    });
    $form[0].submit();
});
{/jq}
