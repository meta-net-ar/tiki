<?php

declare(strict_types=1);

namespace Tiki\Test\Core\Config;

use Tiki\Config\Ini;
use Tiki\Config\Config;

/**
 * @group integration
 */
final class SystemConfigIntegrationTest extends \TikiTestCase
{
    private static function deepMerge(array $base, array $overlay, bool $overwrite = true): array
    {
        foreach ($overlay as $k => $v) {
            if (isset($base[$k]) && is_array($base[$k]) && is_array($v)) {
                $base[$k] = self::deepMerge($base[$k], $v, $overwrite);
            } else {
                if ($overwrite || ! array_key_exists($k, $base)) {
                    $base[$k] = $v;
                }
            }
        }
        return $base;
    }

    private function envIni(): string
    {
        return <<<'INI'
[tikiwiki]
preference.sitetitle = "ENV TITLE"
preference.error_tracking_enabled_js = "y"
INI;
    }

    private function fileIni(): string
    {
        return <<<'INI'
[tikiwiki]
preference.sitetitle = "FILE TITLE"
preference.error_tracking_enabled_js = "n"

[rules]
lock.preference[] = "sitetitle"
INI;
    }

    public function testEnvThenFileMergeOverlayDbAndLock(): void
    {
        $env  = (new Ini())->fromString($this->envIni());
        $file = (new Ini())->fromString($this->fileIni());

        $data = self::deepMerge(['preference' => [], 'rules' => []], $env, false);
        //    - FILE  overwrite (=> FILE > ENV)
        $data = self::deepMerge($data, $file, true);

        $systemConfiguration = new Config($data);

        $dbPrefs = [
            'sitetitle'     => 'DB TITLE',
            'error_tracking_enabled_js' => 'y',
            'other_pref'     => 'db',
        ];
        // The `+` operator keeps the left-hand value when a key already exists
        $prefs = ($systemConfiguration->get('tikiwiki.preference') ?? []) + $dbPrefs;

        // Apply locks defined in the FILE layer (here: sitetitle)
        $locked = $systemConfiguration->get('rules.lock.preference') ?? [];
        foreach ($locked as $key) {
            $fileVal = $systemConfiguration->get('tikiwiki.preference.' . $key);
            if ($fileVal !== null) {
                $prefs[$key] = $fileVal;
            }
        }

        // Expected assertions
        $this->assertSame('FILE TITLE', $prefs['sitetitle']);
        $this->assertSame('n', $prefs['error_tracking_enabled_js']);
        $this->assertSame('db', $prefs['other_pref']);
    }

    public function testYNRemainStrings(): void
    {
        $ini = <<<'INI'
[tikiwiki]
preference.feature_syntax_highlighter = "n"
preference.error_tracking_enabled_php = "y"
INI;

        $prefs = (new Ini())->fromString($ini)['tikiwiki']['preference'];
        $this->assertSame('n', $prefs['feature_syntax_highlighter']);
        $this->assertSame('y', $prefs['error_tracking_enabled_php']);
        $this->assertIsString($prefs['feature_syntax_highlighter']);
        $this->assertIsString($prefs['error_tracking_enabled_php']);
    }
}
