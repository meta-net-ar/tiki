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
        <form method="post" action="{service controller=manager action=TemporaryUser}" id="tiki-manager-create-temporaryuser">
            <input required class="form-control" id="instanceId" value="{$instanceId}" type="hidden" name="instanceId">
            <div class="mb-3 row">
                <label class="col-sm-4 col-md-4 col-form-label" for="groups">{tr}Groups (comma-separated){/tr}</label>
                <div class="col-sm-8 col-md-8">
                    <input type="text" class="form-control" name="groups" id="groups">
                    {autocomplete element='#groups' type='groupname'}
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-sm-10 offset-sm-4 col-md-10 offset-md-4">
                <input class="btn btn-primary" type="submit" name="create" value="{tr}Create{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
