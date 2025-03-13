<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//This happens really early in tiki init, autoloading doesn't seem to be available yet
require_once('lib/core/Cache/KvpCacheInterface.php');
/**
 * \brief This is a library to handle all caching in Tiki.
 *
 * In abstracts a key value pair cache based on Memcache or Redis, with a fallback to the filesystem
 *
 * It also manages template caching, opcode caching, etc...
 */
class Cachelib
{
    private $implementation;

    private const OPTIONAL_SUBSYSTEMS = [
        'Memcache' => [
            'pref' => 'memcache_enabled',
            'extension' => 'memcached',
            'class' => CacheLibMemcache::class,
            'require' => ''
        ],
        'Redis' => [
            'pref' => 'redis_enabled',
            'extension' => 'redis',
            'class' => CacheLibRedis::class,
            'require' => 'lib/cache/redislib.php'
        ],
    ];

    public function __construct()
    {
        global $prefs;

        foreach (self::OPTIONAL_SUBSYSTEMS as $subsystem => $params) {
            if (
                ($prefs[$params['pref']] ?? 'n') === 'y'
                && (empty($params['extension']) || extension_loaded($params['extension']))
            ) {
                if (! empty($params['require'])) {
                    require_once($params['require']);
                }
                $implementation = new $params['class']();
                if ($implementation->isFunctional()) {
                    $this->implementation = $implementation;
                    return;
                }
            }
        }
        // Default implementation and fallback
        $this->implementation = new CacheLibFileSystem();
    }

    public function replaceImplementation($implementation)
    {
        $old = $this->implementation;
        $this->implementation = $implementation;

        return $old;
    }

    public function cacheItem($key, $data, $type = '')
    {
        return $this->implementation->cacheItem($key, $data, $type);
    }

    public function isCached($key, $type = '')
    {
        return $this->implementation->isCached($key, $type);
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return $this->implementation->getCached($key, $type, $lastModif);
    }

    public function getSerialized($key, $type = '', $lastModif = false)
    {
        $data = $this->getCached($key, $type, $lastModif);

        if ($data) {
            return @unserialize($data);
        }
    }

    public function invalidate($key, $type = '')
    {
        return $this->implementation->invalidate($key, $type);
    }

    /**
     * Empty one or more caches
     *
     * Checks for existance of libs because it's called from the installer
     *
     * @param mixed $dir_names      all|templates_c|temp_cache|temp_public|modules_cache|prefs (default all)
     * @param string $log_section   Type of log message. Default 'system'
     */
    public function empty_cache($dir_names = ['all'], $log_section = 'system')
    {
        global $tikidomain, $prefs;
        $logslib = TikiLib::lib('logs');

        $skipLog = defined('TIKI_IN_INSTALLER') // If this is being executed during the installer
            || (defined('DB_STATUS') && ! DB_STATUS); // or from command line, without a working DB

        if (! is_array($dir_names)) {
            $dir_names = [$dir_names];
        }
        if (in_array('all', $dir_names)) {
            $this->erase_dir_content(SMARTY_COMPILED_TEMPLATES_PATH . "/$tikidomain");
            $this->erase_dir_content(TEMP_PUBLIC_PATH . "/$tikidomain");
            $this->erase_dir_content(TEMP_CACHE_PATH . "/$tikidomain");

            $banner = glob(TEMP_PATH . "/banner*.*");
            array_map('unlink', $banner);

            $banner = glob(TEMP_PATH . "/TMPIMG*");
            array_map('unlink', $banner);

            $this->flush_opcode_cache();
            $this->flush_memcache();
            $this->flush_redis();
            $this->invalidate('global_preferences');
            if (! $skipLog) {
                $logslib->add_log($log_section, 'erased all cache content');
            }
        }
        if (in_array('templates_c', $dir_names)) {
            $this->erase_dir_content(SMARTY_COMPILED_TEMPLATES_PATH . "/$tikidomain");
            $this->flush_opcode_cache();
            if (! $skipLog) {
                $logslib->add_log($log_section, 'erased templates_c content');
            }
        }
        if (in_array('temp_cache', $dir_names)) {
            $this->erase_dir_content(TEMP_CACHE_PATH . "/$tikidomain");
            // Next case is needed to clean also cached data created through mod PluginR
            if ((isset($prefs['wikiplugin_rr']) && $prefs['wikiplugin_rr'] == 'y') or (isset($prefs['wikiplugin_r']) && $prefs['wikiplugin_r'] == 'y')) {
                $this->erase_dir_content(TEMP_CACHE_PATH . "/$tikidomain/R_*/");
            }
            $this->flush_redis();
            if (! $skipLog) {
                $logslib->add_log($log_section, 'erased temp/cache content');
            }
        }
        if (in_array('temp_public', $dir_names)) {
            $this->erase_dir_content(TEMP_PUBLIC_PATH . "/$tikidomain");
            if (! $skipLog) {
                $logslib->add_log($log_section, 'erased ' . TEMP_PUBLIC_PATH . ' content');
            }
        }
        if (in_array('prefs', $dir_names)) {
            $this->invalidate('global_preferences');
        }
    }

