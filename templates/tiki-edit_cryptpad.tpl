{title help="CryptPad Docs"}{$name}{/title}

{if $missingPackage}
    {remarksbox type=error title="{tr}Missing Package{/tr}" close="n"}
        {tr}To view/edit Office documents Tiki needs the CryptPad package.{/tr}
        {tr}Please contact the Administrator to install it.{/tr}
    {/remarksbox}
{else}
    {if empty($cryptpadBaseUrl)}
        {remarksbox type=warning title="{tr}Configuration required{/tr}" close="n"}
            {tr}Set the preference{/tr} <code>cryptpad_base_url</code> {tr}to your CryptPad instance URL in Admin → Features{/tr}.
        {/remarksbox}
    {/if}
    <div class="cryptpad-editor-controls">
        <a href="#" class="btn btn-secondary cancelButton" role="button" title="{tr}Close and return to gallery{/tr}">
            {icon name='close'} {tr}Close{/tr}
        </a>
    </div>

    <input id="fileId" type="hidden" value="{$fileId}">
    <input name="galleryId" type="hidden" value="{$smarty.request.galleryId}">

    <div class="cryptpad-document-info" style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 4px;">
        <strong>{tr}Document Type:{/tr}</strong> {$documentType|capitalize}<br>
        <strong>{tr}File Format:{/tr}</strong> .{$fileExtension|upper}
    </div>

    <div id="tiki_cryptpad" class="cryptpad-container" style="min-height: 800px; border: 1px solid #ddd; border-radius: 4px;"></div>

{/if}

<style>
.cryptpad-editor-controls {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.cryptpad-container {
    position: relative;
    background: #fff;
}

.cryptpad-document-info {
    font-size: 0.9em;
}
</style>
