{if $rsstitle and $showtitle}
    <div class="rsstitle mb-3">
        <a target="_blank" href="{$rsstitle.link|escape}">{$rsstitle.title|escape}</a>
    </div>
{/if}
<div class="rsslist{if $ticker} rssticker{/if} d-flex flex-column gap-2">
    {foreach from=$items item=item key=key}
        <div class="rssitem">
            <div class="d-flex gap-2 align-items-center">
                {if $icon}
                    <div style="background-image: url('{$icon}');" class="rss-icon"></div>
                {/if}
                <div class="d-flex flex-column w-100">
                    <div class="d-flex gap-2 align-items-center justify-content-between">
                        <a target="_blank" href="{$item.url|escape}" class="fw-bold text-primary">{$item.title|escape}</a>
                        <div class="d-flex gap-1 fs-6 text-secondary align-items-center fw-lighter">
                            {if $item.author and $showauthor}
                                <span>{icon name='user'} {$item.author|escape}</span>
                            {/if}

                            {if $item.author and $showauthor and $item.publication_date and $showdate}
                            <div class="bg-secondary" style="width: 1px; height: 1em;"></div>
                            {/if}

                            {if $item.publication_date and $showdate}
                                <span>{icon name='calendar_days'} {$item.publication_date|tiki_short_date}</span>
                            {/if}
                        </div>
                    </div>
                    {if $item.description && $showdesc}
                        <div class="rssdescription">
                            {$item.description|escape}
                        </div>
                    {/if}
                    {if $item.labels}
                        <div class="rsslabels">
                            {foreach from=$item.labels item=label}
                            <span class="text-sm badge-pill badge bg-secondary">{$label|escape}</span>
                            {/foreach}
                        </div>
                    {/if}
                </div>
            </div>
            {if $key < count($items) - 1}
                <hr>
            {/if}
        </div>
    {/foreach}
</div>

{if $ticker}
    {jq}
        function rsstick(){
            $('ul.rssticker li').first().slideUp( function () { $(this).appendTo($('ul.rssticker')).slideDown(); });
        }
        setInterval(function(){ rsstick() }, 5000);
    {/jq}
{/if}

<style>
    .rss-icon {
        background-size: cover;
        background-repeat: no-repeat;
        background-position: center;
        width: 3em;
        height: 3em;
        flex-shrink: 0;
    }
</style>
