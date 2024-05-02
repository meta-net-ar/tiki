{* This is meant to emulate, and take the same parameters (when applicable) as searchresults-plain.tpl  *}
<div>
<ul class="searchresults">
    {foreach item=result from=$results}
    <li>
        <strong>
        {object_link type=$result.object_type id=$result.object_id title=$result.title url=$result.url}

        {if $prefs.feature_search_show_object_type eq 'y'}
            (<span class="objecttype">{tr}{$result.object_type|escape}{/tr}</span>)
        {/if}

        {if $prefs.feature_search_show_pertinence eq 'y' and !empty($result.score)}
            <span class="itemrelevance">({tr}Relevance:{/tr} {$result.score|escape})</span>
        {/if}

        {if $prefs.feature_search_show_visit_count eq 'y' and $result.visits neq null}
            <span class="itemhits">({tr}Visits:{/tr} {$result.visits|escape})</span>
        {/if}

        {if !empty($result.parent_object_id)} {tr}in{/tr} {object_link type=$result.parent_object_type id=$result.parent_object_id}{/if}

        {if !empty($result._external)}<span class="label label-info">{tr}External{/tr}</span>{/if}
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
</div>
