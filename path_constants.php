<?php

/**
 * The constants in this file will be available from any code where
 * the composer autoloader has been run (which is the vast majority).
 * Some specific utilities may need to require_once this file directly
 *
 * @package Tiki
 * @copyright(c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL public LICENSE . See license . txt for details .
 * /

/*
Important conventions:

- Do NOT compose paths in this file (ex:  TEMP_CACHE_PATH = TEMP_PATH . 'temp'; It must be TEMP_CACHE_PATH = 'temp/cache').  This is so the strings are findable if someone tries to fine what a file found in the filesystem does.

- Directories path must NOT end with a /
*/

const ADMIN_PATH = 'admin';
const BIN_PATH = 'bin';

/** Location of main configuration files */
const TIKI_CONFIG_PATH = 'db';
/** This is the main tiki config file.  This is problematic because it does not take into account multiple domain names, and wasn't introduced with the constants.  See TikiInit.php - benoitg - 2024-01-10  */
const TIKI_CONFIG_FILE_PATH = 'db/local.php';

/** Location of main sql schema files for install.  For historical reasons some upgrade files are here, but do not confuse with TIKI_UPGRADE_SQL_SCHEMA_PATH */
const TIKI_BASE_SQL_SCHEMA_PATH = 'db';
const TIKI_UPGRADE_SQL_SCHEMA_PATH = 'installer/schema';
const DEPRECATED_DEVTOOLS_PATH = 'doc/devtools';

const EXPORT_DUMP_PATH = 'temp/public/dump';
/** I think this is a remnant of Image Gallery (File gallery stores in storage/fgal by default).  benoitg 2023-04-04 */
const DEPRECATED_FILES_PATH = 'files';
const STATIC_IMG_PATH = 'img';
const DEPRECATED_IMG_WIKI_PATH = 'img/wiki';
/** Seems to be a remnant of an old migration to fgal.  See moveWikiUpToFgal() */
const DEPRECATED_IMG_WIKI_UP_PATH = 'img/wiki_up';
/** Most likely remnants of an incomplete refactoring of Tracker/Field/Image.  I don't think this works - benoitg 2023-04-04 */
const TRACKER_FIELD_IMAGE_STORAGE_PATH = 'img/trackers';
const IMG_FLAGNAMES_FILE = 'img/flags/flagnames.php';
/** This is for the webhelp feature, may be obsolete */
const IMG_TIKIHELP_PATH = 'img/tikihelp';
const INSTALLER_PATH = 'installer';


/** Path to the language files distributed with tiki, relative to root */
const LANG_SRC_PATH = 'lang';
/** subdirectory where languages are found in sites, modules, etc. */
const LANG_PATH_FRAGMENT = 'lang';
const LANG_CUSTOM_PHP_BASENAME = 'custom.php';

/** This is actually a route, will disapear in path_rework */
const LISTS_PATH = 'lists';
const LIB_PATH = 'lib';
/** The path for php source code, mostly fro rector and phpstan */
const PHP_SOURCES_PATH = 'src/php';

/** This was for the Mods feature, replaced by packages.  Do not confuse with MODULES_PATH */
const DEPRECATED_MODS_PATH = 'mods';
/** These are the tiki modules https://doc.tiki.org/Module */
const MODULES_PATH = 'modules';

const PERMISSIONCHECK_PATH = 'permissioncheck';
const PROFILES_PATH = 'profiles';

const DEPRECATED_STORAGE_PATH = 'storage';
const STORAGE_PUBLIC_PATH = 'storage/public';
const STORAGE_PREFSDOC_PATH = 'storage/prefsdoc';
/** File gallery default storage path. This is only the default value of the preference, so will not change with this constant */
const FILE_GALLERY_DEFAULT_STORAGE_PATH = 'storage/fgal';
const DEPRECATED_H5P_STORAGE_SUFFIX = 'public/h5p';
/** This is not correct id tikidomains are used.  A new STORAGE_PUBLIC_H5P_PATH should be created with a different handling of tikidomain */
const DEPRECATED_STORAGE_PUBLIC_H5P_PATH = 'storage/public/h5p';
const SMARTY_COMPILED_TEMPLATES_PATH = 'temp/templates_c';
/** Currently this is both the top level template path and the relative template path in themes and packages.  Needs to be separated if moved */
const SMARTY_TEMPLATES_PATH = 'templates';
const SMARTY_BASE_LAYOUTS_PATH = 'templates/layouts';
/** Where are templates are looked for in multitiki sites, etc. */
const SMARTY_TEMPLATES_PATH_FRAGMENT = 'templates';

