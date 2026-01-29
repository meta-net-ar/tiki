<meta charset="utf-8">
<!--Latest IE Compatibility-->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
{if $base_uri and ($dir_level gt 0 or $prefs.feature_html_head_base_tag eq 'y')}
    <base href="{$base_uri|escape}">
{/if}
{if $headerlib}
{$headerlib->output_headers(true)}
{/if}

{if ($prefs.feature_blogs eq 'y' and $prefs.feature_blog_sharethis eq 'y') or ($prefs.feature_articles eq 'y' and $prefs.feature_cms_sharethis eq 'y') or ($prefs.feature_wiki eq 'y' and $prefs.feature_wiki_sharethis eq 'y')}
    {if $prefs.blog_sharethis_publisher neq "" and $prefs.article_sharethis_publisher neq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.blog_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher neq "" and $prefs.article_sharethis_publisher eq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.blog_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher eq "" and $prefs.article_sharethis_publisher neq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#publisher={$prefs.article_sharethis_publisher}&amp;type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {elseif $prefs.blog_sharethis_publisher eq "" and $prefs.article_sharethis_publisher eq ""}
        <script type="text/javascript" src="https://ws.sharethis.com/button/sharethis.js#type=website&amp;buttonText=&amp;onmouseover=false&amp;send_services=aim"></script>
    {/if}
{/if}

{if $headerlib} 
    {* This is wrong (and always has been).
    For one thing, why would headerlib be or not be there.
    Second, and more importantly it prevents wikiplugins, block handlers, etc. from adding CSS, requiring workarounds like
    https://gitlab.com/tikiwiki/tiki/-/merge_requests/10093
    
    While this pattern may practically work (if not cleanly) in the footer because smarty processing is done by then, it will never work generally here, we cannot time-travel.

    A different solution needs to be implemented if we want both wikiplugins and block handlers to "just work", and headerlib add_css to "just work".

    The simplest solution is probably to add this in SmartyTiki AFTER processing, using DOMDocument. - benoitg - 2026-04-30
    *}
    {$headerlib->output_headers()}
{/if}

{if !empty($prefs.feature_custom_html_head_content)}
    {eval var=$prefs.feature_custom_html_head_content}
{/if}

{if $prefs.switch_color_module_assigned eq 'y'}
    <script>
        const getStoredTheme = () => localStorage.getItem("theme");
        const setStoredTheme = (theme) => localStorage.setItem("theme", theme);
        const getPreferredTheme = () => {
            const storedTheme = getStoredTheme();
            if (storedTheme)  return storedTheme;
            return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
        };
        const setTheme = (theme) => {
            if (theme === "auto" && window.matchMedia("(prefers-color-scheme: dark)").matches)  document.documentElement.setAttribute("data-bs-theme", "dark"); 
            else document.documentElement.setAttribute("data-bs-theme", theme);
        };
        setTheme(getPreferredTheme());
    </script>
    <style>
        {foreach from=$prefs['custom_color_mode'] item=mode}
            {if null !== $mode['css_variables']}
                {$mode['css_variables']}
            {/if}
        {/foreach}
    </style>
{/if}

{* Include mautic snipet code with mautic *}
{if $prefs.site_mautic_enable eq 'y' && $prefs.wikiplugin_mautic eq 'y' && $prefs.site_mautic_tracking_script_location eq 'head'}
    {wikiplugin _name=mautic type="inclusion"}{/wikiplugin}
{/if}
{* END of html head content *}
