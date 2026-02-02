{strip}
    {if !$content}
        {remarksbox type="error" title="{tr}Content Error{/tr}" close="n"}
            {tr}Please check whether the file you are trying to display exists{/tr}
        {/remarksbox}
    {else}
        <div class="viewtextfile-content-wrapper" style="width: {$width}; height: {$height}; min-width: 480px; min-height: 420px;">
            <div class="viewtextfile-content">
                <pre>{$content|escape:'html'}</pre>
            </div>
        </div>
    {/if}
{/strip}