/** Path to store temporary files.  Use one of the more specific path if possible */
const TEMP_PATH = 'temp';
/** Temporary files that must be servable by the webserver */
const TEMP_HTTP_PUBLIC_PATH = 'temp/public';
const TEMP_CACHE_PATH = 'temp/cache';
const TEMP_PUBLIC_PATH = 'temp/public';
const HTMLPURIFIERCACHE_CACHE_PATH = 'temp/cache/HTMLPurifierCache';

const WIKIPLUGINS_SRC_PATH = 'lib/wiki-plugins';
//Question: where is the string or constant that generates these files?
const WIKIPLUGIN_CACHE_FILES_GLOB = 'temp/cache/wikiplugin_*';

const SATIS_TEMP_PATH = 'temp/satis';
const UNIFIED_INDEX_TEMP_PATH = 'temp/unified-index';
const TEMPLATES_ADMIN_PATH = 'templates/admin';
const TEMPLATES_MODULES_PATH = 'templates/modules';

/** This it for the old tiki_tests system which may not be functional - benoitg - 2023-11-16 */
const TIKI_TESTS_PATH = 'tiki_tests/tests';
const TESTS_PATH = 'tests';

const TIKI_MAIN_CLASS_LOADER_XML_PATH = 'db/config';

/* BEGIN - _custom section */
/** This is where custom themes, php files, etc. are stored, relative to project root */
const TIKI_CUSTOMIZATIONS_SRC_PATH = '_custom';
const TIKI_CUSTOMIZATIONS_SRC_DIST_PATH = '_custom_dist';
/** Compiled themes and assets.  relative to tikiroot */
const TIKI_CUSTOMIZATIONS_PUBLIC_PATH = 'public/generated/_custom';
const TIKI_CUSTOMIZATIONS_SHARED_PATH = '_custom/shared';
const TIKI_CUSTOMIZATIONS_SHARED_PUBLIC_PATH = 'public/generated/_custom/shared';
const TIKI_CUSTOMIZATIONS_SHARED_TEMPLATES_PATH = '_custom/shared/templates';
const TIKI_CUSTOMIZATIONS_SHARED_WIKIPLUGINS_PATH = '_custom/shared/wiki-plugins';
const TIKI_CUSTOMIZATIONS_JAVASCRIPT_PATH_FRAGMENT = 'js/custom.js';
const TIKI_CUSTOMIZATIONS_WIKIPLUGINS_PATH_FRAGMENT = 'wiki-plugins';
/** If present, this php file will be included at the end of tiki-setup.php.  A last resort for custom php code.  Before tiki 27 this was in  _custom/lib/setup/custom.php */
const TIKI_CUSTOMIZATIONS_SETUP_PHP_FILE = '_custom/shared/custom.php';
/** Path to directoy containing the custom.xml file in which the user can load additional classes.
 * Before tiki 27 this was in db/config/
 */
const TIKI_CUSTOMIZATIONS_CLASS_LOADER_XML_PATH = '_custom/shared';

const TIKI_CUSTOMIZATIONS_MULTITIKI_SITES_PATH = '_custom/sites';
const TIKI_CUSTOMIZATIONS_MULTITIKI_SITES_PUBLIC_PATH = 'public/generated/_custom/sites';
const TIKI_CUSTOMIZATIONS_MULTITIKI_DEFAULT_SITE_PATH = '_custom/sites/default_site';
const TIKI_CUSTOMIZATIONS_MULTITIKI_DEFAULT_SITE_PUBLIC_PATH = 'public/generated/_custom/sites/default_site';

