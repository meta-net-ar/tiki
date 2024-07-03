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
        <form method="post" action="{service controller=manager action=clone_and_redact}">
            {foreach item=option from=$options}
                <div class="tiki-form-group row p-2">
                    <label class="col-form-label col-sm-3">
                        {tr}{$option.label}{/tr}
                        <a class="tikihelp text-info" title="{tr}{$option.label}:{/tr} {tr}{$option.help}{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div class="col-sm-9">
                        <input value="{$option.selected}" placeholder="" class="form-control" type="text"
                            name="options[--{$option.name}]{if !empty($option.is_array)}[]{/if}">
                    </div>
                </div>
            {/foreach}

            <div class="tiki-form-group row p-2">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="clone_and_redact"
                        value="{tr}Clone and Redact instance{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
