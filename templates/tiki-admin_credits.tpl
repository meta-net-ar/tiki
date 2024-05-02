{if isset($msg)}{$msg|escape}{/if}

{title help="Credits"}{tr}Manage Credits{/tr}{/title}

<form method="get" action="tiki-admin_credits.php" class="row g-3 mb-4 align-items-center">
    <div class="col-auto"><label class="form-label" for="userfilter">{tr}Username:{/tr}</label></div>
    <div class="col-auto"><input class="form-control" type="text" name="userfilter" id="userfilter" value="{$userfilter|escape}"></div>
    <div class="col-auto"><input  class="btn btn-info" type="submit" value="{tr}Search{/tr}"></div>
</form>

{if $new_month}{$new_month}{/if}

{if $editing}
<form method="post" action="tiki-admin_credits.php">
    <div class="table-responsive">
        <table class="table">
            <tr>
                <td></td>
                <th>{tr}Type{/tr}</th>
                <th>{tr}Creation Date{/tr}<br>{tr}(YYYY-MM-DD HH:MM:SS){/tr}</th>
                <th>{tr}Expiration Date{/tr}<br>{tr}(YYYY-MM-DD HH:MM:SS){/tr}</th>
                <th>{tr}Used{/tr}<br>{tr}(level credits always 0){/tr}</th>
                <th>{tr}Total{/tr}</th>
            </tr>
            {foreach key=id item=data from=$credits}
            <tr>
                <td>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delete[]" value="{$id|escape}">
                    </div>
                </td>
                <td><input class="form-control" type="text" name="credits[{$id|escape}][credit_type]" aria-label="{tr}Type{/tr}" value="{$data.credit_type|escape}" readonly="readonly"></td>
                <td><input class="form-control" type="text" name="credits[{$id|escape}][creation_date]" aria-label="{tr}Creation Date{/tr}" value="{$data.creation_date|escape}"></td>
                <td><input class="form-control" type="text" name="credits[{$id|escape}][expiration_date]" aria-label="{tr}Expiration Date{/tr}" value="{$data.expiration_date|escape}"></td>
                <td><input class="form-control" type="text" name="credits[{$id|escape}][used_amount]" aria-label="{tr}Used{/tr}" value="{$data.used_amount|escape}"></td>
                <td><input class="form-control" type="text" name="credits[{$id|escape}][total_amount]" aria-label="{tr}Total{/tr}" value="{$data.total_amount|escape}"></td>
            </tr>
            {/foreach}
            <tr>
                <td><strong>{tr}New{/tr}</strong></td>
                <td>
                    <select name="credit_type" class="form-control">
                        {foreach key=id item=data from=$credit_types}
                        <option value="{$id}">{$id|escape}</option>
                        {/foreach}
                    </select>
                </td>
                <td><input class="form-control" type="text" name="creation_date" aria-label="{tr}Creation Date{/tr}" value="" size="20"></td>
                <td><input class="form-control" type="text" name="expiration_date" aria-label="{tr}Expiration Date{/tr}" value="" size="20"></td>
                <td><input class="form-control" type="text" name="used_amount" aria-label="{tr}Used{/tr}" value="0" size="6" readonly="readonly"></td>
                <td><input class="form-control" type="text" name="total_amount" aria-label="{tr}Total{/tr}" value="" size="6"></td>
            </tr>
            <tr>
                <td colspan="5"><input class="btn btn-primary" type="submit" name="save" value="{tr}Save{/tr}" style="display:none;"><input class="btn btn-danger" type="submit" name="confirm" value="{tr}Delete Checked{/tr}"></td>
                <td colspan="1"><input class="btn btn-primary" type="submit" name="save" value="{tr}Save{/tr}"><input type="hidden" name="userfilter" value="{$userfilter|escape}"></td>
            </tr>
        </table>
    </div>
</form>

{include file='include_credits_expiry.tpl' userPlans=$userPlans}

<h2>{tr}Use User Credits{/tr}</h2>
<form method="post" action="tiki-admin_credits.php">
    <label class="form-label" for="use_credit_type">{tr}Use:{/tr}</label>
    <select class="form-select" name="use_credit_type" id="use_credit_type">
        {foreach key=id item=data from=$credit_types}
            <option value="{$id}">{$id|escape}</option>
        {/foreach}
    </select>
    <br>
    <label class="form-label" for="use_credit_amount">{tr}Amount:{/tr}</label> <input class="form-control" type="text" name="use_credit_amount" id="use_credit_amount" value="0" size="8">
    <input type="hidden" name="userfilter" aria-label="{tr}Amount{/tr}" value="{$userfilter|escape}">
    <input class="btn btn-primary" type="submit" name="use_credit" value="{tr}Use{/tr}">
