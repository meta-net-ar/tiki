<div class="accordion" id="{$unique}">
    {foreach from=$headers item=header key=key}
        <div class="accordion-item">
            <h2 class="accordion-header" id="heading{$unique}-{$key}">
                <button class="accordion-button {if $key neq 0} collapsed{/if} text-break" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{$unique}-{$key}" {if $key eq 0} aria-expanded="true" {else} aria-expanded="false" {/if} aria-controls="collapse{$unique}-{$key}" aria-label="{tr}Toggle {$header} section{/tr}">
                    {if (array_key_exists($key, $icons))}<span class="accordion-icon fas fa-{$icons.$key}"></span>{/if}{$header}
                </button>
            </h2>
            <div id="collapse{$unique}-{$key}" class="accordion-collapse collapse {if $key eq 0} show{/if}" aria-labelledby="heading{$unique}-{$key}" data-bs-parent="#{$unique}">
                <div class="accordion-body">
                    {if (array_key_exists($key, $accordioncontent))}
                        {$accordioncontent.$key}
                    {/if}
                </div>
            </div>
        </div>
    {/foreach}
</div>
