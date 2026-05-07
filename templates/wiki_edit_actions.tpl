<div class="actions d-flex justify-content-center">
    <input type="hidden" name="no_bl" value="y">
    {if $pdf_export eq 'y' and $tiki_p_export_pdf eq 'y'}
        <input type="submit" class="wikiaction btn btn-secondary me-2 pdfPreviewBtn" title="{tr}Preview your changes as PDF.{/tr}" name="pdfPreview" value="{tr}PDF Preview{/tr}">
    {/if}
    <input type="submit" class="wikiaction btn btn-secondary me-2 previewBtn" title="{tr}Preview your changes.{/tr}" name="preview" value="{tr}Preview{/tr}" onclick="needToConfirm=false;">
    {if $page|lower neq 'sandbox' or $tiki_p_admin eq 'y'}
        {if ! isset($page_badchars_display) or $prefs.wiki_badchar_prevent neq 'y'}
            {if $translation_mode eq 'y'}
                <input type="hidden" name="source_page" value="{$source_page|escape}">
                <input type="hidden" name="target_page" value="{$target_page|escape}">
                <input type="submit" class="wikiaction tips btn btn-primary me-2" title="{tr}Edit wiki page{/tr}|{tr}Save the page as a partial translation.{/tr}" name="partial_save" value="{tr}Partial Translation{/tr}" onclick="needToConfirm=false">
                <input type="submit" class="wikiaction tips btn btn-primary me-2" title="{tr}Edit wiki page{/tr}|{tr}Save the page as a completed translation.{/tr}" name="save" value="{tr}Complete Translation{/tr}" onclick="needToConfirm=false">
            {else}
                {if $tiki_p_minor eq 'y' and $page|lower ne 'sandbox' and $prefs.wiki_edit_minor neq 'n'}
                    <input type="submit" class="wikiaction tips btn btn-primary me-2" name="minor" title="{tr}Edit wiki page{/tr}|{if !empty($prefs.wiki_watch_minor)}{tr}Save the page, but do not count it as new content to be translated.{/tr}{else}{tr}Save the page, but do not send notifications and do not count it as new content to be translated.{/tr}{/if}" value="{tr}Save Minor Edit{/tr}" onclick="needToConfirm=false;">
                {/if}
                {if $page|lower neq 'sandbox'}
                    <input type="submit" class="wikiaction btn btn-primary me-2" title="{tr}Save the page.{/tr}" name="save" value="{tr}Save{/tr}" onclick="needToConfirm=false;">
                {/if}
            {/if}
        {/if}
        {if $page|lower ne 'sandbox'}
            <input type="button" class="wikiaction btn btn-link cancelEditBtn" title="{tr}Cancel the edit (changes will be lost).{/tr}" name="cancel_edit" value="{tr}Cancel Edit{/tr}" onclick="needToConfirm=false;">
            {jq}
                $(".cancelEditBtn").off('click.cancelEdit').on('click.cancelEdit', function () {
                    const rootPath = '{{$rootPath}}';
                    const parentForm = $(this).closest('form');
                    const actionUrl = rootPath + parentForm.attr('action');
                    const cancelUrl = new URL(actionUrl);
                    cancelUrl.searchParams.set('cancel_edit', 1);
                    window.location.href = cancelUrl.toString();
                });
            {/jq}
        {/if}
    {/if}
</div>
