<?php

declare(strict_types=1);

namespace Tiki\Test\Core\Config;

use Tiki\Config\Ini;

/**
 * @group unit
 */
final class IniTest extends \TikiTestCase
{
    private function getSampleIni(): string
    {
        return <<<'INI'
[tikiwiki]
preference.feature_syntax_highlighter = "n"
preference.feature_jquery_tablesorter = "n"
preference.unified_manticore_index_prefix = "trunkdev_tiki_org_"
preference.federated_manticore_index_prefix = "trunk_"
preference.php_cli_path = "/opt/homebrew/bin/php"
preference.metatag_robots = "noindex, nofollow"
preference.mailer_handler = "file"
preference.scheduler_delay = "90"
preference.cookie_name = "localhost"
preference.cookie_domain = "localhost"
preference.error_tracking_enabled_php = "y"
preference.error_tracking_enabled_js = "y"
INI;
    }

    public function testIniFromStringParsesAllPreferencesAsStrings(): void
    {
        $data = (new Ini())->fromString($this->getSampleIni());

        $this->assertArrayHasKey('tikiwiki', $data);
        $prefs = $data['tikiwiki']['preference'] ?? [];

        foreach (
            [
                'feature_syntax_highlighter',
                'feature_jquery_tablesorter',
                'unified_manticore_index_prefix',
                'federated_manticore_index_prefix',
                'php_cli_path',
                'metatag_robots',
                'mailer_handler',
                'scheduler_delay',
                'cookie_name',
                'cookie_domain',
                'error_tracking_enabled_php',
                'error_tracking_enabled_js',
            ] as $k
        ) {
            $this->assertArrayHasKey($k, $prefs, "Missing preference: {$k}");
        }

        $expected = [
            'feature_syntax_highlighter'      => 'n',
            'feature_jquery_tablesorter'      => 'n',
            'unified_manticore_index_prefix'  => 'trunkdev_tiki_org_',
            'federated_manticore_index_prefix' => 'trunk_',
            'php_cli_path'                    => '/opt/homebrew/bin/php',
            'metatag_robots'                  => 'noindex, nofollow',
            'mailer_handler'                  => 'file',
            'scheduler_delay'                 => '90',
            'cookie_name'                     => 'localhost',
            'cookie_domain'                   => 'localhost',
            'error_tracking_enabled_php'      => 'y',
            'error_tracking_enabled_js'       => 'y',
        ];

        foreach ($expected as $key => $val) {
            $this->assertSame($val, (string) $prefs[$key], "Bad value for {$key}");
            $this->assertIsString($prefs[$key], "Value for {$key} must stay a string");
        }
    }
}
