{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    {include file='manager/nav.tpl'}
{/block}

{block name="content"}
    <form method="post" action="{service controller=manager action=virtualmin_create}" id="virtualmin-create-form">
        <div class="tiki-form-group row preference">
            <label class="col-form-label col-sm-3">
                {tr}Virtualmin server{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}Choose one of the defined Content Authentication Sources. If you don't see the source in this list, you can define it via Admin -> DSN/Content Authentication.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <select class="form-control" name="source" required>
                    <option value=""></option>
                    {foreach item=url key=id from=$sources}
                        <option value="{$id}" {if $input.source eq $id}selected{/if}>{$url}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">
                {tr}PHP Version{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}Choose one of the available PHP versions on the server.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <select class="form-control" name="php_version" required>
                    <option value=""></option>
                </select>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">
                {tr}Tiki branch{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.branch}{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <select class="form-control" name="branch" required>
                    <option value=""></option>
                    {foreach item=branch from=$branches}
                        <option value="{$branch|escape}" {if $input.branch eq $branch}selected{/if}>{$branch}</option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">
                {tr}Instance name{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.name}{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <input type="text" name="name" value="{$input.name}" class="form-control" required>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">
                {tr}Domain name{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}This domain will be created on the target virtualmin server under one of the existing domains managed there. You can specify either a top-level server or a sub-server here - e.g. subdomain.maindomain.com{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <input type="text" name="domain" value="{$input.domain}" class="form-control" required>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">{tr}Apply a profile{/tr}</label>
            <div class="col-sm-9">
                <select class="form-control" name="apply">
                    <option value="No">{tr}No (Default){/tr}</option>
                    <option value="Yes">{tr}Yes{/tr}</option>
                </select>
            </div>
        </div>
        {include file="manager/apply_fields.tpl"}
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3">
                {tr}Email{/tr}
                <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.email}{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-9">
                <input type="email" name="email" value="{$input.email}" class="form-control" placeholder="johndoe@example.org" required>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-form-label col-sm-3"></label>
            <div class="col-sm-9">
                <input class="btn btn-primary" type="submit" name="create" value="{tr}Create a new instance{/tr}">
            </div>
        </div>
    </form>
{/block}
