<ol>
    {foreach from=$data.movies item=movie}
        <li>
            <input type="checkbox" class="form-check-input" name="{$field.html_name|escape}" id="{$field.ins_id|escape}" value="{$movie.id|escape}" checked="checked">
            <input type="hidden" name="old_{$field.html_name|escape}" value="{$movie.id|escape}">
            <label for="{$field.ins_id|escape}" class="form-check-label">
                {$movie.name|escape}
            </label>
        </li>
    {/foreach}
</ol>
<a class="add-kaltura-media btn btn-primary btn-sm" href="{service controller=kaltura action=upload targetName="{$field.html_name}"}" role="button">{tr}Add Media{/tr}</a>
<a class="list-kaltura-media btn btn-primary btn-sm" href="{bootstrap_modal controller=kaltura action=list targetName="{$field.html_name}"}" role="button">{tr}List Media{/tr}</a>
{foreach from=$data.extras item=entryId}
    <input type="hidden" name="{$field.html_name|escape}" value="{$entryId|escape}">
{/foreach}
{if $data.extras|count}
    <span class="highlight">+{$data.extras|count}</span>
{/if}
{jq}
$('.add-kaltura-media').clickModal({
    title: $(this).text(),
    success: function (data) {
        $("#bootstrap-modal").show();
        $.each(data.entries, function (k, entry) {
            const hidden = $('<input type="hidden">')
                .attr('name', '{{$field.html_name|escape}}')
                .attr('value', entry)
                ;
            $(link).parent().append(hidden);
        });
        $(link).parent().find('span').remove();
        $(link).parent().append($('<span class="highlight"/>')
            .text('+' + $(this).parent().find('input').length));

    },
});
{/jq}