    public function invalidateAll($type)
    {
        return $this->implementation->invalidateAll($type);
    }

    public function count_cache_files($path, $begin = null)
    {
        global $tikidomain;

        if (! $path or ! is_dir($path)) {
            return (['total' => 0,'cant' => 0]);
        }

        $total = 0;
        $cant = 0;
        $back = [];
        $all = opendir($path);

        // If using multiple Tikis but flushing cache on default install...
        if (empty($tikidomain) && is_file(TIKI_CONFIG_PATH . '/virtuals.inc')) {
            $virtuals = array_map('trim', file(TIKI_CONFIG_PATH . '/virtuals.inc'));
        } else {
            $virtuals = false;
        }

        while ($file = readdir($all)) {
            if (
                substr($file, 0, 1) == "." or
                    $file == 'CVS' or
                    $file == '.svn' or
                    $file == "index.php" or
                    $file == "README" or
                    $file == "web.config" or
                    ($virtuals && in_array($file, $virtuals))
            ) {
                continue;
            }

            if (is_dir($path . '/' . $file) and $file <> ".." and $file <> "." and $file <> "CVS" and $file <> ".svn") {
                $du = $this->count_cache_files($path . '/' . $file);
                $total += $du['total'];
                $cant += $du['cant'];
                unset($file);
            } elseif (! is_dir($path . '/' . $file)) {
                if (isset($begin) && substr($file, 0, strlen($begin)) != $begin) {
                    continue; // the file name doesn't begin with the good beginning
                }
                $stats = @stat($path . '/' . $file); // avoid the warning if safe mode on
                $total += $stats['size'];
                $cant++;
                unset($file);
            }
        }
        closedir($all);
        unset($all);
        $back['total'] = $total;
        $back['cant'] = $cant;
        return $back;
    }

    public function flush_opcode_cache()
    {
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache();
        }

