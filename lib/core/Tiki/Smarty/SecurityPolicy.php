<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Smarty;

use TikiLib;

/**
 * extends \Smarty\Security
 * @package TikiWiki\lib\core\Tiki\Smarty
 */
class SecurityPolicy extends \Smarty\Security
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
        // Add WEBSERVICE_TEMPLATES_PATH to secure_dir for dynamically generated webservice templates
        $webserviceTemplatesPath = defined('WEBSERVICE_TEMPLATES_PATH') ? WEBSERVICE_TEMPLATES_PATH : null;
        $this->secure_dir = array_merge(
            $this->secure_dir,
            $dirs,
            $webserviceTemplatesPath ? [$webserviceTemplatesPath] : []
        );
    }

    public function isTrustedModifier($modifier_name, $compiler)
    {
        // TikiLib is absent when templates are compiled outside a full Tiki bootstrap
        // (doc/devtools/check_smarty_syntax.php): fall back to the default policy.
        if (class_exists('TikiLib') && ($tikilib = TikiLib::lib('tiki'))) {
            $allowed_builtin_php_functions = array_filter($tikilib->get_preference('smarty_security_allowed_builtin_php_functions', [], true));
            if (in_array($modifier_name, $allowed_builtin_php_functions)) {
                return true;
            }
        }

        return parent::isTrustedModifier($modifier_name, $compiler);
    }

    /**
     * Work around a bug in smarty where _updateResourceDir doesn't clear the values correctly when
     * smarty templateDir has been updated
     */
    public function clearResourceDir()
    {
        $this->_resource_dir = [];
    }
}
