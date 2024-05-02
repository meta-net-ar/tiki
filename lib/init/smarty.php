<?php

/**
 * brings Smarty functionality into Tiki
 *
 * this script may only be included, it will die if called directly.
 *
 * @package TikiWiki
 * @subpackage lib\init
 * @copyright (c) Copyright by authors of the Tiki Wiki CMS Groupware Project. All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */

// die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

require_once __DIR__ . '/../setup/third_party.php';
require_once __DIR__ . '/SmartyTikiErrorHandler.php';

/**
 * extends \Smarty\Security
 * @package TikiWiki\lib\init
 */
class Tiki_Security_Policy extends \Smarty\Security
{
    /**
     * is an array of regular expressions matching URIs that are considered trusted.
     * See https://smarty-php.github.io/smarty/5.x/api/security/ for more details
     *
     * @var array
     */
    public $trusted_uri = [];

    /**
     * This is the list of template directories that are considered secure.
     * $template_dir is in this list implicitly. A directory configured using $smarty->setTemplateDir() is
     * considered secure implicitly. The default is an empty array.
     *
     * @var array
     */
    public $secure_dir = [];

    /**
     * This is an array of allowed tags. It's the array of (registered / autoloaded) function-, block and filter plugins
     * that should be accessible to the template. If empty, no restriction by allowed_tags.
     *
     * @var array
     */
    public $allowed_tags = [];

    /**
     * This is an array of disabled tags. It's the array of (registered / autoloaded) function-, block and filter plugins
     * that may not be accessible to the template. If empty, no restriction by disabled_tags.
     *
     * @var array
     */
    public $disabled_tags = [];

    /**
     * This is an array of allowed modifier plugins. It's the array of (registered / autoloaded) modifiers that should be accessible to the template.
     * If this array is non-empty, only the herein listed modifiers may be used. This is a whitelist.
     * If empty, no restriction by allowed_modifiers.
     *
     * @var array
     */
    public $allowed_modifiers = [];

    /**
     * This is an array of disabled modifier plugins. It's the array of (registered / autoloaded) modifiers that may not be accessible to the template.
     * If empty, no restriction by disabled_modifiers.
     *
     * @var array
     */
    public $disabled_modifiers = [];

    /**
     * needs a proper description
     * @param \Smarty\Smarty $smarty
     */
    public function __construct($smarty)
    {
        if (class_exists("TikiLib")) {
            $tikilib = TikiLib::lib('tiki');
            // modlib defines zone_is_empty which must exist before smarty initializes to fix bug with smarty autoloader after version 3.1.21
            TikiLib::lib('mod');
        }

        parent::__construct($smarty);


        //With phpunit and command line these don't exist yet for some reason
        if (isset($tikilib) && method_exists($tikilib, "get_preference")) {
            global $url_host;
            $this->trusted_uri[] = '#' . preg_quote("http://$url_host", '$#') . '#';
            $this->trusted_uri[] = '#' . preg_quote("https://$url_host", '$#') . '#';

            $allowed_tags = array_filter($tikilib->get_preference('smarty_security_allowed_tags', [], true));
            $disabled_tags = array_filter($tikilib->get_preference('smarty_security_disabled_tags', [], true));
            $allowed_modifiers = array_filter($tikilib->get_preference('smarty_security_allowed_modifiers', [], true));
            $disabled_modifiers = array_filter($tikilib->get_preference('smarty_security_disabled_modifiers', [], true));
            $dirs = array_filter($tikilib->get_preference('smarty_security_dirs', [], true));

            $cdns = preg_split('/\s+/', $tikilib->get_preference('tiki_cdn', ''));
            $cdns_ssl = preg_split('/\s+/', $tikilib->get_preference('tiki_cdn_ssl', ''));
            $cdn_uri = array_filter(array_merge($cdns, $cdns_ssl));
            foreach ($cdn_uri as $uri) {
                $this->trusted_uri[] = '#' . preg_quote($uri) . '$#';
            }
        } else {
            $allowed_tags = [];
            $disabled_tags = [];
            $allowed_modifiers = [];
            $disabled_modifiers = [];
            $dirs = [];
        }

        // Add defaults
        $this->allowed_tags = $allowed_tags;
        $this->disabled_tags = $disabled_tags;
        $this->allowed_modifiers = $allowed_modifiers;
        $this->disabled_modifiers = $disabled_modifiers;
        $this->secure_dir = array_merge($this->secure_dir, $dirs);
    }
}

