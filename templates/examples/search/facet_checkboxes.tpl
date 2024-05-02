{* This is intended as an example of how to present Elasticsearch facets, also known now as aggregations *}
{if not empty($facets)}
    <pre style="display: none;">{$facets|var_dump}</pre>
    {foreach $facets as $facet}
        {if count($facet.options) gt 0}
            <div class="margin-bottom-sm">
                <label class="h3">{$facet.label|replace:' (Tree)':''|tr_if|escape}</label>
                <ul data-for="#{$facet.name}" data-join="{$facet.operator|escape}">
                    {foreach from=$facet.options key=value item=label}
                        <li>
                            <div class="form-check">
                                <input type="checkbox" value="{$value|escape}" class="form-check-input" id="id_{$value|escape}">
                                <label class="form-check-label" for="id_{$value|escape}">{$label|escape}</label>
                            </div>
                        </li>
                    {/foreach}
                </ul>
            </div>
        {/if}
    {/foreach}
{/if}
