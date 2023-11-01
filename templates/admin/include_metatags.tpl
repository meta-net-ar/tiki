{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}See also{/tr} <a class="alert-link" href="tiki-admin.php?page=sefurl">{tr}Search Engine Friendly URLs{/tr}</a>. {tr}Also{/tr} <a target="_blank" href="http://en.wikipedia.org/wiki/Geotagging#HTML_pages">{tr}here{/tr}</a> {tr}for more information on geo tagging.{/tr}
{/remarksbox}

<form action="tiki-admin.php?page=metatags" method="post" class="admin">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {tabset name="admin_metatags"}
        {tab name="{tr}Meta tags{/tr}"}
            <fieldset>
                <legend>{tr}General{/tr}</legend>
                    <div class="adminoptionbox">
                        {preference name=metatag_keywords}
                        {preference name=metatag_freetags}
                        {preference name=metatag_threadtitle}
                        {preference name=metatag_description}
                        {preference name=metatag_pagedesc}
                        {preference name=metatag_robotscustom}
                        {preference name=metatag_author}
                    </div>
            </fieldset>

            <fieldset>
                <legend>{tr}Twitter{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_twitter_site_name}
                    {preference name=socialnetworks_twitter_site_image}
                </div>
            </fieldset>
            <fieldset>
                <legend>{tr}Facebook{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_facebook_site_name}
                    {preference name=socialnetworks_facebook_site_image}
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}Geo meta tags{/tr}"}
            <br>

            {preference name=metatag_geoposition}
            {preference name=metatag_georegion}
            {preference name=metatag_geoplacename}
        {/tab}
        {tab name="{tr}Robots{/tr}"}
            <br>
            {* Need to show site_metatag_robots as real metatags are overridden at runtime *}

            {preference name=metatag_robots}
            {preference name=metatag_revisitafter}
            {preference name=feature_robots_allow}
            {preference name=feature_robots_disallow}

            {remarksbox type="info" title="{tr}Regenerate robots.txt file{/tr}" close="n"}
            
                {tr}you have two possibilities, firstly you add just your new link to an existinf link in robots.txt or you remove all content of files and you add just your new link{/tr}
                <br>
                {button href="tiki-admin.php?page=metatags&regenerate=1" class="btn btn-primary" _text="{tr}Regenerate with old content{/tr}"}
                {button href="tiki-admin.php?page=metatags&regenerate=deleteOld" class="btn btn-danger" _text="{tr}Regenerate without old content{/tr}"}
                <br>
                {tr} Note: when you regenerate without the old content, it will be not possible, on this interface, to revert your action {/tr}

            {/remarksbox}
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
