#! /bin/sh

# shellcheck disable=SC2317,SC2004,SC2034,SC2209
# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

# This file sets permissions and creates relevant folders for Tiki.

# ---------------------------------------------------------

# This is a PARTIAL list from path_constants.php, for setup.sh. 

# But it MUST match what's in path_constatns.php

ADMIN_PATH=admin
TIKI_CONFIG_PATH=db
DEPRECATED_FILES_PATH=files
STATIC_IMG_PATH=img
DEPRECATED_IMG_WIKI_PATH=img/wiki
DEPRECATED_IMG_WIKI_UP_PATH=img/wiki_up
TRACKER_FIELD_IMAGE_STORAGE_PATH=img/trackers
INSTALLER_PATH=installer
LANG_SRC_PATH=lang
LIB_PATH=lib
MODULES_PATH=modules
DEPRECATED_MODS_PATH=mods
PERMISSIONCHECK_PATH=permissioncheck
DEPRECATED_STORAGE_PATH=storage
STORAGE_PUBLIC_PATH=storage/public
FILE_GALLERY_DEFAULT_STORAGE_PATH=storage/fgal
DEPRECATED_STORAGE_PUBLIC_H5P_PATH=storage/public/h5p
TEMP_PATH=temp
TEMP_CACHE_PATH=temp/cache
TEMP_HTTP_PUBLIC_PATH=temp/public
SMARTY_COMPILED_TEMPLATES_PATH=temp/templates_c
UNIFIED_INDEX_TEMP_PATH=temp/unified-index
SMARTY_TEMPLATES_PATH=templates
TESTS_PATH=tests
BASE_THEMES_SRC_PATH=themes
TIKI_TESTS_PATH=tiki_tests/tests
TIKI_VENDOR_NONBUNDLED_PATH=vendor
TIKI_VENDOR_CUSTOM_PATH=vendor_custom
WHELP_PATH=whelp

# EOF