/**
 * extends \Smarty\Smarty
 *
 * Centralizing overrides here will avoid problems when upgrading to newer versions of the Smarty library.
 * @package TikiWiki\lib\init
 */
class Smarty_Tiki extends \Smarty\Smarty
{
    /**
     * needs a proper description
     * @var array|null
     */
    public $url_overriding_prefix_stack = null;
    /**
     * needs a proper description
     * @var null
     */
    public $url_overriding_prefix = null;
    /**
     * needs a proper description
     * @var null|string
     */
    public $main_template_dir = null;

    private $customErrorHandler;

    private function activateCustomErrorHandler()
    {
        $this->customErrorHandler->activate();
    }
    private function deactivateCustomErrorHandler()
    {
        $this->customErrorHandler->deactivate();
    }
    /**
     * needs a proper description
     */
    public function __construct()
    {
        parent::__construct();
        global $prefs, $base_uri;

        $this->customErrorHandler = new SmartyTikiErrorHandler();
        $this->initializePaths();

        // Extensions order must be like to this override smarty default extension
        $this->setExtensions([
            new Smarty\Extension\CoreExtension(),
            new SmartyTiki\Extension\SmartyTikiExtension(),
            new Smarty\Extension\DefaultExtension(),
        ]);

        $this->setConfigDir(null);
        if (! isset($prefs['smarty_compilation'])) {
            $prefs['smarty_compilation'] = '';
        }
        $this->compile_check = ( $prefs['smarty_compilation'] != 'never' );
        $this->force_compile = ( $prefs['smarty_compilation'] == 'always' );
        $this->assign('app_name', 'Tiki');

        // DO NOT USE THIS, this has been added 2023-06-14 for backward compatibility with existing custom mail templates and will be removed in future versions
        if (class_exists('TikiLib') && $tikilib = TikiLib::lib('tiki')) {
            $this->assign('mail_machine', $tikilib->tikiUrl());
            $this->assign('mail_machine_raw', $tikilib->tikiUrl());
        }

        // sets the default security class, even if is Security not enabled
        // this is the class to be used when you call enableSecurity() without arguments
        $this->security_class = 'Tiki_Security_Policy';

        if (! isset($prefs['smarty_security']) || $prefs['smarty_security'] == 'y') {
            $this->enableSecurity();
        } else {
            $this->disableSecurity();
        }
        $this->use_sub_dirs = false;
        $this->url_overriding_prefix_stack = [];

        include_once(__DIR__ . '/../smarty_tiki/resource.tplwiki.php');
        $this->registerResource('tplwiki', new Smarty_Resource_Tplwiki());

        include_once(__DIR__ . '/../smarty_tiki/resource.wiki.php');
        $this->registerResource('wiki', new Smarty_Resource_Wiki());

        global $prefs;
        // Assign the prefs array in smarty, by reference
        $this->assign_by_ref('prefs', $prefs);
    }

    /**
     * Fetch templates from plugins (smarty plugins, wiki plugins, modules, ...) that may need to :
     * - temporarily override some smarty vars,
     * - prefix their self_link / button / query URL arguments
     *
     * @param      $_smarty_tpl_file
     * @param null $override_vars
     *
     * @return string
     */
    public function plugin_fetch($_smarty_tpl_file, &$override_vars = null)
    {
        $smarty_orig_values = [];
        if (is_array($override_vars)) {
            foreach ($override_vars as $k => $v) {
                $smarty_orig_values[ $k ] = $this->getTemplateVars($k);
                $this->assign($k, $override_vars[ $k ]);
            }
        }

        $return = $this->fetch($_smarty_tpl_file);

        // Restore original values of smarty variables
        if (count($smarty_orig_values) > 0) {
            foreach ($smarty_orig_values as $k => $v) {
                $this->assign($k, $smarty_orig_values[ $k ]);
            }
        }

        unset($smarty_orig_values);
        return $return;
    }

