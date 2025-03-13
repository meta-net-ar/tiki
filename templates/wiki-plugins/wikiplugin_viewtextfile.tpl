{strip}
    {if !$content}
        {remarksbox type="error" title="{tr}Content Error{/tr}" close="n"}
            {tr}Please check whether the file you are trying to display exists{/tr}
        {/remarksbox}
    {else}
        <div class="viewtextfile-viewer-container" style="width: {$width}; height: {$height};">
            {if !empty($filedetails)}
                {capture name='details'}
                    <table class="table">
                        {foreach $filedetails as $key => $value}
                            <tr>
                                <td class="fw-bold">{$key}</td>
                                <td>{$value}</td>
                            </tr>
                        {/foreach}
                    </table>
                {/capture}

                <a class="viewtextfile-info-button" href="#" onclick="return false;" title="{tr}Information{/tr}" {popup fullhtml="1" text=$smarty.capture.details|replace:'&amp;':'&' left=true} style="cursor:help">
                    {icon name='information' class='' title=''}
                </a>
            {/if}
            <div class="viewtextfile-content-wrapper">
                <div class="viewtextfile-content">
                    {foreach key=ix item=line from=$content}
                        <span>{if $showlinenumbers}<b>{$ix}: </b>{/if}{$line|escape:'html'}</span><br />
                    {/foreach}
                </div>
            </div>
        </div>
    {/if}
{/strip}
