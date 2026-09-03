<div class="t_navbar btn-group mb-3">
    <a role="link" class="btn btn-link" href="tiki-admingroups.php" title="{tr}Admin groups{/tr}">
        {icon name="group"} {tr}Admin Groups{/tr}
    </a>
    <a role="link" class="btn btn-link" href="tiki-adminusers.php" title="{tr}Admin users{/tr}">
        {icon name="user"} {tr}Admin Users{/tr}
    </a>

    {permission_link mode=link label="{tr}Manage permissions{/tr}" icon_name="key" addclass="btn btn-link"}
</div>

{remarksbox type="tip" title="{tr}Tip{/tr}"}
{tr}For additional security settings, Please see {/tr}<a class="alert-link" href="https://doc.tiki.org/Security-Admin" title="Security Admin"><strong>{tr}Security Admin{/tr}</strong></a> {tr}on Tiki's documentation site{/tr}.
{/remarksbox}

<form class="admin" id="security" name="security" action="tiki-admin.php?page=security" method="post">
    {ticket}
    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    {tabset}

        {tab name="{tr}General Security{/tr}"}
            <fieldset>
                {if $haveMySQLSSL}{if $mysqlSSL}{$sslInfoType = 'info'}{else}{$sslInfoType = 'warning'}{/if}{else}{$sslInfoType = 'tip'}{/if}
                {remarksbox type=$sslInfoType title='{tr}MySQL SSL connection{/tr}'}
                    {if $haveMySQLSSL}
                        {if $mysqlSSL}
                            <p class="mysqlsslstatus">{icon name="lock" iclass="text-success"} {tr}MySQL SSL connection is active{/tr}
                            <a class="tikihelp alert-link" title="|MySQL SSL" target="tikihelp" href="http://doc.tiki.org/MySQL SSL">
                                {icon name="help"}
                            </a>
                            </p>
                        {else}
                            <p class="mysqlsslstatus">{icon name="unlock"} {tr}MySQL connection is not encrypted{/tr}<br>
                            {tr}To activate SSL, copy the keyfiles (.pem) to db/cert folder and enable "Use SSL connection". The filenames must end with "-key.pem", "-cert.pem", "-ca.pem" in cases the set of keys has 3 files and when using a single key it must end with "-ca.cert".{/tr}
                            <a class="tikihelp alert-link" title="|MySQL SSL" target="tikihelp" href="http://doc.tiki.org/MySQL SSL">
                                {icon name="help"}
                            </a>
                            </p>
                        {/if}
                    {else}
                        <p>{icon name="lock" iclass="text-warning"} {tr}MySQL Server does not have SSL activated{/tr}
                        <a class="tikihelp alert-link" title="|MySQL SSL" target="tikihelp" href="http://doc.tiki.org/MySQL SSL">
                            {icon name="help"}
                        </a>
                        </p>
                    {/if}
                {/remarksbox}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Smarty and Features Security{/tr}</legend>
                {preference name=smarty_security}
                <div class="adminoptionboxchild" id="smarty_security_childcontainer">
                    {preference name=smarty_security_allowed_tags}
                    {preference name=smarty_security_disabled_tags}
                    {preference name=smarty_security_allowed_modifiers}
                    {preference name=smarty_security_disabled_modifiers}
                    {preference name=smarty_security_allowed_builtin_php_functions}
                    {preference name=smarty_security_dirs}
                </div>
                {preference name=feature_purifier}
                {preference name=feature_htmlpurifier_output}

                {preference name=session_protected}
                {preference name=login_http_basic}
                <div class="adminoptionboxchild" id="smarty_security_childcontainer">
                    {tr}Please also see:{/tr} <a href="tiki-admin.php?page=login">{tr}HTTPS (SSL) and other login preferences{/tr}</a>
                </div>
                {preference name=pass_blacklist}
                {preference name=users_admin_actions_require_validation}

                {preference name=newsletter_external_client}

                {preference name=tiki_check_file_content}
                {preference name=tiki_allow_trust_input}
                {preference name=feature_quick_object_perms}
                {preference name=http_sslverifypeer}
                {preference name=http_use_curl}
                {preference name=feature_debug_console}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}SSRF Protection{/tr}</legend>
                {preference name=ssrf_whitelisted_hosts}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Trackers Security{/tr}</legend>
                {preference name=tracker_adminonlyviewedititem_by_default}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}User Encryption{/tr}{help url="User-Encryption"}</legend>
                {preference name=feature_user_encryption}
                <div class="adminoptionboxchild" id="feature_user_encryption_childcontainer">
                    {if $sodium_available}
                        {tr}Requires the Sodium PHP extension for encryption.{/tr} {tr}You have Sodium installed.{/tr}<br>
                    {elseif $openssl_available}
                        {tr}Requires the OpenSSL PHP extension for encryption.{/tr} {tr}You have OpenSSL installed.{/tr}<br>
                    {else}
                        {remarksbox type="warning" title="{tr}Sodium is not loaded{/tr}"}
                        {tr}User Encryption requires the PHP extension Sodium for encryption.{/tr}<br>{tr}You should activate Sodium before activating User Encryption{/tr}.
                        {/remarksbox}
                    {/if}
                    {tr}You may also want to add the Domain Password module somewhere.{/tr}<br>
                    <br>
                    {tr}Comma-separated list of password domains, e.g.: Company ABC,Company XYZ{/tr}<br>
                    {tr}The user can add passwords for a registered password domain.{/tr}
                    {preference name=feature_password_domains}
                    {if $prefs.feature_user_encryption eq 'y' and $show_user_encyption_stats eq 'y'}
                        {tr}Statistics for existing data:{/tr}
                        <ul>
                            <li>Sodium: {$user_encryption_stat_sodium}</li>
                            <li>OpenSSL: {$user_encryption_stat_openssl}</li>
                            <li>MCrypt: {$user_encryption_stat_mcrypt}</li>
                        </ul>
                        {tr}When no data which was encoded by MCrypt in Tiki versions prior to 18 is present, User Encryption does not need the MCrypt PHP extension.{/tr}
                    {/if}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}CSRF security{/tr}{help url="Security"}</legend>
                <div class="adminoptionbox">
                    {tr}Use these options to protect against cross-site request forgeries (CSRF){/tr}.
                </div>
                {preference name=site_short_lived_csrf_tokens}
                <div class="adminoptionboxchild" id="site_short_lived_csrf_tokens_childcontainer">
                    {preference name=site_security_timeout}
                </div>
                {preference name=feature_ticketlib}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}HTTP Headers{/tr}{help url="Security"}</legend>
                <div class="adminoptionbox">
                    {tr}Use these options to add options related with security to the HTTP Headers{/tr}.
                </div>

                {preference name=http_header_frame_options}
                <div class="adminoptionboxchild" id="http_header_frame_options_childcontainer">
                    {preference name=http_header_frame_options_value}
                </div>

                {preference name=http_header_xss_protection}
                <div class="adminoptionboxchild" id="http_header_xss_protection_childcontainer">
                    {preference name=http_header_xss_protection_value}
                </div>

                {preference name=http_header_content_type_options}

                {preference name=http_header_access_control_allow_credentials}

                {preference name=http_header_access_control_allow_methods}
                <div class="adminoptionboxchild" id="http_header_access_control_allow_methods_childcontainer">
                    {preference name=http_header_access_control_allow_methods_value}
                </div>

                {preference name=http_header_access_control_allow_headers}
                <div class="adminoptionboxchild" id="http_header_access_control_allow_headers_childcontainer">
                    {preference name=http_header_access_control_allow_headers_value}
                </div>

                {preference name=http_header_cross_origin_embedder_policy}
                <div class="adminoptionboxchild" id="http_header_cross_origin_embedder_policy_childcontainer">
                    {preference name=http_header_cross_origin_embedder_policy_value}
                </div>

                {preference name=http_header_cross_origin_resource_policy}
                <div class="adminoptionboxchild" id="http_header_cross_origin_resource_policy_childcontainer">
                    {preference name=http_header_cross_origin_resource_policy_value}
                </div>

                {preference name=http_header_cross_origin_opener_policy}
                <div class="adminoptionboxchild" id="http_header_cross_origin_opener_policy_childcontainer">
                    {preference name=http_header_cross_origin_opener_policy_value}
                </div>

                {preference name=http_header_content_security_policy}
                <div class="adminoptionboxchild" id="http_header_content_security_policy_childcontainer">
                    {preference name=http_header_content_security_policy_value}
                </div>

                {preference name=http_header_strict_transport_security}
                <div class="adminoptionboxchild" id="http_header_strict_transport_security_childcontainer">
                    {preference name=http_header_strict_transport_security_value}
                </div>

                {preference name=http_header_set_cookie_samesite}

                {preference name=http_header_public_key_pins}
                <div class="adminoptionboxchild" id="http_header_public_key_pins_childcontainer">
                    {preference name=http_header_public_key_pins_value}
                </div>

                {preference name=http_header_referrer_policy}
                <div class="adminoptionboxchild" id="http_header_referrer_policy_childcontainer">
                    {preference name=http_header_referrer_policy_value}
                </div>

                {preference name=http_header_permitted_cross_domain_policies}
                <div class="adminoptionboxchild" id="http_header_permitted_cross_domain_policies_childcontainer">
                    {preference name=http_header_permitted_cross_domain_policies_value}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}.htaccess Security{/tr}{help url="Security"}</legend>
                {preference name=security_warn_htaccess_mismatch}
            </fieldset>
        {/tab}

        {tab name="{tr}Spam Protection{/tr}"}
            {remarksbox type="tip" title="{tr}Tip{/tr}"}
                {tr _0='<a href="http://doc.tiki.org/Forum-Admin#Forum_moderation" target="_blank" class="alert-link">' _1="</a>" _2="<strong>" _3="</strong>" _4='<a href="tiki-admin_actionlog.php" target="_blank" class="alert-link">' _5="</a>" _6='<a href="tiki-adminusers.php" target="_blank" class="alert-link">' _7="</a>" _8='<a href="tiki-list_comments.php" target="_blank" class="alert-link">' _9="</a>"}You can additionally protect from spam enabling the '%0moderation queue on forums%1', or through %2banning%3 multiple ip's from the '%4Action log%5', from '%6Users registration%7', or from the '%8Comments moderation queue%9' itself{/tr}.
            {/remarksbox}
            <fieldset>
                <legend class="h3">{tr}CAPTCHA Options{/tr}</legend>
                {preference name=feature_antibot}
                <div class="adminoptionboxchild" id="feature_antibot_childcontainer">
                    {preference name=captcha_type}
                    {remarksbox type="tip" title="{tr}Tip{/tr}" close="n"}
                        {tr}Use the selector above to choose the active CAPTCHA implementation. The selected CAPTCHA must also be configured in its section below before it can be used. Other CAPTCHA settings remain available but inactive until selected.{/tr}
                    {/remarksbox}
                    <div class="adminoptionboxchild">
                        <h5 class="mt-3">{tr}Classic CAPTCHA{/tr}</h5>
                        <div class="adminoptionboxchild" id="captcha_wordLen_childcontainer">
                            {preference name=captcha_wordLen}
                            {preference name=captcha_width}
                            {preference name=captcha_noise}
                        </div>
                        <h5 class="mt-4">{tr}Custom Questions CAPTCHA{/tr}</h5>
                        <div class="adminoptionboxchild" id="captcha_questions_childcontainer">
                            {preference name=captcha_questions}
                        </div>
                        <h5 class="mt-4">{tr}Google reCAPTCHA{/tr}</h5>
                        <div class="adminoptionboxchild">
                            {preference name=recaptcha_pubkey}
                            {preference name=recaptcha_privkey}
                            {preference name=recaptcha_theme}
                            {preference name=recaptcha_version}
                        </div>
                        <h5 class="mt-4">{tr}Altcha CAPTCHA{/tr}</h5>
                        <div class="adminoptionboxchild">
                            {preference name=altcha_hmac_key}
                        </div>
                    </div>
                </div>
            </fieldset>
            {preference name=feature_wiki_protect_email}
            {preference name=feature_wiki_ext_rel_nofollow}
            {preference name=feature_banning}
            <div class="adminoptionboxchild" id="feature_banning_childcontainer">
                {preference name=feature_banning_email}
                {preference name=feature_banning_attempts}
                {preference name=feature_banning_duration}
            </div>

            {preference name=feature_comments_moderation}
            {preference name=useRegisterPasscode}
            <div class="adminoptionboxchild" id="useRegisterPasscode_childcontainer">
                {preference name=registerPasscode}
                {preference name=showRegisterPasscode}
            </div>

            {preference name=registerKey}
        {/tab}

        {tab name="{tr}Site Access{/tr}"}
            {preference name=site_closed}
            <div class="adminoptionboxchild" id="site_closed_childcontainer">
                {preference name=site_closed_title}
                {preference name=site_closed_msg}
                <div class="col-sm-8 offset-sm-4">
                    {button _text='{tr}Test site closed message{/tr}' href="tiki-admin.php?page=security&test_closed=y" _class='btn-sm' _type='info'}
                </div>
            </div>

            {preference name=use_load_threshold}
            <div class="adminoptionboxchild" id="use_load_threshold_childcontainer">
                {preference name=load_threshold}
                {preference name=load_retry_after}
                {preference name=site_busy_title}
                {preference name=site_busy_msg}
                <div class="col-sm-8 offset-sm-4">
                    {button _text='{tr}Test site busy message{/tr}' href="tiki-admin.php?page=security&test_busy=y" _class='btn-sm' _type='info'}
                </div>
            </div>

            {preference name=ids_enabled}
            <div class="adminoptionboxchild" id="ids_enabled_childcontainer">
                <div class="mb-3 adminoptionbox clearfix">
                    <div class="offset-sm-4 col-sm-8">
                        <a href="tiki-admin_ids.php">{tr}Admin IDS custom rules{/tr}</a>
                    </div>
                </div>
                {preference name=ids_custom_rules_file}
                {preference name=ids_mode}
                {preference name=ids_threshold}
                {preference name=ids_log_to_file}
                {*{preference name=ids_log_to_database}*}
            </div>

        {/tab}

        {tab name="{tr}Tokens{/tr}"}
            {remarksbox type="tip" title="{tr}Tip{/tr}"}
                {tr _0='<a href="tiki-admin_tokens.php" class="alert-link">' _1="</a>" _2='<a href="tiki-adminusers.php" class="alert-link">' _3='</a>'}To manage tokens go to %0Admin Tokens%1 page. Tokens are also used for the Temporary Users feature (see %2Admin Users%3).{/tr}
            {/remarksbox}
            {preference name=auth_token_access}
            {preference name=auth_token_access_maxtimeout}
            {preference name=auth_token_access_maxhits}
            {preference name=auth_token_share}
            {preference name=auth_token_preserve_tempusers}
        {/tab}

        {tab name="{tr}OpenPGP{/tr}"}
            <fieldset>
                <legend class="h3">{tr}OpenPGP functionality for PGP/MIME encrypted email messaging{/tr}</legend>
                {remarksbox type="tip" title="{tr}Note{/tr}"}
                    {tr}Experimental OpenPGP fuctionality for PGP/MIME encrypted email messaging.{/tr}<br><br>
                    {tr}All email-messaging/notifications/newsletters are sent as PGP/MIME-encrypted messages, signed with the signer-key, and are completely 100% opaque to outsiders. All user accounts need to be properly configured into gnupg keyring with public-keys related to their tiki-account-related email-addresses.{/tr}
                {/remarksbox}
                {preference name=openpgp_gpg_pgpmimemail}
                <div class="adminoptionboxchild" id="openpgp_gpg_pgpmimemail_childcontainer">
                    {preference name=openpgp_gpg_home}
                    {preference name=openpgp_gpg_path}
                    {preference name=openpgp_gpg_signer_passphrase_store}
                    <div class="adminoptionboxchild openpgp_gpg_signer_passphrase_store_childcontainer preferences">
                        {preference name=openpgp_gpg_signer_passphrase}
                        <br><em>{tr}If you use preferences option for the signer passphrase, clear the file option just for security{/tr}</em>
                    </div>
                    <div class="adminoptionboxchild openpgp_gpg_signer_passphrase_store_childcontainer file">
                        {preference name=openpgp_gpg_signer_passfile}
                        <br><em>{tr}If you use file for the signer passphrase, clear the preferences option just for security{/tr}</em>
                    </div>
                    {remarksbox type="tip" title="{tr}Note{/tr}"}
                        {tr _0='<a href="tiki-admin.php?page=general&alt=General" class="alert-link">' _1="</a>"}The email of preference %0'sender_email'%1 is used as signer key ID, and it must have both private and public key in the gnupg keyring.{/tr}
                    {/remarksbox}
                </div>
            </fieldset>
        {/tab}

        {tab name='{tr}Encryption{/tr}' key='encryption'}
            <br>
            {remarksbox type="note" title="{tr}About encryption{/tr}"}
                {tr}Encryption page allows you to create different encryption keys and share them securely with team members.{/tr}<br>
                {tr}Find out more here:{/tr}{help url="User-Encryption"}
            {/remarksbox}
            {if $encryption_enabled neq 'y'}
                {remarksbox type="error" title="{tr}Error{/tr}"}
                    {tr}Openssl extension is required to use this module.{/tr}
                {/remarksbox}
            {/if}
            <div id="single-spa-application:@vue-mf/sss-admin"></div>

            {jq}
                window.registerApplication({
                    name: "@vue-mf/sss-admin",
                    app: () => importShim("@vue-mf/sss-admin"),
                    activeWhen: (location) => true,
                    customProps: {},
                });
                onDOMElementRemoved("single-spa-application:@vue-mf/sss-admin", function () {
                    window.unregisterApplication("@vue-mf/sss-admin");
                });
            {/jq}
        {/tab}
        {tab name="{tr}API{/tr}" key="api"}
            <br>
            {remarksbox type="tip" title="{tr}Tip{/tr}"}
                {tr _0="tiki-admin.php?page=login#contentadmin_login-12" _1="tiki-admin_oauthserver.php"}Enable API access and manage authentication tokens here. In addition, you can use Tiki as an OAuth 2.0 server, <a href="%0">configure here</a> and <a href="%1">manage clients here</a>.{/tr}<br/>
                {tr _0="api/"}API documentation <a href="%0">is available here</a>.{/tr}
            {/remarksbox}
            {preference name=auth_api_tokens}
            <div class="adminoptionboxchild" id="auth_api_tokens_childcontainer">
                {service_inline controller=api_token action=list}
            </div>
        {/tab}
        {tab name="{tr}Webhooks{/tr}" key="webhooks"}
            <br>
            {remarksbox type="tip" title="{tr}Tip{/tr}"}
                {tr}Configure Tiki to receive webhooks from 3rd party servers. Setup verification here and bind your event listener to tiki.webhook.received event.{/tr}<br>
                {tr}URL to receive webhooks:{/tr} {$base_url}tiki-webhooks.php<br>
                {tr}TODO: add a link to _custom/lib/setup/code explanation.{/tr}
            {/remarksbox}
            {preference name=auth_webhooks}
            <div class="adminoptionboxchild" id="auth_webhooks_childcontainer">
                {service_inline controller=webhook action=list}
            </div>
        {/tab}
        {tab name='{tr}Attack Protection{/tr}' key='attack_protection'}
            <fieldset>
                <legend class="h3">{tr}Brute Force Protection{/tr}</legend>
                {preference name=bruteforce_protection}
                {preference name=bruteforce_initial_delay}
                {preference name=bruteforce_growth_rate}
                {preference name=bruteforce_forget_time}
            </fieldset>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
