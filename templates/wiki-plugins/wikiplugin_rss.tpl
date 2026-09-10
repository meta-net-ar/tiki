{if $rsstitle and $showtitle}
    <div class="rsstitle mb-3">
        <a target="_blank" href="{$rsstitle.link|escape}">{$rsstitle.title|escape}</a>
    </div>
{/if}
{if $layout eq 'cards'}
<div class="rsslist rss-cards articles-grid">
    {foreach from=$items item=item}
        <article class="clearfix article wikiplugin_articles rss-news-card">
            {if $showimage and $item.image}
                <a class="rss-card-image-link" target="_blank" href="{$item.url|escape}" aria-hidden="true" tabindex="-1">
                    <img class="article-image" src="{$item.image|escape}" alt="" loading="lazy" decoding="async"{if !empty($item.fallback_image)} onerror="this.onerror=null;this.src='{$item.fallback_image|escape:'javascript'}';"{/if}>
                </a>
            {/if}
            <header class="articletitle mt-0 mx-0 mb-1">
                <h2><a class="stretched-link" target="_blank" href="{$item.url|escape}">{$item.title|escape}</a></h2>
                {if ($item.author and $showauthor) or ($item.publication_date and $showdate)}
                    <span class="titleb">
                        {if $item.author and $showauthor}<span class="author">{icon name='user'} {$item.author|escape}</span>{/if}
                        {if $item.publication_date and $showdate}<span class="rss-date">{icon name='calendar-alt' istyle='opacity:.55'} {$item.publication_date|tiki_short_date}</span>{/if}
                    </span>
                {/if}
            </header>
            {if $item.description && $showdesc}<div class="articleheadingtext">{$item.description|escape}</div>{/if}
            {if !empty($item.labels)}
                <div class="rsslabels">
                    {foreach from=$item.labels item=label}
                    <span class="text-sm badge-pill badge bg-secondary">{$label|escape}</span>
                    {/foreach}
                </div>
            {/if}
        </article>
    {/foreach}
</div>
{else}
<div class="rsslist rss-lines{if $ticker} rssticker{/if} d-flex flex-column gap-2">
    {foreach from=$items item=item key=key}
        <div class="rssitem">
            <div class="d-flex gap-3 align-items-center">
                {if $showimage and $item.image}
                    <a class="rss-item-image" target="_blank" href="{$item.url|escape}" aria-hidden="true" tabindex="-1">
                        <img src="{$item.image|escape}" alt="" loading="lazy" decoding="async"{if !empty($item.fallback_image)} onerror="this.onerror=null;this.src='{$item.fallback_image|escape:'javascript'}';"{/if}>
                    </a>
                {/if}
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
                                <span class="rss-date">{icon name='calendar-alt' istyle='opacity:.55'} {$item.publication_date|tiki_short_date}</span>
                            {/if}
                        </div>
                    </div>
                    {if $item.description && $showdesc}
                        <div class="rssdescription">
                            {$item.description|escape}
                        </div>
                    {/if}
                    {if !empty($item.labels)}
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
{/if}

{if $ticker}
    {jq}
        function rsstick(){
            $('ul.rssticker li').first().slideUp( function () { $(this).appendTo($('ul.rssticker')).slideDown(); });
        }
        setInterval(function(){ rsstick() }, 5000);
    {/jq}
{/if}
