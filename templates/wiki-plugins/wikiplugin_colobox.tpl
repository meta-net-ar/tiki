{strip}
    {foreach from=$colorboxFiles.data item=file name=files}
        {capture name=url}{$colorboxUrl}{$file.$colorboxColumn}{/capture}
        {if $file.mediaType eq 'image'}
            <a href="{$smarty.capture.url}{if $colorboxColumn eq "id"}&display{/if}" class="colorbox" data-gallery="shadowbox[colorbox{$iColorbox}]" data-type="image" title="{$file.elTitle|escape}">
                {if $smarty.foreach.files.first or $params.showallthumbs eq 'y'}
                    <img src="{$smarty.capture.url}{if !empty($colorboxThumb)}&{$colorboxThumb}{/if}" alt="{$file.filename}">
                {/if}
            </a>
        {elseif $file.mediaType eq 'video'}
            <a href='#colorbox-inline-file-{$file.$colorboxColumn}' class="colorbox" data-gallery="shadowbox[colorbox{$iColorbox}]"  title="{$file.elTitle|escape}">
                {if $smarty.foreach.files.first or $params.showallthumbs eq 'y'}
                    <span class="icon icon-file far fa-file-video" style="font-size:300%; height: 100%;"></span>
                {/if}
            </a>
            <div style='display:none'>
                <div id='colorbox-inline-file-{$file.$colorboxColumn}'>
                    <video controls style='width : 100%'>
                        <source src="{$base_url}{$file.fileId|sefurl:'display'}" type="{$file.filetype}">
                    </video>
                </div>
            </div>
        {/if}
    {/foreach}
{/strip}
