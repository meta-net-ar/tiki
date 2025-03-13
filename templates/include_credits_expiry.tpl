<h2>{tr}User Credits Expiry Summary (Plans){/tr}</h2>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
        <tr>
            <th scope="col">{tr}User Plan{/tr}</th>
            <th scope="col">{tr}Start of Latest Plan{/tr}</th>
            <th scope="col">{tr}Start of Next Plan{/tr}</th>
            <th scope="col">{tr}Expiry{/tr}</th>
        </tr>
        </thead>
        <tbody>
        {foreach key=id item=data from=$userPlans}
            <tr>
                <td>{$id|escape}</td>
                <td>{if !empty($data.currentbegin)}{$data.currentbegin|escape}{else}-{/if}</td>
                <td>{if !empty($data.nextbegin)}{$data.nextbegin|escape}{else}-{/if}</td>
                <td>{if !empty($data.expiry)}{$data.expiry|escape}{else}-{/if}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
</div>
