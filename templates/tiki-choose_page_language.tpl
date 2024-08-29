{extends $global_extend_layout|default:'layout_edit.tpl'}

{block name=title}
<h1>{tr}Please choose the language for this page:{/tr}</h1>
{/block}

{block name=content}
<div class="card-body" style="overflow: visible;">
    <p>
        <strong>{tr}Page:{/tr} "{$page|escape}"</strong>
    </p>
    <form method="post" action="tiki-editpage.php?page={$page|escape:'url'}" id='editpageform' name='editpageform'>
        {* Repeat all arguments from the page creation request *}
        {query _type='form_input' _keepall='y' need_lang='n'}
        <div class="mb-3 row">
            <div class="col-sm-6">
                <div class="input-group">
                    <select name="lang" class="form-select">
                        <option value="">{tr}Unknown{/tr}</option>
                        {section name=ix loop=$languages}
                            <option value="{$languages[ix].value|escape}"{if $languages[ix].value|escape == $default_lang} selected="selected"{/if}>
                                {$languages[ix].name}
                            </option>
                        {/section}
                    </select>
                    <input type="submit" class="btn btn-primary" name="select_language" value="{tr}Choose language{/tr}" onclick="needToConfirm=false;">
                </div>
            </div>
        </div>
    </form>
</div>
{/block}
