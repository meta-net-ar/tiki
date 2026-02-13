<form action="tiki-admin.php?page=socnets" method="post">
    {ticket}

<br>
    <div class="adminoptionbox">
    {preference name=feature_socialnetworks visible="always"}
    </div>

    {remarksbox type="note" title="{tr}Note{/tr}"}
                  {tr}To use socnets integration and/or login you need at least:{/tr}
                 <ol>
                    <li>{tr}Register your site as a web application at the corresponding socnets site(s){/tr}.</li>
                    <li>{tr}Enable that socnet settings with a tick in the ENABLED tab below{/tr}.</li>
                  <li>{tr}Copy and enter{/tr} a) <strong>your app id</strong> {tr}and{/tr} b) <strong>your app secret</strong> {tr}from those sites and into the corresponding fields here below{/tr}.</li>
                  <li>{tr}Copy your site's URLs as shown in the settings below as callbacks to the corresponding socnets sites{/tr}.</li>
                 <li>{tr}Configure - enable login and other (some are optional!) settings - for the corresponding socnet in the SETTINGS tab below{/tr}.</li>
              </ol>
             P.S.
             <ol>
             <li> {tr}If you cannot see or want to change appearance of login buttons for the corresponding socnets you need to tweak login module mod-login.tpl and/or CSS{/tr}.</li>
             <li> {tr}If you see only number 1. but not the enabled socnets or encounter other problems, then, first of all, you need to clear Tiki caches and rebuild index{/tr}.</li>
             <li> {tr}Also, if some settings become disabled (like user prefix), you need to execute following sequence: disable-apply-enable-apply for ther corresponding socnet.{/tr}</li>
         </ol>
     {/remarksbox}


<div class="row">
    <div class="mb-3 col-lg-12 clearfix">
        {include file='admin/include_apply_top.tpl'}
    </div>
</div>
{tabset}

{tab name ="{tr}Enabled{/tr}"}

<h5 class="h4">{tr}Social network integration using Hybridauth{/tr}</h5>
{preference name=hybridauth_login_enabled visible="always"}
<div class="adminoptionboxchild" id="hybridauth_login_enabled_childcontainer">
    <ol>
        {foreach from=$prefs["`$socPrefix`enabledProviders"]  key=k  item=pNum}
            {$providerName = $socnetsAll[$pNum]}
            {$prefname="`$socPrefix``$providerName`_socnetEnabled" }
            {$prefs[$prefname] = 'y'}
            <strong><li>{$providerName}</li></strong>
        {/foreach}
    </ol>
    <fieldset>
        <div class="adminoptionbox">
            {$prefName = "`$socPrefix`enabledProviders"}
            {preference name=$prefName visible="always"}
        </div>
    </fieldset>
</div>
{/tab}
{tab name="{tr}Settings{/tr}"}

<ol>
{foreach from=$prefs["`$socPrefix`enabledProviders"]  key=k  item=pNum}
{$providerName = $socnetsAll[$pNum]}
{* TODO check in which cases is needed lower *}
{$providername = $providerName|lower}
<strong><li>{$providerName}</li></strong>

{* START of adminoptionsbox for {$providerName} *}
<div class="adminoptionbox {$providername} card">
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
                <div class="col-sm-12 {$providername} _loginEnabled" style="padding-top:5px;">{preference name=$prefname}
                <button class="{$providername} socbutton btn-secondary" style="border: none;">more/less... <i class="{$providername} fa fa-caret-right"></i> </button>
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


{/tab}
{tab name ="{tr}OAuth Configuration{/tr}" }
<div class="adminoptionbox">
    {remarksbox type="note" title="{tr}OAuth Callback/Redirect URL{/tr}"}
        <p>
            {tr}This is the base URL where social network providers will redirect users after authentication. This is a setting for the OAuth2 authentication flow to work correctly.{/tr}
        </p>
        <p>
            <strong>{tr}Current value:{/tr}</strong><br>
            <code>{$prefname = "`$socPrefix`socLoginBaseUrl"}{$prefs[$prefname]}</code>
        </p>
        <p>
            {tr}Social networks append the provider name to this URL to form the complete OAuth callback endpoint. When configuring your application on social network platforms (Facebook, LinkedIn, etc.), use this URL as the "Redirect URI" or "Callback URL".{/tr}
        </p>
    {/remarksbox}
    {preference name=$prefname}
</div>
{/tab}
{/tabset}

    {include file='admin/include_apply_bottom.tpl'}
</form>
