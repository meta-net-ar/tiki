<div class="highlightable tracker-item-print">
    {if !$viewItemPretty.override}
        <h1>{$tracker_item_main_value|escape}</h1>
    {/if}

    {trackerfields mode=view trackerId=$trackerId itemId=$itemId fields=$fields itemId=$itemId viewItemPretty=$viewItemPretty.value}

    {if not empty($print_comments_html)}
        <h2>{tr}Comments{/tr}</h2>
        {$print_comments_html}
    {/if}

    {if isset($print_page) and $print_page eq 'y' and $prefs.print_original_url_tracker eq 'y'}
        <p>
            {tr}The original document is available at{/tr}
            <a href="{$base_url|escape}{$itemId|sefurl:trackeritem}">{$base_url|escape}{$itemId|sefurl:trackeritem}</a>
        </p>
    {/if}
</div>
