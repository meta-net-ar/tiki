{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    {if not empty($info)}
        <div class="rounded bg-dark text-light p-3">{$info|nl2br}</div>
    {else}
        <form method="post" action="{service controller=manager action=verify}" id="tiki-manager-verify-instance">
            <input required value="{$inputValues['instance']}" class="form-control" id="instance" type="hidden" name="instance">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Update From{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}|{tr}Action related to how checksums are performed. Accepted values - current, source, skip{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="update_from" name="update_from">
                        {foreach item=from from=$inputValues['update_from']}
                            <option value="{$from|escape}">{$from|upper}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="verify" value="{tr}Verify instance{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