    /**
     * needs a proper description
     * @param null $_smarty_tpl_file
     * @param null $_smarty_cache_id
     * @param null $_smarty_compile_id
     * @param null $parent
     * @param bool $_smarty_display
     * @param bool $merge_tpl_vars
     * @param bool $no_output_filter
     * @return string
     */
    public function fetch($_smarty_tpl_file = null, $_smarty_cache_id = null, $_smarty_compile_id = null, $parent = null, $_smarty_display = false, $merge_tpl_vars = true, $no_output_filter = false)
    {
        $this->activateCustomErrorHandler();

        $this->refreshLanguage();

        $this->assign_layout_sections($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);

        $_smarty_tpl_file = $this->get_filename($_smarty_tpl_file);
        $html = '';
        try {
            if ($_smarty_display) {
                //Probably nothing uses this.  Code must have been copy-pasted in the past.  Display cannot return anything...
                parent::display($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);
            } else {
                $html = parent::fetch($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent);
            }
        } catch (Error $e) {
            TikiLib::lib('errortracking')->captureException($e);
            $html = '<div class="error">';
            $html .= "Fatal error rendering template file $_smarty_tpl_file\n<br/>";
            $html .= '</div><pre>';
            $html .= $e;
            $html .= '</pre>';
        }

        $this->deactivateCustomErrorHandler();
        return $html;
    }

    /**
     * Clears the value of an assigned variable
     * @param $var mixed
     * @return Smarty_Internal_Data
     */
    public function clear_assign($var)
    {
        return parent::clearAssign($var);
    }

    /**
     * This is used to assign() values to the templates by reference instead of making a copy.
     * @param $var string
     * @param $value mixed
     * @return Smarty\Variable
     */
    public function assign_by_ref($var, &$value)
    {
        $variable = new Smarty\Variable(null);
        $variable->value = &$value;
        return $this->tpl_vars[$var] = $variable;
    }

    /**
     * fetch in a specific language  without theme consideration
     * @param      $lg
     * @param      $_smarty_tpl_file
     * @param null $_smarty_cache_id
     * @param null $_smarty_compile_id
     * @param bool $_smarty_display
     * @return mixed
     */
    public function fetchLang($lg, $_smarty_tpl_file, $_smarty_cache_id = null, $_smarty_compile_id = null)
    {
        global $prefs;

        $_smarty_tpl_file = $this->get_filename($_smarty_tpl_file);

        $lgSave = $prefs['language'];
        $prefs['language'] = $lg;
        $this->refreshLanguage();
        $res = parent::fetch($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id);
        $prefs['language'] = $lgSave; // Restore the language of the user triggering the notification
        $this->refreshLanguage();

        return preg_replace("/^[ \t]*/", '', $res);
    }