        if (function_exists('xcache_clear_cache') && ! ini_get('xcache.admin.enable_auth')) {
            foreach (range(0, xcache_count(XC_TYPE_PHP) - 1) as $index) {
                xcache_clear_cache(XC_TYPE_PHP, $index);
            }
        }
    }

    /**
     * Flush memcache if endabled
     *
     * @return void
     */
    public function flush_memcache()
    {
        global $prefs;

        if (isset($prefs['memcache_enabled']) && $prefs['memcache_enabled'] === 'y' && ($this->implementation instanceof CacheLibMemcache)) {
            $memcachelib = TikiLib::lib("memcache");
            if ($memcachelib->isFunctional()) {
                $memcachelib->flush();
            }
        }
        return;
    }

    public function flush_redis()
    {
        global $prefs;

        if (isset($prefs['redis_enabled']) && $prefs['redis_enabled'] === 'y' && $this->implementation instanceof CacheLibRedis) {
            $this->implementation->flush();
        }
        return;
    }

    private function erase_dir_content($path)
    {
        global $tikidomain, $prefs;

        $path = rtrim($path, '/');
        if (! $path or ! is_dir($path)) {
            return 0;
        }
        if ($dir = opendir($path)) {
            // If using multiple Tikis but flushing cache on default install...
            if (empty($tikidomain) && is_file(TIKI_CONFIG_PATH . '/virtuals.inc')) {
                $virtuals = array_map('trim', file(TIKI_CONFIG_PATH . '/virtuals.inc'));
            } else {
                $virtuals = false;
            }

            // Next case is needed to clean also cached data created through mod PluginR
            if (
                (isset($prefs['wikiplugin_rr']) && $prefs['wikiplugin_rr'] == 'y') ||
                (isset($prefs['wikiplugin_r']) && $prefs['wikiplugin_r'] == 'y')
            ) {
                // .RData case needed to clean also cached data created through mod PluginR
                $extracheck = 'RData';
            } else {
                $extracheck = '';
            }

            // Folders created by unoconv/libreoffice that should be removed
            $unoconvFolders = ['.cache', '.config'];
            while (false !== ($file = readdir($dir))) {
                if (
                    ( substr($file, 0, 1) == "." && substr($file, -5) != $extracheck ) or
                    $file == 'CVS' or
                    $file == '.svn' or
                    $file == "index.php" or
                    $file == "README" or
                    $file == "README.md" or
                    $file == "web.config" or
                    ($virtuals && in_array($file, $virtuals)) and
                    ! in_array($file, $unoconvFolders)
                ) {
                    continue;
                }

                if (is_dir($path . "/" . $file)) {
                    $this->erase_dir_content($path . "/" . $file);
                    @rmdir($path . "/" . $file);    // dir won't be empty if there are multitiki dirs inside
                } else {
                    try {
                        $filePath = $path . "/" . $file;
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    } catch (Error $e) {
                        Feedback::error(tr('Cache file %0 is not writable (%1)', $path . "/" . $file, $e->getMessage()));
                    }
                }
            }
            closedir($dir);
        }
    }

    public function cache_templates($path, $newlang)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        $smarty->refreshLanguage();

        $oldlang = $prefs['language'];
        $prefs['language'] = $newlang;
        if (! $path or ! is_dir($path)) {
            return 0;
        }
        if ($dir = opendir($path)) {
            while (false !== ($file = readdir($dir))) {
                $a = explode(".", $file);
                $ext = strtolower(end($a));
                if (substr($file, 0, 1) == "." or $file == 'CVS') {
                    continue;
                }
                if (is_dir($path . "/" . $file)) {
                    $prefs['language'] = $oldlang;
                    $this->cache_templates($path . "/" . $file, $newlang);
                    $prefs['language'] = $newlang;
                } else {
                    if ($ext == "tpl") {
                        $template_file = substr($path . "/" . $file, 10);
                        try {
                            $_tpl = $smarty->createTemplate($template_file, null, null, null, false);
                            $_tpl->compileTemplateSource();
                        } catch (Exception $e) {
                            $errors_found = true;
                        }
                    }
                }
            }
            closedir($dir);
        }
        $prefs['language'] = $oldlang;
    }

    /**
     * Generates caches for templates, modules and other features.
     *
     * @param mixed $dir_names all|templates_c|modules_cache|misc (default all)
     *
     * @throws Exception
     */
    public function generateCache($dir_names = ['all'])
    {
        if (! is_array($dir_names)) {
            $dir_names = [$dir_names];
        }

        if (in_array('all', $dir_names)) {
            $this->generateTemplateCache();
            $this->generateModuleCache();
            $this->generateMiscCache();
        }
        if (in_array('templates', $dir_names)) {
            $this->generateTemplateCache();
        }
        if (in_array('modules', $dir_names)) {
            $this->generateModuleCache();
        }
        if (in_array('misc', $dir_names)) {
            $this->generateMiscCache();
        }
    }

    /**
     * Compile all Smarty templates
     * @param string $logSection Section to log the request
     */
    protected function generateTemplateCache($logSection = 'system')
    {
        global $prefs;

        $logslib = TikiLib::lib('logs');

        $inInstaller = defined('TIKI_IN_INSTALLER');

        $lang = $prefs['language'];
        $ctempl = 'templates';
        $this->cache_templates($ctempl, $lang);

        if (! $inInstaller) {
            $logslib->add_log($logSection, 'generated templates cache, language = ' . $lang);
        }
    }

    /**
     * Compile all module cache
     * @param string $logSection Section to log the request
     */
    protected function generateModuleCache($logSection = 'system')
    {
        $logslib = TikiLib::lib('logs');
        $modlib = TikiLib::lib('mod');

        $inInstaller = defined('TIKI_IN_INSTALLER');

        $assigned_modules = $modlib->get_assigned_modules();
        foreach ($assigned_modules as $zone => $modules) {
            foreach ($modules as $pos => $module) {
                /** Pre-execute module to cache its content */
                $result = $modlib->execute_module($module);
                if (! $inInstaller) {
                    $logslib->add_log($logSection, 'generated module-cache for ' . $module['name']);
                }
            }
        }
    }

    /**
     * Compile Misc caches like language, categories, users.
     *
     * @param string $logSection
     *
     * @throws Exception
     */
    protected function generateMiscCache($logSection = 'system')
    {
        $logslib = TikiLib::lib('logs');

        $inInstaller = defined('TIKI_IN_INSTALLER');

        TikiLib::lib('language')->list_languages();
        if (! $inInstaller) {
            $logslib->add_log($logSection, 'cached language list');
        }

        TikiLib::lib('categ')->getCategories();
        if (! $inInstaller) {
            $logslib->add_log($logSection, 'cached category list');
        }

        TikiLib::lib('user')->list_all_users();
        TikiLib::lib('user')->list_all_groups();
        TikiLib::lib('user')->list_all_groupIds();
        if (! $inInstaller) {
            $logslib->add_log($logSection, 'cached user/group list');
        }
    }

    public function get_cache_purge_rules($type = 'all')
    {
        if ($this->isCached($type, 'cachepurgerules')) {
            return $this->getSerialized($type, 'cachepurgerules');
        }
        if ($type != 'all') {
            $rules = TikiLib::lib('tiki')->fetchAll("select * from tiki_object_relations where relation = 'tiki.cache.purge' and source_type = ?", [$type]);
        } else {
            $rules = TikiLib::lib('tiki')->fetchAll("select * from tiki_object_relations where relation = 'tiki.cache.purge'");
        }
        $this->cacheItem($type, serialize($rules), 'cachepurgerules');
        return $rules;
    }

    public function get_purge_rules_for_cache($cacheType, $cacheKey)
    {
        return TikiLib::lib('tiki')->fetchAll("select source_type as type, source_itemId as object from tiki_object_relations where relation = 'tiki.cache.purge' and target_type = ? and target_itemId = ?", [$cacheType, $cacheKey]);
    }

    public function clear_purge_rules_for_cache($cacheType, $cacheKey)
    {
        return TikiLib::lib('tiki')->query("delete from tiki_object_relations where relation = 'tiki.cache.purge' and target_type = ? and target_itemId = ?", [$cacheType, $cacheKey]);
    }

    public function set_cache_purge_rule($type, $object, $cacheType, $cacheKey)
    {
        $relationId = TikiLib::lib('relation')->add_relation('tiki.cache.purge', $type, $object, $cacheType, $cacheKey, true);
        if ($relationId) {
            // Rule is added (if it already existed, nothing would have happened)
            $this->invalidate($type, 'cachepurgerules');
        }
        return $relationId;
    }

    public function invalidate_by_cache_purge_rules($args)
    {
        // First get all candidates which match type (source_itemId does not matter for now - see below)
        $cache_purge_rules = $this->get_cache_purge_rules($args['type']);

        foreach ($cache_purge_rules as $c) {
            if ($c['source_itemId'] == $args['object'] || $c['source_itemId'] == 0) {
                TikiLib::lib('cache')->invalidate($c['target_itemId'], $c['target_type']);
            } elseif ($args['type'] != 'wiki page' && $colonpos = strpos($c['source_itemId'], ':')) {
                // Examples: trackeritem:20, trackerId:3, galleryId:5, forum_id:7, parent_id:8 etc...
                $prefix = substr($c['source_itemId'], 0, $colonpos);
                $itemId = substr($c['source_itemId'], $colonpos + 1);
                if (isset($args[$prefix]) && $itemId == $args[$prefix]) {
                    TikiLib::lib('cache')->invalidate($c['target_itemId'], $c['target_type']);
                }
            }
        }
    }

    /**
     * If there is a failure with the caching subsystem, reports the - first - system that failed.
     * If no failures occurred, an empty string is return instead.
     *
     * @return string
     */
    public function reportOptionalSubSystemFail(): string
    {
        global $prefs;

        foreach (self::OPTIONAL_SUBSYSTEMS as $subsystem => $params) {
            if (($prefs[$params['pref']] ?? 'n') === 'y' && ! $this->implementation instanceof $params['class']) {
                return $subsystem;
            }
        }

        return ''; // empty string if no failure found
    }
}

