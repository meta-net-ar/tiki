{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
{if not empty($info)}
    <div class="rounded bg-dark text-light p-3">{$info|nl2br}</div>
    <a href="{service controller=manager_patch action=index instanceId=$instanceId}" class="btn btn-primary mt-3 float-end">{tr}List patches{/tr}</a>
{else}
    <form method="post" action="{service controller=manager_patch action=apply }" class="ajax-reuse-modal">
        <input type="hidden" name="instanceId" value="{$instance->id}">
        {foreach item=option from=$options}
            <div class="tiki-form-group row p-2">
                <label class="col-form-label col-sm-3">
                    {tr}{$option.label}{/tr}
                    <a class="tikihelp text-info" title="{tr}{$option.label}:{/tr} {tr}{$option.help}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    {if $option.type eq 'checkbox'}
                        <input value="1" class="form-check-input" type="checkbox" name="options[--{$option.name}]{if !empty($option.is_array)}[]{/if}" {if $option.selected}checked="checked"{/if}>
                    {else}
                        <input value="{$option.selected}" placeholder="" class="form-control" type="text" name="options[--{$option.name}]{if !empty($option.is_array)}[]{/if}" {if $option.required}required{/if}>
                    {/if}
                </div>
            </div>
        {/foreach}
        <div class="submit">
            <input class="btn btn-primary" type="submit" name="apply" value="{tr}Apply patch{/tr}">
        </div>
    </form>
{/if}
{/block}
