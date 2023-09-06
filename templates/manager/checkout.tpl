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
        <form method="post" action="{service controller=manager action=checkout}" id="tiki-manager-instance-checkout">
            <input value="{$inputValues['instanceId']}" class="form-control" id="instanceId" type="hidden" name="instanceId">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Local folder containing a Git repository{/tr}
                    <a class="tikihelp text-info" title="{tr}Folder:{/tr} {tr}{$help.folder}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required class="form-control" id="folder" type="text" name="folder">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Url of the Git repository{/tr}
                    <a class="tikihelp text-info" title="{tr}URL:{/tr} {tr}{$help.url}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input class="form-control" id="url" type="text" name="url" placeholder="e.g. git@gitlab.com:tikiwiki/tiki.git">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Git branch to checkout{/tr}
                    <a class="tikihelp text-info" title="{tr}Branch:{/tr} {tr}{$help.branch}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required class="form-control" id="branch" type="text" name="branch">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Revision{/tr}
                    <a class="tikihelp text-info" title="{tr}Revision:{/tr} {tr}{$help.revision}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input class="form-control" id="revision" type="text" name="revision">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="edit" value="{tr}Checkout{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
