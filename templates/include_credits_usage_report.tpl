<h2>{tr}Historical Usage Report{/tr}</h2>
<form method="post" action="{$page}">
    <input type="hidden" name="userfilter" value="{$userfilter|escape}">
    <div class="container">
        <div class="row align-items-start mb-4">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="startDate" class="form-label sr-only">{tr}Start Date{/tr}</label>
                    {html_select_date time=$startDate prefix="startDate_" end_year="-10" day_value_format="%02d" field_order=$prefs.display_field_order}
                </div>
                <div class="form-group">
                    <label for="endDate" class="form-label sr-only">{tr}End Date{/tr}</label>
                    {html_select_date time=$endDate prefix="endDate_" end_year="-10" day_value_format="%02d" field_order=$prefs.display_field_order}
                </div>
            </div>
            <div class="col-md-6 row">
                <div class="col-md-8">
                    <label for="actionType" class="form-label sr-only">{tr}Action Type{/tr}</label>
                    <select class="form-select" name="action_type" id="actionType">
                        <option value="">{tr}all types{/tr}</option>
                        {foreach key=id item=data from=$credit_types}
                            <option value="{$id}" {if $act_type eq '{$id}'}selected="selected"{/if}>{$id|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary w-100" type="submit">{tr}Filter{/tr}</button>
                </div>
            </div>

        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th scope="col">{tr}Type{/tr}</th>
                    <th scope="col">{tr}Usage Date{/tr}</th>
                    <th scope="col" colspan="2">{tr}Amount Used{/tr}</th>
                </tr>
                </thead>
                <tbody>
                {foreach item=con_data from=$consumption_data}
                    <tr>
                        <td>{$con_data.credit_type}</td>
                        <td>{$con_data.usage_date|date_format:"%d-%m-%Y %H:%M:%S"}</td>
                        <td colspan="2">{$con_data.used_amount}</td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>

    </div>
</form>