/* END - _custom section */

/** Where are themes looked for in multitiki sites, etc. */
const THEMES_PATH_FRAGMENT = 'themes';
const THEMES_LAYOUTS_PATH_FRAGMENT = 'templates/layouts';
const THEMES_LEGACY_ALL_SITES_SHARED_TEMPLATES_PATH = 'themes/templates';
const THEMES_BASE_FILES_FEATURE_CSS_PATH = 'themes/base_files/feature_css';
/** Base themes source directory relative to project root */
const BASE_THEMES_SRC_PATH = 'themes';

const WHELP_PATH = 'whelp';

/** This is only for permissions, not to include dependencies */
const TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH = 'vendor_bundled';

/** This is the "main" composer vendor folder from which to include dependencies, most projects would use vendor.  Use this to include dependencies  */
const TIKI_VENDOR_BUNDLED_PATH = 'vendor_bundled/vendor';
/** This is a tiki usage that differs from most other projects.  This is for dependencies we can't redistribute with tiki.  It is NOT the main source of composer dependencies */
const TIKI_VENDOR_NONBUNDLED_PATH = 'vendor'; //VENDOR_PATH caused a problem with CYPHT

const TIKI_VENDOR_CUSTOM_PATH = 'vendor_custom';

const PRIMARY_AUTOLOAD_FILE_PATH = 'vendor_bundled/vendor/autoload.php';
const PRIMARY_COMPOSERJSON_FILE_PATH = 'vendor_bundled/composer.json';
const COMPOSERLOCK_FILE_PATH = 'vendor_bundled/composer.lock';


/* BEGIN - HTTP PATHS */
const HTTP_PUBLIC_PATH = 'public';
/* Javascript assets servable over http, including css generated from js files. */
const JS_ASSETS_PATH = 'public/generated/js';
/** This is the path for JS vendor files that would normally be served by a content delivery network.  We both bundle these files in the tarball, and (usually) serve them locally. */
const NODE_PUBLIC_DIST_PATH = 'public/generated/js/vendor_dist';
const GENERATED_ICONSET_PATH = 'public/generated/iconsets';
/* END - HTTP PATHS */


/* BEGIN - VENDOR ASSETS SECTION */
const BOOTSTRAP_ICONS_FONT_PATH = 'public/generated/js/vendor_dist/bootstrap-icons/font';
const BOOTSTRAP_TOUR_DIST_PATH = 'vendor_bundled/vendor/sorich87/bootstrap-tour/build';
const CONVERSEJS_DIST_PATH = 'public/generated/js/vendor_dist/converse.js/dist';
const FONTAWESOME_CSS_PATH = 'public/generated/js/vendor_dist/@fortawesome/fontawesome';
const FONTAWESOME_WEBFONTS_PATH = 'public/generated/js/vendor_dist/@fortawesome/webfonts';

const MINICART_DIST_PATH = 'vendor_bundled/vendor/jquery/minicart/dist';
const NESTEDSORTABLE_ASSETS_PATH = 'vendor_bundled/vendor/jquery-plugins/nestedsortable';
const PIVOTTABLE_DIST_PATH = 'vendor_bundled/vendor/nicolaskruchten/pivottable/dist';
const PLOTLYJS_DIST_PATH = 'vendor_bundled/vendor/plotly/plotly.js/dist';
const REVEALJS_ASSETS_PATH = 'vendor_bundled/vendor/npm-asset/reveal.js';
const SUBTOTAL_DIST_PATH = 'vendor_bundled/vendor/nagarajanchinnasamy/subtotal/dist';
const SIGNATURE_PAD_DIST_PATH = 'vendor_bundled/vendor/npm-asset/signature_pad/dist';
const SWIPER_DIST_PATH = 'vendor_bundled/vendor/nolimits4web/swiper/dist/js';
const ZXING_DIST_PATH = 'vendor_bundled/vendor/npm-asset/zxing--library';

/* END - VENDOR ASSETS SECTION */
