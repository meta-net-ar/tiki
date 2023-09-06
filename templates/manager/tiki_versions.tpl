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
    {else}
        <form method="post" action="{service controller=manager action=tiki_versions}" id="tiki-manager-tiki-versions">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Version Control System{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.vcs}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="vcs" name="vcs">
                        {foreach item=vcs from=$inputValues['vcs']}
                            <option value="{$vcs|escape}" {if $inputValues['selected_vcs'] eq $vcs}selected="selected"{/if}>{$vcs|upper}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="filter" value="{tr}Filter{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
