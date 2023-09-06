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
        <form method="post" action="{service controller=manager action=create}" id="tiki-manager-create-instance">
            <div class="tiki-form-group row preference">
                <label class="col-form-label col-sm-3">
                    {tr}Instance type{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}This will allows you to create an instance form an existing tiki installation or a blank instance without actually add a Tiki{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <div class="row">
                    {foreach item=type from=$inputValues['instance_types']}
                        <div class="col-sm-3">
                            <input type="radio" class="form-check-input" id="{$type}" name="instance_type" value="{$type|escape}" {if $inputValues['selected_instance_type'] eq $type}checked{/if}>
                            <label class="form-check-label" for="{$type}">{$type|upper}</label>
                        </div>
                    {/foreach}
                    </div>
                </div>
            </div>
            <div class="tiki-form-group row preference">
                <label class="col-form-label col-sm-3">
                    {tr}Connection type{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.type}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <select class="form-control" id="connection_type" name="connection_type" data-tiki-admin-child-block=".type_childcontainer">
                        {foreach item=type from=$inputValues['connection_types']}
                            <option value="{$type|escape}" {if $inputValues['selected_connection_type'] eq $type}selected="selected"{/if}>{$type|upper}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="adminoptionboxchild type_childcontainer ssh ftp">
                <div class="tiki-form-group row">
                    <label class="col-form-label col-sm-3">
                        {tr}Remote host name{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.host}{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div class="col-sm-9">
                        <input value="{$inputValues['host']}" class="form-control" id="host" type="text" name="host">
                    </div>
                </div>
                <div class="tiki-form-group row">
                    <label class="col-form-label col-sm-3">
                        {tr}Remote port number{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.port}{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div class="col-sm-9">
                        <input value="{$inputValues['port']}" class="form-control" id="port" type="text" name="port">
                    </div>
                </div>
                <div class="tiki-form-group row">
                    <label class="col-form-label col-sm-3">
                        {tr}Remote user{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.user}{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div class="col-sm-9">
                        <input value="{$inputValues['user']}" class="form-control" id="user" type="text" name="user">
                    </div>
                </div>
            </div>
            <div class="adminoptionboxchild type_childcontainer ssh">
                <div class="alert alert-info">
                    {tr}You have to setup SSH access between Tiki Manager and remote machine by copying the SSH key and allowing key-based authentication. Tiki Manager running via Web GUI cannot (yet) ask for password on each command execution. Log in to your web server and execute:{/tr}<br>
                    {include file='manager/command.tpl' command='ssh-copy-id -i '|cat:$sshPublicKey|cat:' -p REMOTE-PORT REMOTE-USER@REMOTE-HOST'}
                </div>
            </div>
            <div class="adminoptionboxchild type_childcontainer ftp">
                <div class="tiki-form-group row">
                    <label class="col-form-label col-sm-3">
                        {tr}Remote password{/tr}
                        <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.pass}{/tr}">
                            {icon name=information}
                        </a>
                    </label>
                    <div class="col-sm-9">
                        <input value="{$inputValues['pass']}" class="form-control" id="pass" type="password" name="pass" autocomplete="new-password">
                    </div>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Instance name{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.name}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['name']}" class="form-control" id="name" type="text" name="name">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Instance URL{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.url}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['url']}" class="form-control" id="url" type="url" name="url" placeholder="example.org">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Email{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.email}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['email']}" class="form-control" id="email" type="email" name="email" placeholder="johndoe@example.org">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Password{/tr}
                    <a class="tikihelp text-info" title="{tr}Description{/tr}|{tr}Password of the admin user{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                   <div class="input-group">
                        <input type="password" value class="form-control" name="tikipassword" id="tikipassword">
                        <span class="input-group-text tiki-pass" id="togglepassword" data-toggle="tooltip" data-placement="bottom" title="{tr}Toggle password visibility{/tr}">
                            <i class="fa fa-eye" id="icon-change"></i>
                        </span>
                        <span class="input-group-text tiki-pass" id="passwordgenerate" data-toggle="tooltip" data-placement="bottom" title="{tr}Generate new password{/tr}">
                            <i class="fa fa-key"></i>
                        </span>
                    </div>
                    <div class="input-group">
                        <input type="checkbox" name="leavepassword" value="yes" checked>
                        <label for="leavepassword">{tr}Leave default password as is.{/tr}</label>
                    </div>
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Instance Webroot{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.webroot}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['webroot']}" class="form-control" id="webroot" type="text" name="webroot" placeholder="/var/www/html">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Temp dir{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.tempdir}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['temp_dir']}" class="form-control" id="tempdir" type="text" name="tempdir">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Tiki branch{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help.branch}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <select class="form-control" id="branch" name="branch">
                        {foreach item=branch from=$inputValues['branches']}
                            <option value="{$branch|escape}" {if $inputValues['selected_branch'] eq $branch}selected="selected"{/if}>{$branch}</option>
                        {/foreach}
                    </select>
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
            {include file="manager/apply_fields.tpl" profiles=$inputValues['profiles'] default_repository=$inputValues['default_repository']}
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Backup User{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['backup-user']}{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_user']}" class="form-control" id="backup_user" type="text" name="backup_user">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Backup Group{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['backup-group']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_group']}" class="form-control" id="backup_group" type="text" name="backup_group">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Backup Permission{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['backup-permission']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['backup_permission']}" placeholder="777" class="form-control" id="backup_permission" type="text" name="backup_permission">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}DB Host{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['db-host']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_host']}" placeholder="localhost" class="form-control" id="db_host" type="text" name="db_host">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}DB User{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['db-user']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_user']}" placeholder="root" class="form-control" id="db_user" type="text" name="db_user">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}DB Password{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['db-pass']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['db_pass']}" placeholder="root" class="form-control" id="db_pass" type="text" name="db_pass">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}DB Prefix{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['db-prefix']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_prefix']}" placeholder="root" class="form-control" id="db_prefix" type="text" name="db_prefix">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}DB Name{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr}|{tr}{$help['db-name']}{/tr}">
                        {icon name=information}
                    </a>
                </label>
                <div class="col-sm-9">
                    <input value="{$inputValues['db_name']}" placeholder="root" class="form-control" id="db_name" type="text" name="db_name">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="create" value="{tr}Create a new instance{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
