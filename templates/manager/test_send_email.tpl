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
        <form method="post" action="{service controller=manager action=test_send_email}" id="tiki-manager-test-send-mail">
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3">
                    {tr}Email{/tr}
                    <a class="tikihelp text-info" title="{tr}Description:{/tr} {tr}The email address to send the test message{/tr}">
                        {icon name=information}
                    </a>
                    </label>
                <div class="col-sm-9">
                    <input required value="{$inputValues['email']}" class="form-control" id="email" type="email" name="email" placeholder="johndoe@example.org">
                </div>
            </div>
            <div class="tiki-form-group row">
                <label class="col-form-label col-sm-3"></label>
                <div class="col-sm-9">
                    <input class="btn btn-primary" type="submit" name="send" value="{tr}Send mail{/tr}">
                </div>
            </div>
        </form>
    {/if}
{/block}
