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
        $this->bootstrapIconsFilePath = __DIR__ . '/../' . BOOTSTRAP_ICONS_FONT_PATH . '/bootstrap-icons.css';
        $this->fontAwesomeFilePath = __DIR__ . '/../' . FONTAWESOME_CSS_PATH . '/all.css';
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
        $fontAwesomePattern = '/\.fa-([a-zA-Z0-9-]+)(::|:)before/';
        //pattern to extract unicodes from file
        $fontAwesomeUnicodePattern = '#content: "\\\(.*?)"; }#';

        $bootstrapFinal = [];
        $fontAwesomeFinal = [];
        $bootstrapPhp = "";
        $fontAwesomePhp = "";

        // Extract Bootstrap icons
        if ($bootstrapIcons) {
            $bootstrapMatches = [];
            preg_match_all($bootstrapPattern, $bootstrapIcons, $bootstrapMatches);

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
            preg_match_all($fontAwesomePattern, $fontAwesomeIcons, $fontAwesomeMatches);
            preg_match_all($fontAwesomeUnicodePattern, $fontAwesomeIcons, $fontAwesomeUnicodeMatches);
            $fontAwesomeResult = $fontAwesomeMatches[1];
            $fontAwesomeUnicodeResult = $fontAwesomeUnicodeMatches[1];
            $fontAwesomePhp = "<?php\n    global \$prefs; \n      \$prefs['fa_generated_icons'] = [";
            $line = 0;
            foreach ($fontAwesomeResult as $value) {
                $name = str_replace('-', '_', $value);
                $fontAwesomeFinal[$name] = [
                'id' => $value,
                'prepend' => 'fas fa-',
                'codeValue' => '#x' . $fontAwesomeUnicodeResult[$line]
                ];
                $fontAwesomePhp .= "       '$name' => [ 
                    'id' => '$value',
                    'prepend' => 'fas fa-'
                ],\n";
                $line++;
            }
            $fontAwesomePhp .= "];";
        }

        return [json_encode($bootstrapFinal), $bootstrapPhp, json_encode($fontAwesomeFinal), $fontAwesomePhp];
    }

    public function checkFileContent($filePath, $error_key): string|false
    {
        if (realpath($filePath) === false) {
            $this->state[$error_key] = -1;
            return false;
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

    public function reportFailure(): void
    {
        if ($this->state['bs'] == -1) {
            throw new Exception("Failed to open the bootstrap css vendor file, bootstrap icons won't be synched. Maybe npm is not available at this stage.");
        }
        if ($this->state['fa'] == -1) {
            throw new Exception("Failed to open the fontawesome css vendor file, fontawesome icons won't be synched. Maybe composer is not available at this stage.");
        }
    }

    public function execute(): void
    {
        $bootstrapIconsContent = $this->checkFileContent($this->bootstrapIconsFilePath, 'bs');
        $fontAwesomeContent = $this->checkFileContent($this->fontAwesomeFilePath, 'fa');

        if (! $bootstrapIconsContent || ! $fontAwesomeContent) {
            $this->reportFailure();
        }

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
