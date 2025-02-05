{strip}
    {if !$content}
        {remarksbox type="error" title="{tr}Content Error{/tr}" close="n"}
            {tr}Please check whether the file you are trying to display exists{/tr}
        {/remarksbox}
    {else}
        <div class="iframe-media-wrapper">
            <div class="iframe-text-container">
                <iframe srcdoc="{$content|escape:'html'}" class="word-page"></iframe>
            </div>
        </div>
    {/if}
{/strip}
