<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_redirect_info()
{
    return [
        'name' => tra('Redirect'),
        'documentation' => 'PluginRedirect',
        'description' => tra('Redirect to another page.'),
        'prefs' => [ 'wikiplugin_redirect' ],
        'validate' => 'arguments',
        'iconname' => 'next',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'page' => [
                'required' => false,
                'name' => tra('Page Name'),
                'description' => tra('Wiki page name to redirect to.'),
                'since' => '3.0',
                'filter' => 'pagename',
                'profile_reference' => 'wiki_page',
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra('Complete URL, internal or external.'),
                'since' => '3.0',
                'filter' => 'url',
            ],
            'perspective' => [
                'required' => false,
                'name' => tra('Perspective'),
                'description' => tra('The ID of a perspective to switch to (requires feature_perspective).'),
                'since' => '7.1',
                'filter' => 'int',
                'profile_reference' => 'perspective',
            ],
            'autologin_remotetiki' => [
                'required' => false,
                'name' => tra('Auto login remote Tiki and redirect to page there.'),
                'description' => tra('Base URL where remote Tiki is located, to auto login to prior to redirection to page there, e.g. https://othertiki.com.'),
                'filter' => 'url',
                'default' => '',
            ],
        ],
    ];
}

function wikiplugin_redirect($data, $params)
{
    global $just_saved;
    $tikilib = TikiLib::lib('tiki');
    extract($params, EXTR_SKIP);
    $areturn = '';

    if (is_null($page) && is_null($url)) {
        $areturn = "REDIRECT plugin: No page or url specified!<br />";
    }

    if (! is_null($page)) {
        $location = $page;
    } elseif (! is_null($url)) {
        $location = $url;
    } elseif (! is_null($perspective)) {
        $location = tra('perspective ') . $perspective;
    } else {
        $location = tra('nowhere');
        $areturn .= "REDIRECT plugin: No perspective specified!";
    }

    if ($just_saved) {
        $areturn = sprintf(tra("REDIRECT plugin: The redirection to '%s' is disabled just after saving the page."), $location);
    } elseif (isset(TikiLib::lib('parser')->option['indexing']) && TikiLib::lib('parser')->option['indexing']) {
        return;
    } elseif (isset(TikiLib::lib('parser')->option['preview_mode']) && TikiLib::lib('parser')->option['preview_mode']) {
        $areturn = sprintf(tra("REDIRECT plugin: The redirection to '%s' is disabled in preview mode. "), $location);
    } elseif ((isset($_REQUEST['redirectpage']))) {
        $areturn = tra("REDIRECT plugin: redirect loop detected!");
    } elseif (isset(TikiLib::lib('parser')->option['print']) && TikiLib::lib('parser')->option['print'] == 'y') {
        $info = $tikilib->get_page_info($location);
        $defaultOptions = [
            'is_html' => false,
            'absolute_links' => false,
            'language' => ''
        ];

        $parserOptions = TikiLib::lib('parser')->option ?? $defaultOptions;
        if (is_array($info) && isset($info['data'])) {
            return TikiLib::lib('parser')->parse_data($info['data'], $parserOptions);
        }
    } else {
        if (! is_null($perspective)) {
            global $base_host;
            $access = TikiLib::lib('access');
            $perspectivelib = TikiLib::lib('perspective');
            $access->check_feature('feature_perspective');

            if ($_SESSION['current_perspective'] !== $perspective) {
                if ($perspectivelib->perspective_exists($perspective)) {
                    $_SESSION['current_perspective'] = $perspective;
                    $_SESSION['current_perspective_name'] = $perspectivelib->get_perspective_name($_SESSION['current_perspective']);
                }
                if (empty($page) && empty($url)) {
                    $url = $base_host . $_SERVER['REQUEST_URI'];
                }
            }
            $areturn = '';  // errors set above not relevant if using perspective
        }

        // Make it possible to edit the plugin in wysiwyg
        // Do not redirect if the page is being edited
        $isEditMode = (str_contains($_SERVER['SCRIPT_NAME'], 'tiki-editpage.php')) || (isset($_REQUEST['controller']) && $_REQUEST['controller'] == 'edit');
        if (! $isEditMode) {
            // Auto login to remote Tiki functionality
            if (! empty($autologin_remotetiki)) {
                if (str_ends_with($autologin_remotetiki, '/')) {
                    $autologin_remotetiki = rtrim($autologin_remotetiki, '/');
                }
                if (! empty($page)) {
                    $redirect_page = $page;
                } else {
                    $redirect_page = '';
                }
                $remotetikiurl = get_remotetikiurl($autologin_remotetiki, $redirect_page);
                if (filter_var($remotetikiurl, FILTER_VALIDATE_URL)) {
                    header("Location: $remotetikiurl");
                    die;
                } else {
                    TikiLib::lib('access')->display_error('', tra('Remote system error'), "500");
                    die;
                }
            }

            if (! is_null($page)) {
                $safePage = urlencode($page);
                // Ensure temporary, non-cached redirect to avoid URL caching issues and SEO impact
                header("Cache-Control: no-cache, no-store, must-revalidate");
                header("Pragma: no-cache");
                header("Expires: 0");
                if (isset($_REQUEST['page']) && ! empty($_REQUEST['page'])) {
                    $safeRedirectPage = urlencode($_REQUEST['page']);
                    if ($safeRedirectPage === $safePage) {
                        TikiLib::lib('access')->redirect("tiki-index.php?page={$safePage}", '', 302);
                    } else {
                        TikiLib::lib('access')->redirect("tiki-index.php?page={$safePage}&redirectpage={$safeRedirectPage}", '', 302);
                    }
                } else {
                    // Final clean URL with 301 redirect if no loop check is needed
                    TikiLib::lib('access')->redirect("tiki-index.php?page={$safePage}", '', 301);
                }
            }
            if (! is_null($url)) {
                global $base_url, $url_path;        // try to detect redirect loop to server root
                if (
                    $url == $base_url ||            // whole site url
                    $url . '/' == $base_url ||      // optional trailing /
                    $url == $url_path ||            // just the path?
                    $url . '/' == $url_path ||
                    preg_match('/[\.]?\/$/', $url)  // either ./ or / current dir or root
                ) {
                    $hp = TikiLib::lib('wiki')->get_default_wiki_page();

                    if ($_REQUEST['page'] === $hp && ! isset($_GET['page']) && ! isset($_POST['page'])) {
                        return '';                      // don't redirect if we've already been redirected to the "home page"
                    }
                }
                TikiLib::lib('access')->redirect($url);
            }
        }
    }
    return $areturn;
}

