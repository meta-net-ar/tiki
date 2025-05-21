{* changes 24.01.2021 by bob_romoxi, updated 15.05.2025 for height control *}
{* if for controls and pagination *}
{if $carousel and not empty($carousel.id)}{$containerId = $carousel.id}{else}{$containerId = 'wp_list_carousel'}{/if}
{if $carousel and not empty($carousel.mode)}{$mode = $carousel.mode}{else}{$mode = ''}{/if}

{* Determine height behavior *}
{if $carousel and not empty($carousel.height)}{$heightMode = $carousel.height}{else}{$heightMode = 'tallest'}{/if}

<style>
    #{$containerId} .carousel-inner {
        display: flex;
        align-items: center;
        {if $heightMode eq 'tallest'}
            /* Height set by JS */
        {elseif $heightMode eq 'variable'}
            height: auto;
        {else}  
            height: {$heightMode}px;
        {/if}
    }
    #{$containerId} .carousel-item p {
        margin: 0;
        padding: 0;
    }
</style>

<div id="{$containerId}" class="carousel slide {$mode}" data-bs-ride="carousel"
        {if $carousel and $carousel.interval} data-bs-interval="{$carousel.interval}"{/if}
        {if $carousel and isset($carousel.pause)} data-pause="{$carousel.pause}"{/if}
        {if $carousel and isset($carousel.wrap)} data-wrap="{$carousel.wrap}"{/if}
>
    {if $carousel and (empty($carousel.indicators) or $carousel.indicators neq 'n')}
        {* Indicators *}
        <ol class="carousel-indicators">
            {foreach from=$results item=row}
                <li data-bs-target="#{$containerId}" data-bs-slide-to="{$row@index}"{if $row@index eq 0} class="active"{/if}></li>
            {/foreach}
        </ol>
    {/if}

    {* Wrapper for slides *}
    <div class="carousel-inner">
        {foreach from=$results item=row}
            <div class="carousel-item{if $row@index eq 0} active{/if}">
                {if $body and $body.field}
                    {if not empty($body.mode) and $body.mode eq 'raw'}
                        {$row[$body.field]}
                    {else}
                        {$row[$body.field]|escape}
                    {/if}
                {/if}

                <div class="carousel-caption d-none d-md-block">
                    {if $caption and $caption.field}
                        {if not empty($caption.mode) and $caption.mode eq 'raw'}
                            {$row[$caption.field]}
                        {else}
                            {$row[$caption.field]|escape}
                        {/if}
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>

    {* Controls *}
    {if $carousel and not empty($carousel.controls) and $carousel.controls neq 'n'}
        <a class="carousel-control-prev" href="#{$containerId}" role="button" data-bs-slide="prev">
            {icon name='chevron-left'}
            <span class="sr-only">Previous</span>
        </a>
        <a class="carousel-control-next" href="#{$containerId}" role="button" data-bs-slide="next">
            {icon name='chevron-right'}
            <span class="sr-only">Next</span>
        </a>
    {/if}

</div>
{if not empty($carousel.pagination) and $carousel.pagination neq 'n'}
    {pagination_links resultset=$results}{/pagination_links}
{/if}

{if $heightMode eq 'tallest'}
    {jq}
        $(document).ready(function() {
            const $carousel = $("#{{$containerId}}");
            const $images = $carousel.find('.carousel-item img');
            let maxHeight = 0;
            let loadedCount = 0;

            const checkAllLoadedAndSetHeight = function() {
                loadedCount++;
                if (loadedCount === $images.length) {
                    $images.each(function() {
                        maxHeight = Math.max(maxHeight, $(this).height());
                    });
                    $carousel.find('.carousel-inner').css('height', maxHeight + 'px');
                }
            };

            $images.each(function() {
                const $img = $(this);
                if (this.complete) {
                    checkAllLoadedAndSetHeight();
                } else {
                    $img.on('load', checkAllLoadedAndSetHeight)
                        .on('error', function() {
                            console.error('Image failed to load:', $img.attr('src'));
                            checkAllLoadedAndSetHeight();
                        });
                }
            });
        });
    {/jq}
{/if}