    /**
     * Tiki wrapper for Smarty display (displays a template)
     * *
     * * @param string $template the resource handle of the template file or template object
     * * @param mixed $cache_id cache id to be used with this template
     * * @param mixed $compile_id compile id to be used with this template
     * *
     * * @return void
     *
     * @throws \Smarty\Exception
     * @throws \Exception
     * */
    public function display(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $content_type = 'text/html; charset=utf-8'
    ): void {

        global $prefs;
        $this->activateCustomErrorHandler();

        if (! empty($prefs['feature_htmlpurifier_output']) and $prefs['feature_htmlpurifier_output'] == 'y') {
            static $loaded = false;
            static $purifier = null;
            if (! $loaded) {
                require_once('lib/htmlpurifier_tiki/HTMLPurifier.tiki.php');
                $config = getHTMLPurifierTikiConfig();
                $purifier = new HTMLPurifier($config);
                $loaded = true;
            }
        }

        /**
         * Add security headers. By default there headers are not sent.
         * To change go to admin > security > site access
         */
        if (! headers_sent()) {
            if (! isset($prefs['http_header_frame_options'])) {
                $frame = false;
            } else {
                $frame = $prefs['http_header_frame_options'];
            }
            if (! isset($prefs['http_header_xss_protection'])) {
                $xss = false;  // prevent smarty E_NOTICE
            } else {
                $xss = $prefs['http_header_xss_protection'];
            }

            if (! isset($prefs['http_header_content_type_options'])) {
                $content_type_options = false;  // prevent smarty E_NOTICE
            } else {
                $content_type_options = $prefs['http_header_content_type_options'];
            }

            if (! isset($prefs['http_header_content_security_policy'])) {
                $content_security_policy = false;  // prevent smarty E_NOTICE
            } else {
                $content_security_policy = $prefs['http_header_content_security_policy'];
            }

            if (! isset($prefs['http_header_strict_transport_security'])) {
                $strict_transport_security = false;  // prevent smarty E_NOTICE
            } else {
                $strict_transport_security = $prefs['http_header_strict_transport_security'];
            }

            if (! isset($prefs['http_header_public_key_pins'])) {
                $public_key_pins = false;  // prevent smarty E_NOTICE
            } else {
                $public_key_pins = $prefs['http_header_public_key_pins'];
            }

            if ($frame == 'y') {
                    $header_value = $prefs['http_header_frame_options_value'];
                    header('X-Frame-Options: ' . $header_value);
            }
            if ($xss == 'y') {
                    $header_value = $prefs['http_header_xss_protection_value'];
                    header('X-XSS-Protection: ' . $header_value);
            }
            if ($content_type_options == 'y') {
                header('X-Content-Type-Options: nosniff');
            }
            if ($content_security_policy == 'y') {
                $header_value = $prefs['http_header_content_security_policy_value'];
                header('Content-Security-Policy: ' . $header_value);
            }

            if ($strict_transport_security == 'y') {
                $header_value = $prefs['http_header_strict_transport_security_value'];
                header('Strict-Transport-Security: ' . $header_value);
            }

            if ($public_key_pins == 'y') {
                $header_value = $prefs['http_header_public_key_pins_value'];
                header('Public-Key-Pins: ' . $header_value);
            }
        }

        /**
         * By default, display is used with text/html content in UTF-8 encoding
         * If you want to output other data from smarty,
         * - either use fetch() / fetchLang()
         * - or set $content_type to '' (empty string) or another content type.
         */
        if ($content_type != '' && ! headers_sent()) {
            header('Content-Type: ' . $content_type);
        }

        if (function_exists('current_object') && $obj = current_object()) {
            $attributes = TikiLib::lib('attribute')->get_attributes($obj['type'], $obj['object']);
            if (isset($attributes['tiki.object.layout'])) {
                $prefs['site_layout'] = $attributes['tiki.object.layout'];
            }
        }

        $this->refreshLanguage();

        TikiLib::events()->trigger('tiki.process.render', []);

        $this->assign_layout_sections($template, $cache_id, $compile_id, $parent);
        try {
            parent::display($template, $cache_id, $compile_id);
        } catch (Error $e) {
            TikiLib::lib('errortracking')->captureException($e);
            $html = '<div class="error">';
            $html .= "Fatal error rendering template resource $template\n<br/>";
            $html .= '</div><pre>';
            $html .= $e;
            $html .= '</pre>';
            echo $html;
        }

        $this->deactivateCustomErrorHandler();
    }