class CacheLibFileSystem implements Tiki\Cache\KvpCacheInterface
{
    public $folder;

    public function __construct()
    {
        global $tikidomain;
        $this->folder = realpath(TEMP_CACHE_PATH);
        if ($tikidomain) {
            $this->folder .= "/$tikidomain";
        }
        if (! is_dir($this->folder)) {
            mkdir($this->folder);
            chmod($this->folder, 0777);
            $resource = opendir($this->folder);
            if ($resource === false) {
                throw new Exception("Unable to create cache directory {$this->folder}");
            }
        }
    }

    public function isFunctional(): bool
    {
        return true;
    }

    public function cacheItem($key, $data, $type = '')
    {
        $key = $type . md5($key);
        @file_put_contents($this->folder . "/$key", $data);
        return true;
    }

    public function isCached($key, $type = '')
    {
        $key = $type . md5($key);
        return is_file($this->folder . "/$key");
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        $key = $type . md5($key);
        $file = $this->folder . "/$key";
        if (is_readable($file)) {
            // If a last date is given for cache validity, make sure the file is younger
            if ($lastModif !== false && filemtime($file) < $lastModif) {
                unlink($file);
                return false;
            }

            return @file_get_contents($file);
        } else {
            return false;
        }
    }

    public function invalidate($key, $type = '')
    {
        $key = $type . md5($key);
        if (is_file($this->folder . "/$key")) {
            unlink($this->folder . "/$key");
        }
    }

