{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="content"}
    <form action="{service controller='edit' action='post_syntax_settings'}" method="post" id="syntax-settings" data-area-id="{$domId}" class="no-ajax">
        <input type="hidden" name="page" value="{$page}">
        <div class="mb-3">
            <label for="syntax-select" class="form-label">{tr}Syntax{/tr}</label>
            <select class="form-select" aria-label="{tr}Tiki or Markdown{/tr}" id="syntax-select">
                <option value="tiki" {if $syntax eq 'tiki'}selected{/if}>{tr}Tiki{/tr}</option>
                <option value="markdown" {if $syntax eq 'markdown'}selected{/if}>{tr}Markdown{/tr}</option>
            </select>
        </div>
        <div class="submit">
            <button type="submit" class="btn btn-primary">{tr}Save{/tr}</button>
        </div>
    </form>
{/block}
