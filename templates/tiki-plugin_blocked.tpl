<div class="card text-bg-warning plugin-approval-form" id="{$plugin_fingerprint|escape}">
    <div class="card-header">
        <h1 class="h4 card-title">
        {icon name='error' style="vertical-align:middle"}
        {if $plugin_status eq 'rejected'}
            {tr}Plugin execution was denied{/tr}
        {else}
            {tr}Plugin execution pending approval{/tr}
        {/if}</h1>
    </div>
    <div class="card-body" style="background: var(--bs-body-bg); color: var(--bs-body-color)">
        {if $plugin_status eq 'rejected'}
            <p>{tr}After argument validation by an editor, the execution of this plugin was denied. This plugin will eventually be removed or corrected.{/tr}</p>
        {else}
            <p>{tr}This plugin was recently added or modified. Until an editor of the site validates the parameters, execution will not be possible.{/tr} {if $plugin_details}{tr}You are allowed to:{/tr}{/if}</p>
            {if $plugin_details}
                <ul>
                    <li>{tr}View arguments{/tr}</li>
                    {if $plugin_preview}<li>{tr}Execute the plugin in preview mode (may be dangerous){/tr}</li>{/if}
                    {if $plugin_approve}<li>{tr}Approve the plugin for public execution{/tr}</li>{/if}
                </ul>
            {/if}
            {if $plugin_details}
                {assign var=thisplugin_name value=$plugin_name|escape}
                {assign var=thisplugin_index value=$plugin_index|escape}
                {button href="javascript:void(0)" _onclick="toggle('sec-$thisplugin_name-$thisplugin_index')" _type="info" _class="text-end" _text="{tr}View Details{/tr}"}
                <div id="sec-{$plugin_name|escape}-{$plugin_index|escape}" style="display:none">
                    <div style="margin-top: 1rem"><h2 class="h5">{tr}Details:{/tr} {$plugin_name|upper|escape}</h2></div>
                    {if $plugin_args|@count > 0}
                        <table>
                            {foreach from=$plugin_args key=arg item=val}
                            <tr>
                                <th>{$arg|escape}</th>
                                <td>{$val|escape}</td>
                            </tr>
                            {/foreach}
                        </table>
                    {else}
                        <p>{tr}This plugin does not contain any arguments.{/tr}</p>
                    {/if}

                    {if $plugin_body}
                        <div class="card border-info mb-3">
                            <div class="card-header text-bg-info">
                                <h3 class="card-title h5" id="body">{tr}Body{/tr}</h3>
                            </div>
                            <div class="card-body mb-3">
                                <textarea class="form-control w-100" rows="10" aria-labelledby="body">{$plugin_body}</textarea>
                            </div>
                        </div>
                    {else}
                        <p>{tr}This plugin's body is empty.{/tr}</p>
                    {/if}
                    <form method="post" action="{$smarty.server.REQUEST_URI|escape}">
                            <input type="hidden" name="plugin_fingerprint" value="{$plugin_fingerprint|escape}">
                            {if $plugin_preview}
                                <input type="submit" class="btn btn-info btn-sm" name="plugin_preview" value="{tr}Preview{/tr}">
                            {/if}
                            {if $plugin_approve}
                                <input type="submit" class="btn btn-primary btn-sm" name="plugin_accept" value="{tr}Approve{/tr}">
                                <input type="submit" class="btn btn-warning btn-sm" name="plugin_reject" value="{tr}Reject{/tr}">
                            {/if}
                    </form>
                </div>
            {/if}
        {/if}
    </div>
</div>
{* add a little js to scroll the first approval form into view *}
{if $plugin_approve}{jq}$(".plugin-approval-form")[0].scrollIntoView({behavior: "smooth", block: "end"});{/jq}{/if}
