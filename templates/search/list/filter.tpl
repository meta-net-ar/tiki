<a name="list_filter{$filterCounter}"></a>
<div class="list_filter" id="list_filter{$filterCounter}">
    <form action="{$filterUrl}#list_filter{$filterCounter}" method="post">
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
                <input class="button submit btn btn-primary" type="reset" name="reset_filter" value="{tr}Reset{/tr}">
            </div>
        </div>
    </form>
</div>

{jq}
$('#list_filter{{$filterCounter}} input[name=reset_filter]').off('click').on('click', function() {
    window.location.href = $(this).closest('form').attr('action').replace('#list_filter{{$filterCounter}}', '');
});

$(window).on('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const filterCounter = '{{$filterCounter}}';
    const filterForm = $('#list_filter' + filterCounter + ' form');

    if (!filterForm.length) return;

    // Prevent multiple submits per page load
    let formSubmitted = false;

    if (urlParams.get('filter') === 'Filter') {
        urlParams.forEach((value, key) => {
            if (key === 'filter') return;
            if (!key.startsWith('tf_')) return;

            const input = filterForm.find('[name="' + key + '"]')[0];
            if (input) {
                input.setAttribute('value', value);
            }
        });

        if (!formSubmitted) {
            formSubmitted = true;
            filterForm.submit();
        }
    }

    filterForm.find('[name^="tf_"]').on('input', function() {

        const newParams = new URLSearchParams(window.location.search);

        filterForm.find('[name^="tf_"]').each(function() {
            newParams.set(this.name, this.value);
        });

        newParams.set('filter', 'Filter');
        const hash = '#list_filter' + filterCounter;

        // Handle param values change in the URL
        history.replaceState(null, '', '?' + newParams.toString() + hash);
    });
});
{/jq}
