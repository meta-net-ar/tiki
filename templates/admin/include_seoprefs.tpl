{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}To better use this tool, please consult{/tr}<a class='alert-link' target='tikihelp' href='http://doc.tiki.org/SEO-preferences'> SEO preferences</a> {tr} on Tiki's documentation site{/tr}
{/remarksbox}
{ticket}

    {tabset}
        {tab name="{tr}Sitemap{/tr}"}
            {title help="Sitemap" admpage="general&cookietab=3&highlight=sitemap_enable"}{tr}Sitemap{/tr}{/title}

            {button href="tiki-admin.php?page=seoprefs&rebuild=1" _icon_name="sitemap" class="btn btn-primary" _text="{tr}Rebuild sitemap{/tr}"}

            <br/>
            {remarksbox type="info" title="{tr}Submit the Sitemap{/tr}" close="n"}
            {if $sitemapAvailable}
                {tr}You can submit the sitemap for processing in all major search engines using the following URL:{/tr}
                <br>
                <br>
                <a href="{$Url}" target="_blank">{$Url}</a>
            {else}
                {tr}The URL that you will need to use for submitting the sitemap will be available after you rebuild the sitemap.{/tr}
            {/if}
            {/remarksbox}
            {remarksbox type="info" title="{tr}Automate Sitemap generation{/tr}" close="n"}
                <p>
                    {tr}You can automate the sitemap generation by using the scheduler functionality:
                        <a href="https://doc.tiki.org/Scheduler" class="alert-link">https://doc.tiki.org/Scheduler</a>
                    {/tr}
                </p>
                <p>
                    {tr}Or you can use directly the command line:{/tr} <code>php console.php sitemap:generate {$base_url}</code>
                </p>
            {/remarksbox}


        {/tab}

        {tab name="{tr}Tag title{/tr}"}
            {remarksbox type="info" title="{tr}Tag Title{/tr}" close="n"}
                <p>{tr}Another practical method to dynamically generate the content of the title tag is here <a href="https://doc.tiki.org/PluginList---Hacks-and-Fun#Set_the_Browser_Page_Title" class="alert-link"> here</a>{/tr}</p>
            {/remarksbox}
            <div class="adminoptionbox clearfix">
                <fieldset class="mb-3 w-100">
                    <legend class="h3">{tr}List of pages and content of the title tag{/tr}</legend>
                    <div class="table-responsive">
                        <table class="table" >
                            <tr>
                                <th>{tr}Name of the page{/tr}</th>
                                <th>{tr}Content Tag Title{/tr}</th>
                                <th>{tr}Edit content Tag{/tr}</th>
                            </tr>
                            {foreach item=pages from=$listPages}
                            <tr>
                                <td>
                                    {$pages.pageName}
                                </td>
                                <td>
                                    {$pages.attribute_title}
                                </td>
                                <td>
                                    <div class="">
                                        <a href="tiki-editpage.php?page={$pages.pageName}#contenttabs_editpage-2">
                                            <input type="submit" class="btn btn-outline-primary btn-sm" value="{tr}Edit{/tr}"/>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            {/foreach}
                        </table>
                    </div>
                </fieldset>
            </div>
        {/tab}
    {/tabset}
