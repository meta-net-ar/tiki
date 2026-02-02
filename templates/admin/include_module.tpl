{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}&quot;Modules&quot; are the items of content at the top &amp; bottom and in the right &amp; left columns of the site.{/tr} {tr}Select{/tr}
    <a class="alert-link" href="tiki-admin_modules.php">{tr}Admin &gt; Modules{/tr}</a> {tr}from the menu to create and edit modules{/tr}.
{/remarksbox}

<form action="tiki-admin.php?page=module" method="post" class="admin">
    {ticket}
    <input type="hidden" name="modulesetup" />

    <div class="t_navbar mb-4 clearfix">
            <a role="button" class="btn btn-link" href="tiki-admin_modules.php" title="{tr}List{/tr}">
                {icon name="list"} {tr}Modules{/tr}
            </a>
            {include file='admin/include_apply_top.tpl'}
    </div>

    <div class="styling-element">

    <fieldset id="Modules">
        <legend class="h3">{tr}{$crumbs[$crumb]->description}{/tr}{help crumb=$crumbs[$crumb]}</legend>

        {preference name=feature_modulecontrols}
        {preference name=user_assigned_modules}
        {preference name=user_flip_modules}
        {preference name=modallgroups}
        {preference name=modseparateanon}
        {preference name=modhideanonadmin}

        <div class="adminoptionbox">
            <fieldset>
                <legend class="h3">{tr}Module zone visibility{/tr}</legend>
                {preference name=module_zones_top}
                {preference name=module_zones_topbar}
                {preference name=module_zones_pagetop}
                {preference name=feature_left_column}
                {preference name=feature_right_column}
                {preference name=module_zones_pagebottom}
                {preference name=module_zones_bottom}
                <hr>
                {preference name=module_file}
                {preference name=module_zone_available_extra}
                {preference name=module_sidebar_toggle_small_screen}
            </fieldset>
        </div>
        <div class="adminoptionbox">
            <fieldset>
                <legend class="h3">{tr}Enable/Disable Modules{/tr}</legend>
                {foreach from=$all_modules item=name}
                    {preference name="module_{$name}"}
                {/foreach}
            </fieldset>
        </div>

        {remarksbox type="tip" title="{tr}Hint{/tr}"}
            {tr}If you lose your login module, use tiki-login_scr.php to be able to login!{/tr}
        {/remarksbox}
    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
    </div>
</form>
