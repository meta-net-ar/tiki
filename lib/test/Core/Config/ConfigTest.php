<?php

declare(strict_types=1);

namespace Tiki\Test\Core\Config;

use Tiki\Config\Ini;
use Tiki\Config\Config;

/**
 * @group unit
 */
final class ConfigTest extends \TikiTestCase
{
    private function sampleIni(): string
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

    public function testGetReturnsExpectedPreferenceValues(): void
    {
        $file = (new Ini())->fromString($this->sampleIni());
        $cfg  = new Config($file);

        $this->assertSame('n', $cfg->get('tikiwiki.preference.feature_syntax_highlighter'));
        $this->assertSame('n', $cfg->get('tikiwiki.preference.feature_jquery_tablesorter'));
        $this->assertSame('trunkdev_tiki_org_', $cfg->get('tikiwiki.preference.unified_manticore_index_prefix'));
        $this->assertSame('trunk_', $cfg->get('tikiwiki.preference.federated_manticore_index_prefix'));
        $this->assertSame('/opt/homebrew/bin/php', $cfg->get('tikiwiki.preference.php_cli_path'));
        $this->assertSame('noindex, nofollow', $cfg->get('tikiwiki.preference.metatag_robots'));
        $this->assertSame('file', $cfg->get('tikiwiki.preference.mailer_handler'));
        $this->assertSame('90', $cfg->get('tikiwiki.preference.scheduler_delay'));
        $this->assertSame('localhost', $cfg->get('tikiwiki.preference.cookie_name'));
        $this->assertSame('localhost', $cfg->get('tikiwiki.preference.cookie_domain'));
        $this->assertSame('y', $cfg->get('tikiwiki.preference.error_tracking_enabled_php'));
        $this->assertSame('y', $cfg->get('tikiwiki.preference.error_tracking_enabled_js'));
    }

    public function testAddOnlyOverlayDoesNotCoerceStringTypes(): void
    {
        $file = (new Ini())->fromString($this->sampleIni());

        $envOverlay = [
            'tikiwiki' => [
                'preference' => [
                    'scheduler_delay'            => 90,   // int in overlay
                    'error_tracking_enabled_php' => true, // bool in overlay
                ],
            ],
        ];

        // Add-only merge at array level: keep base for existing keys
        $basePrefs = $file['tikiwiki']['preference'] ?? [];
        $overlay   = $envOverlay['tikiwiki']['preference'];
        $merged    = $basePrefs + $overlay; // '+' keeps left-hand (base) values

        // Types must remain strings from the base INI
        $this->assertSame('90', $merged['scheduler_delay']);
        $this->assertIsString($merged['scheduler_delay']);
        $this->assertSame('y', $merged['error_tracking_enabled_php']);
        $this->assertIsString($merged['error_tracking_enabled_php']);
    }
}
