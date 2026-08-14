{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}To better use this tool, please consult{/tr}<a class='alert-link' target='tikihelp' href='http://doc.tiki.org/SEO-preferences'> SEO preferences</a> {tr} on Tiki's documentation site{/tr}
{/remarksbox}


    {tabset}
        {tab name="{tr}Sitemap{/tr}"}
            {title help="Sitemap" admpage="general&cookietab=3&highlight=sitemap_enable"}{tr}Sitemap{/tr}{/title}
        <form action="tiki-admin.php?page=seoprefs" method="post" class="admin">
            {ticket}
            <input type="hidden" name="modulesetup" />

            {if $prefs.sitemap_enable eq 'y'}
                <div class="adminoptionbox clearfix mb-4">
                    <fieldset class="mb-3 w-100">
                        <legend class="h4 pt-4">{tr}Generation Settings{/tr}</legend>
                        {preference name=sitemap_method}

                        <legend class="h4 pt-4">{tr}Splitting Options{/tr}</legend>
                        {preference name=sitemap_split}
                    </fieldset>
                </div>
            {/if}

            {include file='admin/include_apply_bottom.tpl'}
        </form>

        {if $prefs.sitemap_method eq 'manual'}
        <div class="py-2">
            {button href="tiki-admin.php?page=seoprefs&rebuild=1" _icon_name="sitemap" class="btn btn-primary" _text="{tr}Rebuild sitemap{/tr}"}
        </div>
        {/if}

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

            {if $prefs.sitemap_enable eq 'y'}
                {remarksbox type="info" title="{tr}Automation Status{/tr}" close="n"}
                    {if $prefs.sitemap_method eq 'auto'}
                        <p>{tr}Sitemap generation is set to <strong>automatic</strong>. New content will trigger automatic regeneration.{/tr}</p>
                    {else}
                        <p>{tr}Sitemap generation is set to <strong>manual</strong>. Use the rebuild button above or configure a scheduler after content changes.{/tr}</p>
                        <p>{tr}You can automate using the <a href="https://doc.tiki.org/Scheduler" class="alert-link">Scheduler</a> or command line:{/tr} <code>php console.php sitemap:generate {$base_url}</code></p>
                    {/if}
                {/remarksbox}
            {else}
                {remarksbox type="info" title="{tr}Automate Sitemap generation{/tr}" close="n"}
                    <p>{tr}You can automate the sitemap generation by using the scheduler functionality:{/tr} <a href="https://doc.tiki.org/Scheduler" class="alert-link">{tr}Scheduler{/tr}</a></p>
                    <p>{tr}Or you can use directly the command line:{/tr} <code>php console.php sitemap:generate {$base_url}</code></p>
                {/remarksbox}
            {/if}


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