</form>

<h2>{tr}Restore User Level Credits{/tr}</h2>
<form method="post" action="tiki-admin_credits.php">
    <label class="form-label" for="restore_credit_type">{tr}Restore:{/tr}</label>
    <select class="form-select" name="restore_credit_type" id="restore_credit_type">
        {foreach key=id item=data from=$static_credit_types}
            <option value="{$id}">{$id|escape}</option>
        {/foreach}
    </select>
    <br>
    <label class="form-label" for="restore_credit_amount">{tr}Amount:{/tr}</label> <input class="form-control" type="text" name="restore_credit_amount" id="restore_credit_amount" value="0" size="8">
    <input type="hidden" name="userfilter" value="{$userfilter|escape}">
    <input type="submit" name="restore_credit" value="{tr}Restore{/tr}">
</form>

{include file='include_credits_usage_report.tpl' userfilter=$userfilter consumption_data=$consumption_data credit_types=$credit_types startDate=$startDate endDate=$endDate page='tiki-admin_credits.php'}
{else}
    {tr}No such user{/tr}
{/if}
<hr>

<h1>{tr}Manage Credit Types{/tr}</h1>
<form method="post" action="tiki-admin_credits.php">
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <tr>
                <td></td>
                <th>{tr}Type{/tr}</th>
                <th>{tr}Display Text{/tr}</th>
                <th>{tr}Unit Text{/tr}</th>
                <th>{tr}Is Static Level Credit{/tr}</th>
                <th>{tr}Display Bar Length Scaling Divisor{/tr}</th>
            </tr>
            {foreach key=id item=data from=$credit_types}
            <tr>
                <td>&nbsp;</td>
                <td><input class="form-control" type="text" name="credit_types[{$id|escape}][credit_type]" aria-label="{tr}Type{/tr}" value="{$data.credit_type|escape}" size="8" readonly="readonly"></td>
                <td><input class="form-control" type="text" name="credit_types[{$id|escape}][display_text]" aria-label="{tr}Display Text{/tr}" value="{$data.display_text|escape}" size="8"></td>
                <td><input class="form-control" type="text" name="credit_types[{$id|escape}][unit_text]" aria-label="{tr}Unit Text{/tr}" value="{$data.unit_text|escape}" size="8"></td>
                <td><select class="form-select" name="credit_types[{$id|escape}][is_static_level]" aria-label="{tr}Is Static Level Credit{/tr}">
                <option value='n'>{tr}No{/tr}</option>
                <option value='y' {if $data.is_static_level == 'y'}selected="selected"{/if}>{tr}Yes{/tr}</option>
                </select>
                <td><input class="form-control" type="text" name="credit_types[{$id|escape}][scaling_divisor]" aria-label="scaling_divisor" value="{$data.scaling_divisor|escape}" size="6"></td>
            </tr>
            {/foreach}
            <tr>
                <td><strong>{tr}New{/tr}</strong></td>
                <td><input class="form-control" type="text" name="new_credit_type" aria-label="{tr}Type{/tr}" value="" size="8"></td>
                <td><input class="form-control" type="text" name="display_text" aria-label="{tr}Display Text{/tr}" value="" size="8"></td>
                <td><input class="form-control" type="text" name="unit_text" aria-label="{tr}Unit Text{/tr}" value="" size="8"></td>
                <td>
                    <select class="form-select" name="is_static_level" aria-label="{tr}Is Static Level Credit{/tr}">
                    <option value='n'>{tr}No{/tr}</option>
                    <option value='y'>{tr}Yes{/tr}</option>
                    </select>
                </td>
                <td><input class="form-control" type="text" name="scaling_divisor" aria-label="{tr}Scaling Divisor{/tr} value="1" size="6"></td>
            </tr>
            <tr>
                <td colspan="6"><input class="btn btn-primary" type="submit" name="update_types" value="{tr}Save{/tr}"><input type="hidden" name="userfilter" value="{$userfilter|escape}"></td>
            </tr>
        </table>
    </div>
</form>

<h2>{tr}Purge Expired and Used Credits (All Users){/tr}</h2>
<form method="post" action="tiki-admin_credits.php">
    <input class="btn btn-primary" type="submit" name="purge_credits" value="{tr}Purge{/tr}">
</form>