    /**
     * Since Smarty 3.1.23, display no longer calls fetch function, so we need to have this Tiki layout section assignment
     * and modules loading called in both places
     */
    private function assign_layout_sections($_smarty_tpl_file, $_smarty_cache_id, $_smarty_compile_id, $parent)
    {
        global $prefs;

        if (($tpl = $this->getTemplateVars('mid')) && ( $_smarty_tpl_file == 'tiki.tpl' || $_smarty_tpl_file == 'tiki-print.tpl' || $_smarty_tpl_file == 'tiki_full.tpl' )) {
            // Set the last mid template to be used by AJAX to simulate a 'BACK' action
            if (isset($_SESSION['last_mid_template'])) {
                $this->assign('last_mid_template', $_SESSION['last_mid_template']);
                $this->assign('last_mid_php', $_SESSION['last_mid_php']);
            }
            $_SESSION['last_mid_template'] = $tpl;
            $_SESSION['last_mid_php'] = $_SERVER['REQUEST_URI'];

            // set the first part of the browser title for admin pages
            if (null === $this->getTemplateVars('headtitle')) {
                $script_name = basename($_SERVER['SCRIPT_NAME']);
                if ($script_name === 'route.php' && ! empty($inclusion)) {
                    $script_name = $inclusion;
                }
                if ($script_name != 'tiki-admin.php' && strpos($script_name, 'tiki-admin') === 0) {
                    $str = substr($script_name, 10, strpos($script_name, '.php') - 10);
                    $str = ucwords(trim(str_replace('_', ' ', $str)));
                    $this->assign('headtitle', 'Admin ' . $str);
                } elseif (strpos($script_name, 'tiki-list') === 0) {
                    $str = substr($script_name, 9, strpos($script_name, '.php') - 9);
                    $str = ucwords(trim(str_replace('_', ' ', $str)));
                    $this->assign('headtitle', 'List ' . $str);
                } elseif (strpos($script_name, 'tiki-view') === 0) {
                    $str = substr($script_name, 9, strpos($script_name, '.php') - 9);
                    $str = ucwords(trim(str_replace('_', ' ', $str)));
                    $this->assign('headtitle', 'View ' . $str);
                } elseif ($prefs['urlIndex'] && strpos($script_name, $prefs['urlIndex']) === 0) {
                    $this->assign('headtitle', tra($prefs['urlIndexBrowserTitle']));    // Viewing Custom Homepage
                } else { // still not set? guess...
                    $str = str_replace(['tiki-', '.php', '_'], ['', '', ' '], $script_name);
                    $str = ucwords($str);
                    $this->assign('headtitle', tra($str));  // for files where no title has been set or can be reliably calculated - translators: please add comments here as you find them
                }
            }

            if ($_smarty_tpl_file == 'tiki-print.tpl') {
                $this->assign('print_page', 'y');
            }
            $data = $this->fetch($tpl, $_smarty_cache_id, $_smarty_compile_id, $parent);//must get the mid because the modules can overwrite smarty variables

            $this->assign('mid_data', $data);
        } elseif ($_smarty_tpl_file == 'confirm.tpl' || $_smarty_tpl_file == 'error.tpl' || $_smarty_tpl_file == 'error_ticket.tpl' || $_smarty_tpl_file == 'error_simple.tpl') {
            if (! empty(ob_get_status())) {
                ob_end_clean(); // Empty existing Output Buffer that may have been created in smarty before the call of this confirm / error* template
            }
            if ($prefs['feature_obzip'] == 'y') {
                ob_start('ob_gzhandler');
            }
        }

        if (! defined('TIKI_IN_INSTALLER') && ! defined('TIKI_IN_TEST')) {
            require_once 'tiki-modules.php';
        }
        $this->assign('TIKI_IN_INSTALLER', defined('TIKI_IN_INSTALLER'));
    }

    /**
     * Returns the file path associated to the template name
     * Check if the path is a template inside one of the template dirs and not an arbitrary file
     * @param $template
     * @return string
     */
    public function get_filename($template)
    {
        if (substr($template, 0, 5) === 'file:') {
            $template = substr($template, 5);
        }

        // could be extends: or something else?
        if (preg_match('/^[a-z]+\:/', $template)) {
            return $template;
        }

        //get the list of template directories
        $dirs = array_merge(
            $this->getTemplateDir(),
            [TEMP_CACHE_PATH],
            $this->security_policy ? array_map('realpath', $this->security_policy->secure_dir) : []
        );

        // sanity check
        if (file_exists($template)) {
            $valid_path = false;
            foreach ($dirs as $dir) {
                $dirPath = realpath($dir);
                if ($dirPath === false) {
                    continue;
                }

                if (strpos(realpath($template), $dirPath) === 0) {
                    $valid_path = true;
                    break;
                }
            }
            if (! $valid_path) {
                Feedback::error(tr("Invalid template name: %0", $template));
                return "";
            }
            return $template;
        }

        //go through directories in search of the template
        foreach ($dirs as $dir) {
            if (file_exists($dir . $template)) {
                return $dir . $template;
            }
        }
        return "";
    }

    /**
     * needs a proper description
     * @param $url_arguments_prefix
     * @param $arguments_list
     */
    public function set_request_overriders($url_arguments_prefix, $arguments_list)
    {
        $this->url_overriding_prefix_stack[] = [ $url_arguments_prefix . '-', $arguments_list ];
        $this->url_overriding_prefix =& $this->url_overriding_prefix_stack[ count($this->url_overriding_prefix_stack) - 1 ];
    }

    /**
     * needs a proper description
     * @param $url_arguments_prefix
     * @param $arguments_list
     */
    public function remove_request_overriders($url_arguments_prefix, $arguments_list)
    {
        $last_override_prefix = empty($this->url_overriding_prefix_stack) ? false : array_pop($this->url_overriding_prefix_stack);
        if (! is_array($last_override_prefix) || $url_arguments_prefix . '-' != $last_override_prefix[0]) {
            trigger_error('URL Overriding prefix stack is in a bad state', E_USER_ERROR);
        }
        $this->url_overriding_prefix =& $this->url_overriding_prefix_stack[ count($this->url_overriding_prefix_stack) - 1 ];
        ;
    }

