<form action="tiki-admin.php?page=seo" onreset="return(confirm('{tr}Cancel Edit{/tr}'))" class="admin" method="post">
{ticket}
<div class="row">
    <div class="mb-3 col-lg-12 clearfix">
        {include file='admin/include_apply_top.tpl'}
    </div>
</div>
<div class="styling-element">
    <fieldset>
        <legend class="h3">{tr}Search Engine Crawling{/tr}{help url="SEO"}</legend>

        {preference name=seo_prevent_crawling}
    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
</div>
</form>
