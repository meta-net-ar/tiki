{title help="Shoutbox"}{tr}Shoutbox{/tr}{/title}

{if $tiki_p_admin_shoutbox eq 'y'}
    <div class="t_navbar">
        <a href="tiki-admin_shoutbox_words.php" class="btn btn-link" title="List">
            {icon name="list"} {tr}Banned Words{/tr}
        </a>
    {*    {button href="tiki-admin_shoutbox_words.php" class="btn btn-primary" _text="{tr}Banned Words{/tr}"} *}
    </div>

    <h2>{tr}Change shoutbox general settings{/tr}</h2>
    <form action="tiki-shoutbox.php" method="post">
        <div class="form-check">
            <label class="form-check-label offset-md-3">
                <input class="form-check-input" type="checkbox" name="shoutbox_autolink" value="on"{if $prefs.shoutbox_autolink eq 'y'} checked="checked"{/if}>
                {tr}auto-link urls{/tr}
            </label>
        </div>
        <div class="text-center">
            <input type="submit" class="btn btn-primary btn-sm" name="shoutbox_admin" value="{tr}Save{/tr}">
        </div>
    </form>
{/if}

{if $tiki_p_post_shoutbox eq 'y'}
    <h2>{tr}Post or edit a message{/tr}</h2>
    {if $msg}
        <div class="simplebox highlight">{$msg}</div>
    {/if}
    {js_maxlength textarea=message maxlength=255}
    <form action="tiki-shoutbox.php" method="post" onsubmit="return verifyForm(this);">
        {ticket}
        <input type="hidden" name="msgId" value="{$msgId|escape}">
        <div class="mb-3 row">
            <label class="col-form-label col-md-3" for="message">{tr}Message:{/tr}</label>
            <div class="col-md-9">
                <textarea class="form-control" name="message" id="message">{$message|escape}</textarea>
                {if $prefs.feature_socialnetworks eq 'y' && $user neq ''}
                    {if $prefs.socialnetworks_twitter_client_id neq ''}
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" type="checkbox" name="tweet" id="tweet" value='1'>
                                {tr}Tweet with Twitter{/tr}
                            </label>
                        </div>
                    {/if}
                    {if $prefs.socialnetworks_facebook_application_id neq ''}
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input"  type="checkbox" name="facebook" id="facebook" value='1'>
                                {tr}Post on my Facebook wall{/tr}
                            </label>
                        </div>
                    {/if}
                {/if}
            </div>
        </div>
        {if $prefs.feature_antibot eq 'y' && $user eq ''}
            {include file='antibot.tpl'}
        {/if}
        <div class="text-center">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
        </div>
    </form>
{/if}

<h2>{tr}Messages{/tr}</h2>

{include file='find.tpl'}

{section name=user loop=$channels}
    <div class="shoutboxmsg">
        <b><a href="tiki-user_information.php?view_user={$channels[user].user}">{$channels[user].user}</a></b>, {$channels[user].timestamp|tiki_long_date}, {$channels[user].timestamp|tiki_long_time}

        {if $tiki_p_admin_shoutbox eq 'y' || $channels[user].user == $user}
            <a href="tiki-shoutbox.php?find={$find}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;msgId={$channels[user].msgId}" class="tips text-primary" title=":{tr}Edit{/tr}">
                {icon name='edit'}
            </a>
            <form style="display: inline" action="tiki-shoutbox.php" method="post">
                {ticket}
                <input type="hidden" name="find" value="{$find}">
                <input type="hidden" name="offset" value="{$offset}">
                <input type="hidden" name="sort_mode" value="{$sort_mode}">
                <input type="hidden" name="remove" value="{$channels[user].msgId}">
                <button type="submit" class="btn btn-link px-0 pt-0 tips text-danger" title=":{tr}Remove{/tr}" aria-label="{tr}Remove{/tr}" onclick="confirmPopup()">
                    {icon name='remove'}
                </button>
            </form>
        {/if}
        <br>
        {$channels[user].message}
    </div>
{/section}

{pagination_links count=$pages_count step=$prefs.maxRecords offset=$offset}{/pagination_links}