    public function invalidateAll($type)
    {
        $path = $this->folder;
        $all = opendir($path);
        if ($all === false) {
            throw new Exception("Unable to open cache directory {$this->folder}");
        }
        while ($file = readdir($all)) {
            if (strpos($file, $type) === 0) {
                unlink("$path/$file");
            }
        }
    }
}

class CacheLibMemcache implements Tiki\Cache\KvpCacheInterface
{
    private function getKey($key, $type)
    {
        return $type . md5($key);
    }

    public function isFunctional(): bool
    {
        return TikiLib::lib("memcache")->isFunctional();
    }

    public function cacheItem($key, $data, $type = '')
    {
        TikiLib::lib("memcache")->set($this->getKey($key, $type), $data);
        return true;
    }

    public function isCached($key, $type = '')
    {
        return false;
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return TikiLib::lib("memcache")->get($this->getKey($key, $type));
    }

    public function invalidate($key, $type = '')
    {
        return TikiLib::lib("memcache")->delete($this->getKey($key, $type));
    }

    public function invalidateAll($type)
    {
        return TikiLib::lib("memcache")->flush();
    }
}

class CacheLibNoCache implements Tiki\Cache\KvpCacheInterface
{
    public function isFunctional(): bool
    {
        return true;
    }

    public function cacheItem($key, $data, $type = '')
    {
        return false;
    }

    public function isCached($key, $type = '')
    {
        return false;
    }

    public function getCached($key, $type = '', $lastModif = false)
    {
        return false;
    }

    public function invalidate($key, $type = '')
    {
        return false;
    }

    public function invalidateAll($type)
    {
        return false;
    }
}
