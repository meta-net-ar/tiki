<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
namespace Tiki\Lib;

use Exception;
use Symfony\Component\Console\Output\OutputInterface;

class IconGenerator
{
    private $bootstrapIconsFilePath;
    private $fontAwesomeFilePath;
    private $bootstrapJsonFilePath;
    private $bootstrapPhpFilePath;
    private $fontAwesomeJsonFilePath;
    private $fontAwesomePhpFilePath;
    private $state;
    public $output;

    public function __construct(OutputInterface $output)
    {
        $this->bootstrapIconsFilePath = __DIR__ . '/../' . BOOTSTRAP_ICONS_FONT_SRC_PATH . '/bootstrap-icons.css';
        $this->fontAwesomeFilePath = __DIR__ . '/../' . FONTAWESOME_CSS_SRC_PATH . '/all.css';
        $this->bootstrapJsonFilePath = __DIR__ . '/../' . GENERATED_ICONSET_PATH . '/all_bootstrap_icons.json';
        $this->bootstrapPhpFilePath = __DIR__ . '/../' . GENERATED_ICONSET_PATH . '/all_bootstrap_icons.php';
        $this->fontAwesomeJsonFilePath = __DIR__ . '/../' . GENERATED_ICONSET_PATH . '/all_fontawesome_icons.json';
        $this->fontAwesomePhpFilePath = __DIR__ . '/../' . GENERATED_ICONSET_PATH . '/all_fontawesome_icons.php';

        $this->state = [
            'bs' => 1,
            'fa' => 1
          ];
        $this->output = $output;
    }

    public function generateIconArraysFromCss($bootstrapIcons, $fontAwesomeIcons): array
    {
        $bootstrapPattern = '/\.bi-([a-zA-Z0-9-]+)::before/';
        /**
         * faClassPattern matches a selector starting with .fa- and faUnicodePattern
         * asserts that within the upcoming block there exists a '--fa' property
         * with the desired unicode value. We combine icon name and unicode value
         * search to avoid non-icon classes from all.css as all of them start by .fa-
        */
        $faClassPattern = '\.fa-([\w-]+)';
        $faUnicodePattern = '{--fa:["\']\\\\([a-f0-9]{2,5})["\'];}';
        $fontAwesomePattern = "/{$faClassPattern}{$faUnicodePattern}/s";

        $bootstrapFinal = [];
        $fontAwesomeFinal = [];
        $bootstrapPhp = "";
        $fontAwesomePhp = "";

        // Extract Bootstrap icons
        if ($bootstrapIcons) {
            $bootstrapMatches = [];
            $numMatches = preg_match_all($bootstrapPattern, $bootstrapIcons, $bootstrapMatches);
            if ($numMatches < 1) {
                throw new Exception("Unable to find any icons with pattern: $bootstrapPattern");
            }

            $bootstrapResult = $bootstrapMatches[1];
            $bootstrapPhp = "<?php\n    global \$prefs; \n       \$prefs['bs_generated_icons'] = [";
            foreach ($bootstrapResult as $value) {
                $name = str_replace('-', '_', $value);
                $bootstrapFinal[$name]['id'] = $value;
                $bootstrapPhp .= "       '$name' => [ 
                    'id' => '$value'
                ],\n";
            }
            $bootstrapPhp .= "];";
        }
        // Extract Font Awesome icons
        if ($fontAwesomeIcons) {
            $fontAwesomeMatches = [];

            $numMatchesFA = preg_match_all($fontAwesomePattern, preg_replace('/\s+/', '', $fontAwesomeIcons), $fontAwesomeMatches);
            if ($numMatchesFA < 1) {
                throw new Exception("Unable to find any icons with pattern: $fontAwesomePattern");
            }

            $fontAwesomeResult = $fontAwesomeMatches[1];
            $fontAwesomeUnicodeResult = $fontAwesomeMatches[2];

            $fontAwesomePhp = "<?php\n    global \$prefs; \n      \$prefs['fa_generated_icons'] = [";
            foreach ($fontAwesomeResult as $index => $iconName) {
                $name = str_replace('-', '_', $iconName);
                $fontAwesomeFinal[$name] = [
                'id' => $iconName,
                'prepend' => 'fas fa-',
                'codeValue' => '#x' . ($fontAwesomeUnicodeResult[$index])
                ];
                $fontAwesomePhp .= "       '$name' => [ 
                    'id' => '$iconName',
                    'prepend' => 'fas fa-'
                ],\n";
            }
            $fontAwesomePhp .= "];";
        }
        return [json_encode($bootstrapFinal), $bootstrapPhp, json_encode($fontAwesomeFinal), $fontAwesomePhp];
    }

    public function checkFileContent($filePath, $error_key): string|false
    {
        if (realpath($filePath) === false) {
            throw new Exception(sprintf("Failed to open the %s file, fontawesome icons won't be synched. Maybe composer or npm install didn't run yet?", $filePath));
        }
        $content = file_get_contents($filePath);
        return $content;
    }

    public function displayResult($filePath, $result, $operation): void
    {
        if ($result !== false) {
            $bytesWritten = $result . ' bytes written.';
            $this->output->writeln("<info>Successfully wrote to " . realpath($filePath) . ". $bytesWritten</info>");
        } else {
            throw new Exception("Error writing to $filePath during $operation"); //here we keep the path as is cause realpath may return empty string if dir dont exist
        }
    }

    public function execute(): void
    {
        $bootstrapIconsContent = $this->checkFileContent($this->bootstrapIconsFilePath, 'bs');
        $fontAwesomeContent = $this->checkFileContent($this->fontAwesomeFilePath, 'fa');

        $modifiedIcons = $this->generateIconArraysFromCss($bootstrapIconsContent, $fontAwesomeContent);
        if (! is_dir(__DIR__ . "/../" . GENERATED_ICONSET_PATH) && ! mkdir(__DIR__ . "/../" . GENERATED_ICONSET_PATH, 0777, true)) {
            throw new Exception("Could not create '.GENERATED_ICONSET_PATH.' folder");
        }
        if ($this->state['bs'] != -1) {
            $resultBootstrapJson = file_put_contents($this->bootstrapJsonFilePath, $modifiedIcons[0]);
            $this->displayResult($this->bootstrapJsonFilePath, $resultBootstrapJson, 'Bootstrap icons JSON file writing');
            $resultBootstrapPhp = file_put_contents($this->bootstrapPhpFilePath, $modifiedIcons[1]);
            $this->displayResult($this->bootstrapPhpFilePath, $resultBootstrapPhp, 'Boostrap icons PHP file writing');
        }

        if ($this->state['fa'] != -1) {
            $resultFontAwesomeJson = file_put_contents($this->fontAwesomeJsonFilePath, $modifiedIcons[2]);
            $this->displayResult($this->fontAwesomeJsonFilePath, $resultFontAwesomeJson, 'Fontawesome icons JSON file writing');
            $resultFontAwesomePhp = file_put_contents($this->fontAwesomePhpFilePath, $modifiedIcons[3]);
            $this->displayResult($this->fontAwesomePhpFilePath, $resultFontAwesomePhp, 'Fontawesome PHP file writing');
        }
    }
}
