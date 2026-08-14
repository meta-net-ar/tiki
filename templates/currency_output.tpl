{strip}
<div style="display:inline" id="currency_output_{$id}" class="currency_output">
{if $prepend}
    <span class="formunit">{$prepend|escape}</span>
{/if}
{if $currency}
    {$currency=$currency}
{elseif empty($defaultCurrency)}
    {$currency='USD'}
{else}
    {$currency=$defaultCurrency}
{/if}
{if empty($symbol)}
    {$part1a='%(!#10n'}
    {$part1b='%(#10n'}
{else}
    {$part1a='%(!#10'}
    {$part1b='%(#10'}
{/if}
{if (isset($reloff) and $reloff gt 0) and ($allSymbol ne 1)}
    {$format=$part1a|cat:$symbol}
    {$amount|money_format:$locale:$currency:$format:0}
{else}
    {$format=$part1b|cat:$symbol}
    {$amount|money_format:$locale:$currency:$format:1}
{/if}
{if $append}
    <span class="formunit">{$append|escape}</span>
{/if}
</div>
{if $conversions}
    <div class="d-none currency_output_{$id}" style="position:absolute; z-index: 1000;">
        <div class="modal-content">
            <div class="modal-body">
    {foreach from=$conversions key=currency item=amount}
        {if (isset($reloff) and $reloff gt 0) and ($allSymbol ne 1)}
            {$format=$part1a|cat:$symbol}
            {$amount|money_format:$locale:$currency:$format:0}
        {else}
            {$format=$part1b|cat:$symbol}
            {$amount|money_format:$locale:$currency:$format:1}
        {/if}
        <br>
    {/foreach}
            </div>
        </div>
    </div>
{/if}
{/strip}