/**
 * This function gets a URL with a token in it so that the user can be redirected that to actually login
 * @param $autologin_remotetiki The remote Tiki base url, e.g. https://remotetiki.com
 * @param $redirect_page The pagename of the page on the remote Tiki to redirect to.
 * If not set, user will end up on the default home page on remote Tiki.
 * @return string The URL with the token in it.
 */
function get_remotetikiurl($autologin_remotetiki, $redirect_page)
{
    // Get URL for user to login into remote Tiki
    global $user, $base_url;
    TikiLib::lib('access')->check_user($user);
    $email = TikiLib::lib('user')->get_user_email($user);
    $realName = trim(TikiLib::lib('tiki')->get_user_preference($user, 'realName', ''));
    $remotetikiurl = $autologin_remotetiki . '/tiki-autologin.php';
    $client = TikiLib::lib('tiki')->get_http_client($remotetikiurl);
    $groups = TikiLib::lib('user')->get_user_groups($user);
    $base = [ 'uname' => $user, 'email' => $email, 'realName' => $realName, 'page' => $redirect_page, 'base_url' => $base_url, 'groups' => $groups ];
    try {
        $client->setParameterPost($base);
        $client->setMethod(Laminas\Http\Request::METHOD_POST);
        $response = $client->send();
        if ($response->isSuccess()) {
            return $response->getBody();
        } else {
            TikiLib::lib('access')->display_error('', $response->getReasonPhrase(), $response->getStatusCode());
            die;
        }
    } catch (Laminas\Http\Exception\ExceptionInterface $e) {
        throw new Exception($e->getMessage());
    }
}
