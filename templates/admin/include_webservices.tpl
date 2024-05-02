<form method="post" action="tiki-admin.php?page=webservices">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            <a role="link" class="btn btn-link" href="tiki-admin_webservices.php" title="{tr}List{/tr}">
                {icon name="admin"} {tr}Webservices{/tr}
            </a>
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    <fieldset>
        <legend class="h3">{tr}Activate the feature{/tr}</legend>
        {preference name=feature_webservices visible="always"}
    </fieldset>

    <fieldset>
        <legend class="h3">{tr}Options{/tr}</legend>
        {preference name=webservice_consume_defaultcache}
    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
</form>