    public function refreshLanguage()
    {
        global $tikidomain, $prefs;

        $lang = $prefs['language'];
        if (empty($lang)) {
            $lang = 'default';
        }

        if (! empty($prefs['site_layout'])) {
            $layout = $prefs['site_layout'];
        } else {
            $layout = 'classic';
        }

        $this->setCompileId("$lang-$tikidomain-$layout");
        $this->initializePaths();
    }

    /**
     * Call addTemplateDir on:
     * - $templatePath/layouts/configured_site_layout_or_site_layout_admin
     *    - Falls back to $templatePath/layouts/basic/
     * @param [type] $templatePath
     * @return void
     */
    private function addLayoutTemplatesFromTemplatePath($templatePath): void
    {
        global $prefs, $section;
        if ($section != "admin") {
            $selectedLayout = $prefs['site_layout'] ?? $prefs['site_layout_admin'] ?? 'basic';
        } else {
            $selectedLayout = $prefs['site_layout_admin'] ?? $prefs['site_layout'] ?? 'basic';
        }

        $layout = TIKI_PATH . "/$templatePath/" . 'layouts/' . $selectedLayout . '/';
        if (! is_readable($layout)) {
            $layout = TIKI_PATH . "/$templatePath/" . 'layouts/basic/';
        }
        if (is_readable($layout)) {
            $this->addTemplateDir($layout);
            //This is just to find layout_plain.tpl
            $this->addTemplateDir(TIKI_PATH . "/$templatePath/" . 'layouts/');
        }
    }

    /**
    Add smarty template paths from where tpl files should be loaded. This function also gets called from lib/setup/theme.php to initialize theme specific paths.  It's dependent on

    $prefs['theme'], $prefs['theme_option'], $prefs['site_layout'], $prefs['site_layout_admin']

    The load order for main templates is
    - theme_option path
    - theme path
    - themes/templates/
    - tikidomain path
    - tiki extension modules templates/
    - templates/ (at project root)


    The effective template will be the one present in the last directory loaded.
    */
    public function initializePaths(): void
    {
        global $prefs, $tikidomainslash, $section;

        if (! $this->main_template_dir) {
            // First run only
            $this->main_template_dir = TIKI_PATH . '/' . SMARTY_TEMPLATES_PATH . '/';
            $this->setCompileDir(TIKI_PATH . '/' . SMARTY_COMPILED_TEMPLATES_PATH);
        }
        //Initialize smarty template dir system, without adding a template.Keep in mind that smarty will use the first template found.  So generally speaking we add the most specific paths first.
        $this->setTemplateDir([]);

        // when called from release.php TikiLib isn't initialised so we can ignore the themes and addons
        if (class_exists('TikiLib')) {
            // Theme templates
            $themelib = TikiLib::lib('theme');
            $theme = $prefs['theme'] ?? null;
            $themeOption = $prefs['theme_option'] ?? null;
            if (! in_array($theme, ['custom_url'])) {
                //Templates from theme_options of currently active theme
                if ($themeOption) {
                    $currentThemeOptionTemplatesPath = $themelib->getThemePath($theme, $themeOption, SMARTY_TEMPLATES_PATH_FRAGMENT, true); // path to the theme options
                    if ($currentThemeOptionTemplatesPath) {
                        $this->addTemplateDir(TIKI_PATH . "/$currentThemeOptionTemplatesPath/");
                        $this->addLayoutTemplatesFromTemplatePath($currentThemeOptionTemplatesPath);
                    }
                }

                //Templates from currently active theme
                //This will fallback to 'default' theme if $theme is empty
                $currentThemeTemplatesPath = $themelib->getThemePath($theme, '', SMARTY_TEMPLATES_PATH_FRAGMENT, true); // path to the currently active main theme templates
                if ($currentThemeTemplatesPath) {
                    $this->addTemplateDir(TIKI_PATH . "/$currentThemeTemplatesPath/");
                    $this->addLayoutTemplatesFromTemplatePath($currentThemeTemplatesPath);
                }
            }


            if (! empty($tikidomainslash)) {
                // Legacy /themes/mydomain.tld/templates/
                $this->addTemplateDir(TIKI_PATH . "/themes/{$tikidomainslash}templates/");
                // Legacy /templates/mydomain.tld/
                $this->addTemplatedir($this->main_template_dir . '/' . $tikidomainslash);
            }

            //Shared templates in _custom/shared/templates
            $this->addTemplateDir(TIKI_PATH . "/" . TIKI_CUSTOMIZATIONS_SHARED_TEMPLATES_PATH . '/');

            //Legacy custom templates for all sites
            $this->addTemplateDir(TIKI_PATH . '/' . THEMES_LEGACY_ALL_SITES_SHARED_TEMPLATES_PATH . '/');

            //Templates from addon packages
            foreach (\Tiki\Package\ExtensionManager::getPaths() as $path) {
                $this->addTemplateDir($path . '/templates/');
            }
        }

        //Base tiki templates
        $this->addLayoutTemplatesFromTemplatePath(SMARTY_TEMPLATES_PATH);
        $this->addTemplateDir($this->main_template_dir);

        //Test templates
        $this->addTemplateDir(TIKI_PATH . '/lib/test/core/Search/');
        //var_dump($this->getTemplateDir());
    }

