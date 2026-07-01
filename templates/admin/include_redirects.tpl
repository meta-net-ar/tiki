{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}See also{/tr} <a class="alert-link" href="tiki-admin.php?page=seoprefs">{tr}SEO Preferences{/tr}</a>.
{/remarksbox}
<form class="admin" method="post" action="tiki-admin.php?page=redirects" class="form">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {include file='admin/include_apply_top.tpl'}
    </div>
    <div class="styling-element">
    <fieldset>
        <legend class="h3">{tr}Activate the feature{/tr}</legend>
        {preference name=feature_redirects visible="always"}
        <div class="col-md-8">
            <input type="hidden" name="redirects" id="redirects" value="{$redirects|escape}">
            <div id="single-spa-application:@vue-mf/redirect-builder" class="redirect-builder"></div>
        </div>
        {jq}
            document.addEventListener('redirect-builder:update', function(e){
                $('#redirects').val(JSON.stringify(e.detail));
            });
        {/jq}
    </fieldset>
    </div>
    {include file='admin/include_apply_bottom.tpl'}
</form>
