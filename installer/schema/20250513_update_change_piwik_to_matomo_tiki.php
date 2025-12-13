<?php

use Tiki\Installer\Installer;

/**
 * Upgrade: change piwik to matomo and enable it if site_matomo_code preference enable
 *
 * @param Installer $installer
 */
function upgrade_20250513_update_change_piwik_to_matomo_tiki($installer)
{
    $piwik_preferences = [
        'site_piwik_code',
        'site_piwik_analytics_server_url',
        'site_piwik_group_option',
        'site_piwik_groups',
        'site_piwik_site_id',
        'wikiplugin_piwik',
        'wikiplugininline_piwik'
    ];

    foreach ($piwik_preferences as $piwik_pref_name) {
        $piwik_pref_value = $installer->getOne("SELECT value FROM `tiki_preferences` WHERE `name` = ? ", [$piwik_pref_name]);

        if (! empty($piwik_pref_value)) {
            $matomo_pref_name = str_replace('piwik', 'matomo', $piwik_pref_name);
            $installer->query('UPDATE `tiki_preferences` SET `name` = ? WHERE `name` = ?', [$matomo_pref_name, $piwik_pref_name]);
        }
    }

    $query = "SELECT `pagename`, `data` FROM `tiki_pages` WHERE LOWER(`data`) LIKE ?";
    $bindvars = [ "%{piwik%" ];
    $wiki_pages_with_piwik = $installer->fetchAll($query, $bindvars);

    $matomo_wiki_page_data = "";

    if ($wiki_pages_with_piwik) {
        foreach ($wiki_pages_with_piwik as $piwik_wiki_page) {
            $matches = WikiParser_PluginMatcher::match($piwik_wiki_page['data']);
            $argumentParser = new WikiParser_PluginArgumentParser();

            foreach ($matches as $match) {
                if ($match->getName() !== 'piwik') {
                    continue;
                }
                $args = $argumentParser->parse($match->getArguments());
                $body = $match->getBody();

                if (array_key_exists('piwikserverurl', $args)) {
                    $args['matomoserverurl'] = $args['piwikserverurl'];
                    unset($args['piwikserverurl']);
                }
                $match->replaceWithPlugin('matomo', $args, $body);
            }

            $matomo_wiki_page_data = $matches->getText();
            $installer->query('UPDATE `tiki_pages` SET `data` = ? WHERE `pagename` = ?', [$matomo_wiki_page_data, $piwik_wiki_page['pagename']]);
        }
    }

    $siteMatomoCode = $installer->getOne("SELECT value FROM `tiki_preferences` WHERE `name` = 'site_matomo_code'");

    if (! empty($siteMatomoCode)) {
        $tikilib = TikiLib::lib('tiki');
        $tikilib->set_preference('wikiplugin_matomo', 'y');
    }
}
