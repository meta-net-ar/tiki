{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title|escape}{/title}
{/block}
{block name="content"}
    <form role="form" id="confirm-action" method="post" action="{service controller=managestream action=record}">
        <div class="mb-3 row clearfix">
            <label for="event" class="col-form-label col-md-3">
                {tr}Event{/tr}
            </label>
            <div class="col-md-9">
                <select name="event" class="form-select">
                    {foreach from=$eventTypes item=eventName}
                        <option value="{$eventName|escape}"{if $rule.eventType eq $eventName} selected{/if}>
                            {$eventName|escape}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
        {if $prefs.activity_notifications eq 'y'}
            <div class="mb-3 row clearfix">
                <div class="offset-md-3 col-md-9">
                    <div class="form-check">
                        <input class="form-check-input" id="notification_checkbox" name="is_notification" type="checkbox">
                        <label class="form-check-label" for="notification_checkbox">
                             {tr}Allow Notifications{/tr}
                        </label>
                    </div>
                </div>
            </div>
            <div class="priority-div hidden clearfix">
                <div class="mb-3 row offset-md-4 col-md-8">
                    <label for="priorityinput" class="col-form-label">{tr}Priority{/tr}</label>
                    <select id="priorityinput" name="priority" class="form-select">
                        <option value="low">Low</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="mb-3 row offset-md-4 col-md-8">
                    <label for="userInput" class="col-form-label">{tr}Recipient{/tr}</label>
                    <input id="userInput" name="user" class="form-control" value="user">
                </div>
            </div>
        {/if}
        <div class="mb-3 row clearfix">
            <label for="notes" class="col-form-label col-md-3">
                {tr}Description{/tr}
            </label>
            <div class="col-md-9">
                <textarea name="notes" class="form-control">{$rule.notes|escape}</textarea>
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label for="rule" class="col-form-label col-md-3">
                {tr}Rule{/tr}
            </label>
            <div class="col-md-9">
                <textarea name="rule" class="form-control" rows="3" readonly>{$rule.rule|escape}</textarea>
            </div>
        </div>
        <div class="submit">
            {ticket mode='confirm'}
            <input type="hidden" name="ruleId" value="{$rule.ruleId|escape}"/>
            <input type="submit" class="btn btn-primary" value="{tr}Save{/tr}"/>
        </div>
    </form>
    {jq}
        $("#notification_checkbox").on("change", function(){
        if (this.checked){
        $(".priority-div").removeClass("hidden");
        }else{
        $(".priority-div").addClass("hidden");
        }
        });
    {/jq}
{/block}
