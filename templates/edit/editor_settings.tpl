{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <form action="{service controller='edit' action='post_editor_settings'}" method="post" id="editor-settings" data-area-id="{$domId}" class="no-ajax">
        <input type="hidden" name="page" value="{$page}">
        {if $prefs.feature_wysiwyg eq 'y' and $prefs.wysiwyg_optional eq 'y'}
            <div class="mb-3">
                <label for="editor-select" class="form-label">{tr}Editor Type{/tr}</label>
                <select class="form-select" aria-label="{tr}Plain or WYSIWYG{/tr}" id="editor-select">
                    <option value="plain" {if $type neq 'wysiwyg'}selected{/if}>{tr}Plain{/tr}</option>
                    <option value="wysiwyg" {if $type eq 'wysiwyg'}selected{/if}>{tr}WYSIWYG{/tr}</option>
                </select>
            </div>
        {/if}
        {if $prefs.markdown_enabled eq 'y'}
            <div class="mb-3">
                <label for="syntax-select" class="form-label">{tr}Syntax{/tr}</label>
                <select class="form-select" aria-label="{tr}Tiki or Markdown{/tr}" id="syntax-select">
                    <option value="tiki" {if $syntax eq 'tiki'}selected{/if}>{tr}Tiki{/tr}</option>
                    <option value="markdown" {if $syntax eq 'markdown'}selected{/if}>{tr}Markdown{/tr}</option>
                </select>
            </div>
        {/if}
        <div class="submit">
            <button type="submit" class="btn btn-primary">{tr}Save{/tr}</button>
        </div>
    </form>
{/block}