    /**
     * When calling directly smarty functions, from PHP, you need to provide a object of type \Smarty\Template
     * The method signature for smarty functions is: smarty_function_xxxx($params, \Smarty\Template $template)
     *
     * @return \Smarty\Template
     */
    public function getEmptyInternalTemplate()
    {
        global $prefs;
        $tpl = new \Smarty\Template('empty', $this);
        $tpl->assign('app_name', $this->getTemplateVars('app_name'));

        // DO NOT USE THIS, this has been added 2023-06-14 for backward compatibility with existing custom mail templates and will be removed in future versions
        if (class_exists('TikiLib') && $tikilib = TikiLib::lib('tiki')) {
            $tpl->assign('mail_machine', $tikilib->tikiUrl());
            $tpl->assign('mail_machine_raw', $tikilib->tikiUrl());
        }

        $tpl->assign('prefs', $prefs);
        return $tpl;
    }

    /**
     * Checks a wiki page permissions are ok to use as a template and returns the page info if all ok
     *
     * @param string      $page
     * @param string|null $tpl_source
     *
     * @return array|null
     * @throws Exception
     */
    final public function checkWikiPageTemplatePerms(string $page, ?string &$tpl_source): ?array
    {
        global $tikilib;

        $perms = Perms::get([ 'type' => 'wiki page', 'object' => $page ]);
        if (! $perms->use_as_template) {
            $tpl_source = tra('Permission denied: the specified wiki page cannot be used as Smarty template resource') . '<br />';
            // TODO: do not cache ! and return the message only once should be enough...
            return null;
        }

        // check perms for non-admin editors but only show to admins
        if ($perms->admin_wiki) {
            $loaded = $perms->getResolver()->dump();
            $nonAdminEditorGroups = [];
            if (isset($loaded['perms']['edit']) && is_array($loaded['perms']['edit'])) {
                foreach ($loaded['perms']['edit'] as $editorGroup) {
                    if ($editorGroup !== 'Admins' && is_array($loaded['perms']['admin_wiki']) && ! in_array($editorGroup, $loaded['perms']['admin_wiki'])) {
                        $nonAdminEditorGroups[] = $editorGroup;
                    }
                }
            }
            if ($nonAdminEditorGroups) {
                $groupString = implode(', ', $nonAdminEditorGroups);
                $pageLink = '<a href="' . smarty_modifier_sefurl($page) . '" class="alert-link">' . $page . '</a>';
                if (count($nonAdminEditorGroups) > 1) {
                    $message = 'The %0 groups can edit this template page %1 but are not wiki administrators';
                    $groupString = substr_replace($groupString, tr(' and'), strrpos($groupString, ','), 1);
                } else {
                    $message = 'The %0 group can edit this template page %1 but is not a wiki administrator';
                }
                Feedback::warning(tr($message, $groupString, $pageLink));
            }
        }

        $info = $tikilib->get_page_info($page);

        if (empty($info)) {
            return null;
        } else {
            return (array) $info;
        }
    }
}
