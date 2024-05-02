<form action="tiki-admin.php?page=socialnetworks" method="post">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {tabset}
        {tab name="{tr}General{/tr}"}
            <legend class="h3">{tr}Social network integration{/tr}</legend>
            {preference name=feature_socialnetworks visible="always"}

            <ol>
                {foreach $prefs["`$socPrefix`enabledProviders"] as $k => $pNum}
                    {$providerName = $socnetsAll[$pNum]}
                    {$prefname="`$socPrefix``$providerName`_socnetEnabled" }
                    {$prefs[$prefname] = 'y'}
                    <strong><li>{$providerName}  {* debug pNum={$pNum} k={$k} *}</li></strong>
                {/foreach}
            </ol>

            <fieldset>
                <div class="adminoptionbox">
                    {$prefName = "`$socPrefix`enabledProviders"}
                    {preference name=$prefName visible="always"}
                </div>

            </fieldset>

            {remarksbox type="note" title="{tr}Note{/tr}"}
                {tr}To enable social network login and/or integration, these steps are required{/tr}
                <ol>
                    <li>{tr}Register your site as a web application at the chosen social network site.{/tr}</li>
                    <li>{tr}Select the social network for configuration, above.{/tr}</li>
                    <li>{tr}Copy these items from the social network site -{/tr} <strong>your app id</strong> {tr}and{/tr} <strong>your app secret</strong> {tr}- and input them into the corresponding fields for the social network under the Settings tab.{/tr}</li>
                    <li>{tr}Copy your website's URLs as shown at the bottom of the settings for each social network as callbacks to the corresponding social network site.{/tr}</li>
                    <li>{tr}Configure the settings to enable login and other settings (some are optional) for the social network under the Settings tab.{/tr}</li>
                 </ol>
                {tr}Also{/tr}
                <ol>
                     <li> {tr}If the login button for the corresponding social network can't be seen or if its appearance needs to be modified, the login module template file (mod-login.tpl) and/or related CSS might need to be adjusted.{/tr}</li>
                     <li> {tr}If only number 1. is visible but not the configured social network, or there are other problems, then clear the Tiki caches and rebuild the search index.{/tr}</li>
                     <li> {tr}Also, if some settings become disabled (such as the user prefix), execute the following sequence: disable-apply-enable-apply for the affected social network.{/tr}</li>
                </ol>
            {/remarksbox}
        {/tab}
        {tab name="{tr}Settings{/tr}"}

            <ol>
                {foreach $prefs["`$socPrefix`enabledProviders"] as $k => $pNum}
                    {$providerName = $socnetsAll[$pNum]}
                    {* TODO check in which cases is needed lower *}
                    {$providername = $providerName|lower}
                    <strong><em>{$providerName}</em></strong>

                    {* START of adminoptionsbox for {$providerName} *}
                    <div class="adminoptionbox {$providername} card pb-3">
                        <ol>
                            <br>
                            {foreach from=$socBasePrefs key=basePref item=prefItem}
                                {$prefname="`$socPrefix``$providerName``$basePref`"}
                                {if ($basePref === '_socnetEnabled')}
                                    {* skip this iteration *}
                                    {continue}
                                {elseif ($basePref === '_loginEnabled') }
                                    {* if we use closing buttons again... *}
                                    {* start of _loginEnabled for {$providerName} *}
                                    <div class="col-sm-12 {$providername} _loginEnabled" style="padding-top:5px;">
                                        {preference name=$prefname}
                                        <button class="{$providername} socbutton btn btn-secondary dropdown-toggle"  type="button" id="{$providername}dropdownMenuButton" data-bs-toggle="dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            {tr}More/less...{/tr} <i class="{$providername} fa fa-caret-right d-none"></i>
                                        </button>
                                    </div> {* end of _loginEnabled for {$providerName} *}
                                {else}
                                    <div class="col-sm-12 {$providername} _else_loginEnabled">
                                        <li>{preference name=$prefname}</li>
                                    </div>
                                {/if}
                            {/foreach}
                        </ol>

                        <div class="col-sm-12 {$providername} _else_loginEnabled">
                            {remarksbox type="note" title="{tr}Urls for {/tr}{$providerName}"}
                            Login&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;url: {$callbackUrl}?provider={$providerName}<br>
                            Remove&nbsp;url: {$callbackUrl}?remove={$providerName}
                            {/remarksbox}
                        </div>
                    </div> {* END of adminoptionsbox for {$providerName} *}

                {/foreach}
            </ol>

            {jq}
 $("._else_loginEnabled").hide();

 var chev = $(".socbutton");
 chev.on("click", function (ev){
     var allclass = $(ev.target).attr('class');
     var netname1 = allclass.split(' ')[0];
     var cl2 = $(this).children('.fa');
     cl2.toggleClass('fa-caret-right fa-caret-down');
     var logch = $( "." + netname1 + "._else_loginEnabled");
        cl2.is('.fa-caret-down') ?  logch.show() : logch.hide();
     ev.preventDefault();
 });

 var chk = $("input, input:checkbox","._loginEnabled");
 chk.on("change", function (ev) {
     var netname = ev.target.name.split('_')[1];
     var ch2 = $("i."+netname+".fa");
     var logch = $( "." + netname + "._else_loginEnabled");
     if ($(this).is( ":checked" )) {
         logch.show();
         ch2.removeClass('fa-caret-right');
         ch2.addClass('fa-caret-down');
     } else {
         ch2.removeClass('fa-caret-down');
         ch2.addClass('fa-caret-right');
         logch.hide();
     }
     ev.preventDefault();
 });
            {/jq}


            {************************************}
            <fieldset class="mt-5">
                <legend class="h3">{tr}Debug and Logs{/tr}</legend>
                <div class="adminoptionbox">
                    {$prefname = "`$socPrefix`socLoginBaseUrl"}
    {*                {$prefs[$prefname]}*}
                    {preference name=$prefname}
                </div>
            </fieldset>
        {/tab}
        {tab name="{tr}bit.ly{/tr}"}
            <br>
            {remarksbox type="note" title="{tr}Note{/tr}"}
                <p>
                    {tr}There is no need to set up a site-wide bit.ly account; every user can have his or her own, but this allows for site-wide statistics{/tr}<br>
                    {tr}Go to{/tr} <a class="alert-link" href="http://bit.ly/a/sign_up">http://bit.ly/a/sign_up</a> {tr}to sign up for an account{/tr}.<br>
                    {tr}Go to{/tr} <a class="alert-link" href="http://bit.ly/a/your_api_key">http://bit.ly/a/your_api_key</a> {tr}to retrieve the API key{/tr}.
                </p>
            {/remarksbox}
            <div class="adminoptionbox">
                {preference name=socialnetworks_bitly_login}
                {preference name=socialnetworks_bitly_key}
                {preference name=socialnetworks_bitly_sitewide}
            </div>
        {/tab}
        {tab name="{tr}Share This{/tr}"}
            <br>
            <div class="adminoptionbox">
                {preference name=feature_wiki_sharethis}
                <div class="adminoptionboxchild" id="feature_wiki_sharethis_childcontainer">
                    {preference name=blog_sharethis_publisher}
                    {preference name=wiki_sharethis_encourage}
                </div>
            </div>
        {/tab}
        {tab name="{tr}Legacy Integrations{/tr}"}
            <fieldset>
                {remarksbox type="warning" title="{tr}Warning{/tr}"}
                    <p>
                        {tr}This connection mode will be removed in a future version and we will only use those provided by the library &nbsp;{/tr}
                        <a class="alert-link" href="https://doc.tiki.org/Social-Networks-Configuration" target="_blank"> Hybridauth</a>
                        {tr}find them on the General, Settings options in the social networks. If you have an opinion on this choice please let us know on{/tr}
                        <a class="alert-link" href="https://dev.tiki.org/Endangered-features#Candidates_for_removals_for_Tiki_26" target="_blank">https://dev.tiki.org/Endangered-features#Candidates_for_removals_for_Tiki_26</a>

                    </p>
                {/remarksbox}
                <legend class="h3">{tr}Twitter{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_twitter_site_name}
                    {preference name=socialnetworks_twitter_site_image}
                </div>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    <p>
                        {tr}To use Twitter integration, you must register this site as an application at{/tr}
                        <a class="alert-link" href="http://twitter.com/oauth_clients/" target="_blank">http://twitter.com/oauth_clients/</a>
                        {tr}and allow write access for the application{/tr}.<br>
                        {tr}Enter &lt;your site URL&gt;tiki-socialnetworks.php as callback URL{/tr}.
                    </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_twitter_consumer_key}
                    {preference name=socialnetworks_twitter_consumer_secret}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Facebook{/tr}</legend>
                <br>
                <div class="adminoptionbox">
                    {preference name=socialnetworks_facebook_site_name}
                    {preference name=socialnetworks_facebook_site_image}
                </div>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                    <p>
                        {tr}To use Facebook integration, you must register this site as an application at{/tr}
                        <a class="alert-link" href="https://developers.facebook.com/" target="_blank">https://developers.facebook.com/</a>
                        {tr}and allow extended access for the application{/tr}.<br>
                        {tr}Enter &lt;your site URL&gt;tiki-socialnetworks.php?request_facebook as Site URL and &lt;your site&gt; as Site Domain{/tr}.
                    </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_facebook_application_id}
                    {preference name=socialnetworks_facebook_application_secr}
                    {preference name=socialnetworks_facebook_login}
                    {preference name=socialnetworks_facebook_autocreateuser}
                    <div class="adminoptionboxchild" id="socialnetworks_facebook_autocreateuser_childcontainer">
                        {preference name=socialnetworks_facebook_firstloginpopup}
                        {preference name=socialnetworks_facebook_email}
                        {preference name=socialnetworks_facebook_create_user_trackeritem}
                        {preference name=socialnetworks_facebook_names}
                    </div>
                    {remarksbox type="note" title="{tr}Note{/tr}"}
                        {tr}The following preferences affect what permissions the user is asked to allow Tiki to do by Facebook when authorizing it.{/tr}
                    {/remarksbox}
                    {preference name=socialnetworks_facebook_publish_stream}
                    {preference name=socialnetworks_facebook_manage_events}
                    {preference name=socialnetworks_facebook_manage_pages}
                    {preference name=socialnetworks_facebook_sms}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LinkedIn{/tr}</legend>
                <br>
                {remarksbox type="note" title="{tr}Note{/tr}"}
                <p>
                    {tr}To use LinkedIn integration, you must register this site as an application at{/tr}
                    <a class="alert-link" href="https://www.linkedin.com/developer/apps" target="_blank">https://www.linkedin.com/developer/apps</a>
                    {tr}and allow necessary permissions for the application{/tr}.<br>
                    {tr}Enter &lt;your site URL&gt;tiki-socialnetworks_linkedin.php as Authorized OAuth Redirect URLs{/tr}.
                </p>
                {/remarksbox}
                <div class="adminoptionbox">
                    {preference name=socialnetworks_linkedin_client_id}
                    {preference name=socialnetworks_linkedin_client_secr}
                    {preference name=socialnetworks_linkedin_login}
                    {preference name=socialnetworks_linkedin_autocreateuser}
                    <div class="adminoptionboxchild" id="socialnetworks_linkedin_autocreateuser_childcontainer">
                        {preference name=socialnetworks_linkedin_email}
                        {preference name=socialnetworks_linkedin_create_user_trackeritem}
                        {preference name=socialnetworks_linkedin_names}
                    </div>
                </div>
            </fieldset>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
