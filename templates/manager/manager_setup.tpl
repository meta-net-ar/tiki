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
        <form method="post" action="{service controller=manager action=$inputValues['action']}" id="tiki-manager-{$inputValues['event']}">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Time for {$inputValues['event']}{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.time}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="" class="form-control" id="time" type="time" name="time">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {if $inputValues['action'] == 'manager_backup'}
                        {tr}Instances to be excluded{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.exclude}{/tr}">
                            {icon name=information}
                        </a>
                    {else}
                        {tr}Instances to be updated{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.instances}{/tr}">
                            {icon name=information}
                        </a>
                    {/if}
                </label>
                <div class="col-sm-9">
                    <select multiple class="form-control" id="instance" name="instance[]" data-tiki-admin-child-block=".type_childcontainer" {if $inputValues['action'] == 'manager_update'}required{/if}>
                        {foreach item=instance from=$inputValues['instances']}
                            <option value="{$instance->id}">{$instance->name}</option>
                        {/foreach}
                    </select>
                    <div class="form-text">{tr}Use Ctrl+Click or Command+Click to select multiple instances{/tr}</div>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Email{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help.email}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input value="{$inputValues['email']}" class="form-control" id="name" type="text" name="email" placeholder="johndoe@example.org">
                    <div class="form-text">{tr}You can add several email addresses by separating them with commas.{/tr}</div>
                </div>
            </div>
            {if  $inputValues['action'] == 'manager_backup' }
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Max number of backups to keep{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}{$help['max-backups']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input value="{$inputValues['number_backups_to_keep']}" class="form-control" id="number_backups_to_keep" type="text" name="number_backups_to_keep" placeholder="100">
                </div>
            </div>
            {/if}
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="{$inputValues['event']}" value="{tr}Creat Cron Job{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
