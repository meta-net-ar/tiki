<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Installer\Installer;

/**
 * Change various default prefs as discussed on https://dev.tiki.org/Tiki27-Pref-Changes-Discussion
 *
 * @param Installer $installer
 */
function upgrade_20240311_various_pref_default_tiki27_tiki($installer)
{
    $installer->preservePreferenceDefault('trackerfield_dropdownother', 'n');
    $installer->preservePreferenceDefault('trackerfield_geographicfeature', 'n');
    $installer->preservePreferenceDefault('trackerfield_itemslist', 'n');
    $installer->preservePreferenceDefault('trackerfield_math', 'n');
    $installer->preservePreferenceDefault('trackerfield_relation', 'n');
    $installer->preservePreferenceDefault('trackerfield_usergroups', 'n');
    $installer->preservePreferenceDefault('change_theme', 'n');
    $installer->preservePreferenceDefault('cookie_refresh_rememberme', 'n');
    $installer->preservePreferenceDefault('feature_trackers', 'n');
    $installer->preservePreferenceDefault('feature_categories', 'n');
    $installer->preservePreferenceDefault('feature_page_title', 'n');
    $installer->preservePreferenceDefault('feature_wiki_icache', 'n');
    $installer->preservePreferenceDefault('feature_quick_object_perms', 'n');
    $installer->preservePreferenceDefault('feature_wysiwyg', 'n');
    $installer->preservePreferenceDefault('feature_mytiki', 'n');
    $installer->preservePreferenceDefault('feature_userPreferences', 'n');
    $installer->preservePreferenceDefault('feature_obzip', 'n');
    $installer->preservePreferenceDefault('feature_sefurl_title_trackeritem', 'n');
    $installer->preservePreferenceDefault('feature_sefurl_routes', 'n');
    $installer->preservePreferenceDefault('feature_show_stay_in_ssl_mode', 'y');
    $installer->preservePreferenceDefault('feature_absolute_to_relative_links', 'n');
    $installer->preservePreferenceDefault('fgal_elfinder_feature', 'n');
    $installer->preservePreferenceDefault('fgal_default_view', 'list');
    $installer->preservePreferenceDefault('fgal_sortField', 'created');
    $installer->preservePreferenceDefault('fgal_sortDirection', 'desc');
    $installer->preservePreferenceDefault('rememberme', 'disabled');
    $installer->preservePreferenceDefault('remembertime', '7200');
    $installer->preservePreferenceDefault('jquery_smartmenus_enable', 'n');
    $installer->preservePreferenceDefault('jquery_ui_modals_draggable', 'n');
    $installer->preservePreferenceDefault('jquery_ui_modals_resizable', 'n');
    $installer->preservePreferenceDefault('layout_tabs_optional', 'y');
    $installer->preservePreferenceDefault('layout_add_body_group_class', 'n');
    $installer->preservePreferenceDefault('namespace_separator', ':_:');
    $installer->preservePreferenceDefault('permission_denied_login_box', 'n');
    $installer->preservePreferenceDefault('session_storage', 'default');
    $installer->preservePreferenceDefault('site_layout', 'basic');
    $installer->preservePreferenceDefault('tracker_refresh_itemlink_detail', 'n');
    $installer->preservePreferenceDefault('tracker_change_field_type', 'n');
    $installer->preservePreferenceDefault('tracker_tabular_enabled', 'n');
    $installer->preservePreferenceDefault('tracker_always_notify', 'y');
    $installer->preservePreferenceDefault('tracker_field_rules', 'n');
    $installer->preservePreferenceDefault('unified_trackeritem_category_names', 'n');
    $installer->preservePreferenceDefault('user_show_realnames', 'n');
    $installer->preservePreferenceDefault('user_store_file_gallery_picture', 'n');
    $installer->preservePreferenceDefault('user_picture_gallery_id', 0);
    $installer->preservePreferenceDefault('users_prefs_remember_closed_rboxes', 'n');
    $installer->preservePreferenceDefault('vuejs_enable', 'n');
    $installer->preservePreferenceDefault('vuejs_always_load', 'n');
    $installer->preservePreferenceDefault('vuejs_toolbar_dialogs', 'n');
    $installer->preservePreferenceDefault('wiki_cache', 0);
    $installer->preservePreferenceDefault('wiki_date_field', 'created');
    $installer->preservePreferenceDefault('wikiplugin_list_gui', 'n');
    $installer->preservePreferenceDefault('unified_included_plugins', serialize([]));
}
