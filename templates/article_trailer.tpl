{if !isset($preview)}
    <div class="clearfix articletrailer">
        {if $pdf_export eq 'y'}
            <div class="wikiinfo float-left" id="pdfinfo" style="display:none">
                <div class="alert alert-info" style="width:500px"><h4><span class="icon icon-information fas fa-info-circle fa-fw "></span>&nbsp;<span class="rboxtitle">{tr}Please wait{/tr}</span></h4><div class="rboxcontent" style="display: inline"><span class="fas fa-circle-notch fa-spin" style="font-size:24px"></span>{tr} The PDF is being prepared, please wait...{/tr}</div></div>
            </div>
        {/if}
        <span>
            {if $show_size eq 'y'}
                ({$size} {tr}bytes{/tr})
            {/if}
        </span>
        <div class="actions hidden-print float-end">
            <div class="btn-group">
                {if $prefs.feature_multilingual eq 'y' and $lang and $prefs.show_available_translations eq 'y'}
                    {include file='translated-lang.tpl' object_type='article'}
                {/if}
                <div class="btn-group">
                    {if ! $js}<ul><li>{/if}
                    <a class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" data-hover="dropdown" href="#"title="{tr}Article actions{/tr}">
                        {icon name="menu-extra"}
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">
                            {tr}Article actions{/tr}
                        </h6>
                        <div class="dropdown-divider"></div>
                        {if $tiki_p_edit_article eq 'y'}
                            <a class="dropdown-item" href="tiki-edit_article.php?articleId={$articleId}">
                                {icon name='edit'} {tr}Edit{/tr}
                            </a>
                        {/if}
                        {if $prefs.feature_cms_print eq 'y'}
                            <a class="dropdown-item" href="tiki-print_article.php?articleId={$articleId}">
                                {icon name='print'} {tr}Print{/tr}
                            </a>
                        {/if}
                        {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
                            <a class="dropdown-item" href="tiki-print_article.php?articleId={$articleId}&display=pdf">
                                {icon name="pdf"} {tr} &nbsp;PDF{/tr}
                                {assign var="hasPageAction" value="1"}
                            </a>
                        {elseif $tiki_p_admin eq "y" and $pdf_warning eq 'y'}
                            <a href="tiki-admin.php?page=packages" target="_blank" class="dropdown-item text-danger" title="{tr}Warning:mPDF Package Missing{/tr}">
                                {icon name="warning"} {tr} PDF{/tr}
                                {assign var="hasPageAction" value="1"}
                            </a>
                        {/if}
                        {if $prefs.user_favorites eq 'y'}
                            {favorite type="article" object=$articleId button_classes="dropdown-item icon"}
                        {/if}
                        {if $prefs.feature_share eq 'y' && $tiki_p_share eq 'y'}
                            <a class="dropdown-item tips" href="tiki-share.php?url={$smarty.server.REQUEST_URI|escape:'url'}">
                                {icon name='share'} {tr}Share{/tr}
                            </a>
                        {/if}
                        {if $prefs.feature_cms_sharethis eq "y"}
                            <div class="dropdown-item">
                                {capture name=shared_title}
                                    {tr}ShareThis{/tr}
                                {/capture}
                                {literal}<script type="text/javascript">
                                    //Create your sharelet with desired properties and set button element to false
                                    var object{/literal}{$articleId}{literal} = SHARETHIS.addEntry({},
                                            {button:false});
                                    //Output your customized button
                                    document.write('<a id="share{/literal}{$articleId}{literal}" href="javascript:void(0);">{/literal}{icon name="sharethis"} {tr}ShareThis{/tr}{literal}</a>');
                                    //Tie customized button to ShareThis button functionality.
                                    var element{/literal}{$articleId}{literal} = document.getElementById("share{/literal}{$articleId}{literal}"); object{/literal}{$articleId}{literal}.attachButton(element{/literal}{$articleId}{literal}); </script>{/literal}
                            </div>
                        {/if}
                        {if $prefs.sefurl_short_url eq 'y'}
                            <a class="dropdown-item" id="short_url_link" href="#" onclick="(function() { $(document.activeElement).attr('href', 'tiki-short_url.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title)); })();">
                                {icon name="link"} {tr}Get a short URL{/tr}
                                {assign var="hasPageAction" value="1"}
                            </a>
                        {/if}
                        {if $tiki_p_remove_article eq 'y'}
                            <a class="dropdown-item" href="tiki-list_articles.php?remove={$articleId}">
                                {icon name='remove'} {tr}Remove{/tr}
                            </a>
                        {/if}
                        {if $tiki_p_admin_cms eq 'y' or $tiki_p_assign_perm_cms eq 'y'}
                            <span class="dropdown-item">
                                {permission_link mode=text type=article permType=articles id=$articleId parentId=$topicId}
                            </span>
                        {/if}
                    </div>
                    {if ! $js}</li></ul>{/if}
                </div>
            </div>
        </div>
    </div>
{/if}
