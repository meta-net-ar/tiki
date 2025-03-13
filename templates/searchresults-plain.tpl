{if $facets|@count}
    <div class="facets" style="width: 25%; float: right;">
        {foreach from=$facets item=facet}
            <div class="mb-3 row facet-hide-group">
                <label for="{$facet.name|escape}">{$facet.label|escape}</label>
                <select id="{$facet.name|escape}" class="form-select" multiple data-for="#search-form input[name$='filter~{$facet.name|escape}']" data-join="{$facet.operator|escape}">
                    {foreach from=$facet.options key=value item=label}
                        <option value="{$value|escape}">{$label|escape}</option>
                    {/foreach}
                </select>
            </div>
        {/foreach}
        <div class="mb-3 row">
            <button class="btn btn-primary">{tr}Filter{/tr}</button>
        </div>
    </div>
    {jq}
        $('.facets select').registerFacet();
        $('.facets button').on("click", function () {
            $('#search-form').trigger("submit");
        });

        // remove empty inputs to keep the url clean
        $('#search-form').on("submit", function () {
            $(this)
                .find('input[name]')
                .filter(function () {
                    return ! this.value && this.name !== 'filter~content';
                })
                .prop('name', '');
        });
    {/jq}
{/if}
<div>
<ul class="searchresults">
    {if $results->didYouMean}
        <i>{tr}Found results for:{/tr} <b>{$results->didYouMean}</b></i>
    {/if}
    {foreach item=result from=$results}
    <li>
        <strong>
        {if !empty($result._external)}
            {object_link type=external id=$result.url title=$result.title}
        {else}
            {object_link type=$result.object_type id=$result.object_id title=$result.title url=$result.url}
        {/if}

        {if $prefs.feature_search_show_object_type eq 'y'}
            (<span class="objecttype">{tr}{$result.object_type|escape}{/tr}</span>)
        {/if}

        {if $prefs.feature_search_show_pertinence eq 'y' and !empty($result.score)}
            <span class="itemrelevance">({tr}Relevance:{/tr} {$result.score|escape})</span>
        {/if}

        {if $prefs.feature_search_show_visit_count eq 'y' and $result.visits neq null}
            <span class="itemhits">({tr}Visits:{/tr} {$result.visits|escape})</span>
        {/if}

        {if !empty($result._external)}
            <span class="label label-info">{tr}External{/tr}</span>
        {else}
            {if !empty($result.parent_object_id)} {tr}in{/tr} {object_link type=$result.parent_object_type id=$result.parent_object_id}{/if}
        {/if}
        </strong>

        <blockquote>
            <p>{$result.highlight}</p>

            {if $prefs.feature_search_show_last_modification eq 'y'}
                <div class="searchdate small">{tr}Last modification:{/tr} {$result.modification_date|tiki_long_datetime}</div>
            {/if}
        </blockquote>
    </li>
    {foreachelse}
            <li>{tr}No pages matched the search criteria{/tr} </li>
    {/foreach}
</ul>
{pagination_links resultset=$results}{/pagination_links}
</div>
