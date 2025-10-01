{title}{tr}Invitations list{/tr}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-invite.php" class="btn btn-primary" _type="link" _icon_name="group" _text="{tr}Invite{/tr}"}
    {if $tiki_p_admin eq 'y'}
        {button href="tiki-adminusers.php" class="btn btn-primary" _type="link" _icon_name="user" _text="{tr}Add Users{/tr}"}
    {/if}
</div>

<hr>

<div class="clearfix">
    <form action="tiki-list_invite.php" method="post">
        {if $tiki_p_admin eq 'y'}
            <div class="mb-3 row">
                <label class="col-form-label col-sm-5" for="inviter">{tr}Inviter{/tr}</label>
                <div class="col-sm-7">
                    <input type="text" class="form-control" id="inviter" name="inviter" value="{$inviter|escape}">
                </div>
            </div>
        {/if}

        <div class="mb-3 row">
            <div class="offset-sm-5 col-sm-7">
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" name="only_success" type="checkbox" {if $only_success eq 'y'} checked="checked"{/if}>{tr}Only successful invitations{/tr}
                    </label>
                </div>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="offset-sm-5 col-sm-7">
                <div class="form-check">
                    <label class="form-check-label">
                        <input class="form-check-input" name="only_pending" type="checkbox" {if $only_pending eq 'y'} checked="checked"{/if}>{tr}Only pending invitations{/tr}
                    </label>
                </div>
            </div>
        </div>

        <div class="mb-3 row">
            <div class="col-sm-7 offset-sm-5">
                <input type="submit" class="btn btn-primary btn-sm" name="filter" value="{tr}Filter{/tr}">
            </div>
        </div>
    </form>
</div>

<hr>

{tr}Number of invitations:{/tr} {$count}
{if $count > 0}
    <div class="table-responsive">
        <table class="table">
            <tr>
                {if $tiki_p_admin eq 'y'}
                    <th>{self_link _sort_arg='sort_mode' _sort_field='inviter'}{tr}Inviter{/tr}{/self_link}</th>
                {/if}
                <th>{self_link _sort_arg='sort_mode' _sort_field='ts'}{tr}Date{/tr}{/self_link}</th>
                <th>{self_link _sort_arg='sort_mode' _sort_field='email'}{tr}Email{/tr}{/self_link}</th>
                <th>{self_link _sort_arg='sort_mode' _sort_field='status'}{tr}Status{/tr}{/self_link}</th>
            </tr>

            {foreach item=invited from=$inviteds}
                <tr>
                    {if $tiki_p_admin eq 'y'}
                        <td class="text">{$invited.inviter|userlink}</td>
                    {/if}
                    <td class="date">{$invited.ts|tiki_short_date}</td>
                    <td class="email">{$invited.email|escape}</td>
                    <td class="text">{$invited.used|escape}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{/if}

{pagination_links count=$count step=$max offset=$offset}{/pagination_links}
