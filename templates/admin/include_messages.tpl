<form action="tiki-admin.php?page=messages" method="post" name="messages">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    <fieldset>
        <legend class="h3">{tr}Activate the feature{/tr}</legend>
        {preference name=feature_messages visible="always"}
    </fieldset>

    <fieldset>
        <legend class="h3">{tr}Settings{/tr}</legend>

        {preference name=allowmsg_by_default}
        {preference name=allowmsg_is_optional}
        {preference name=messu_mailbox_size}
        {preference name=messu_archive_size}
        {preference name=messu_sent_size}
        {preference name=user_selector_realnames_messu}
        {preference name=messu_truncate_internal_message}

    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
</form>
