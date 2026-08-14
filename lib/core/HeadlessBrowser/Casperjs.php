<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

use Perms;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Tiki\HeadlessBrowser\Exception\HeadlessException;
use Tiki\Package\VendorHelper;
use Tiki\Process\Process;
use TikiLib;
use WikiPlugin_Casperjs_Result;

class Casperjs implements HeadlessBrowserInterface
{
    private $casperBin;
    private const BASE_MARKER = "TIKI_BRIDGE";

    public function __construct()
    {
        $this->casperBin = TIKI_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'casperjs';
        $requiredPackage = $this->getRequiredPackage();
        foreach ($requiredPackage as $package => $class) {
            if (! class_exists($class)) {
                throw new HeadlessException(tr("Required package %0 is missing. Install using tiki packages.", $package));
            }
        }
        if (! file_exists($this->casperBin) || ! is_executable($this->casperBin)) {
            throw new HeadlessException(tr("CasperJS binary not found or not executable."));
        }
    }

    public function getType()
    {
        return HeadlessBrowserFactory::CASPERJS;
    }

    private function getRequiredPackage()
    {
        return ['jerome-breton/casperjs-installer' => 'CasperJsInstaller\Installer'];
    }

    public function getUrlAsHtml($url, $cssSelector = null)
    {
        $script = <<<JS
            var casper = require('casper').create();
            casper.start("$url", function() {
                var html = casper.evaluate(function() {
                    return document.querySelector("$cssSelector").innerHTML;
                });
                this.echo(html);
            });
             
            casper.run();
        JS;

        try {
            $result = $this->executeCasperJs($script);
            $result = implode("\n", array_filter($result->getScriptOutput(), function ($line) {
                return ! empty($line);
            }));

            if ($result == 'null' || empty($result)) {
                throw new \Exception(tr('Failed to capture HTML from url: %0', $url));
            }
            return $result;
        } catch (\Exception $e) {
            throw new HeadlessException($e->getMessage());
        }
    }

    public function executeCasperJs($script, $options = [], $casperInstance = null)
    {
        $casperScript = tempnam(false, 'casperjs-script-');
        $fullScript = $this->scriptPrefix() . "\n" . $script . "\n" . $this->scriptPostfix($script, $casperInstance);
        file_put_contents($casperScript, $fullScript);

        $cmd = [$this->casperBin, $casperScript, '--ignore-ssl-errors=true'];
        if (! empty($options) && is_array($options)) {
            foreach ($options as $option => $value) {
                $cmd[] = ' --' . $option . '=' . $value;
            }
        }

        $process = new Process($cmd);
        try {
            $process->run(null, ['OPENSSL_CONF' => '/etc/ssl']);
        } catch (ProcessTimedOutException $e) {
            throw new \Exception(tr('Failed to run CasperJS script: %0', $e->getMessage()));
        } finally {
            unlink($casperScript);
        }

        if ($process->isSuccessful()) {
            $output = $process->getOutput();
        } else {
            throw new \Exception(tr('Failed to run CasperJS script: %0', $process->getErrorOutput()));
        }

        if (empty($output)) {
            throw new \Exception(tr('Failed to run CasperJS script.'));
        }

        $result = new WikiPlugin_Casperjs_Result(explode("\n", $output), implode(' ', $cmd), $fullScript);

        return $result;
    }

    public function getUrlAsImage($htmlFile, $outputPath = null, $cssSelector = null, $timeout = null)
    {
        global $base_url;

        $htmlFileUrl = $base_url . 'temp' . DIRECTORY_SEPARATOR . basename($htmlFile);
        $cssSelector = $cssSelector ? $cssSelector : 'body';
        $casperjsScript = <<<JS
        var casper = require('casper').create();
        var htmlFileUrl = '{$htmlFileUrl}';
        casper.start(htmlFileUrl, function() {
            this.echo(this.captureBase64('png', '{$cssSelector}'));
        });
        casper.run();
        JS;

        $casperFile = writeTempFile($casperjsScript, '', true, 'wikiplugin_chart_', '.js');
        $process = new Process([$this->casperBin, $casperFile, '--ignore-ssl-errors=true']);
        if (! empty($timeout)) {
            $process->setTimeout($timeout);
            $process->setIdleTimeout($timeout);
        }

        try {
            $process->run(null, ['OPENSSL_CONF' => '/etc/ssl']);
        } catch (ProcessTimedOutException $e) {
            throw new HeadlessException(tr('Process timeout while capturing image from file: %0, %1', $htmlFile, $e->getMessage()));
        }

        if ($process->isSuccessful()) {
            if (file_exists($htmlFile)) {
                unlink($htmlFile);
            }
            if (file_exists($casperFile)) {
                unlink($casperFile);
            }
            if (file_exists($outputPath)) {
                unlink($outputPath);
            }
            return $process->getOutput();
        } else {
            $errorMessage = tr(
                'Failed to generate chart image using Casperjs "%0"',
                $process->getErrorOutput()
            );
            if (Perms::get()->admin) {
                $errorMessage .= tr(
                    ' (with html file "%0" and casper file "%1")',
                    substr($htmlFile, strlen(TIKI_PATH)),
                    substr($casperFile, strlen(TIKI_PATH))
                );
            }
            return $errorMessage;
        }
    }

    public function getDiagramAsImage($rawXml)
    {
        $diagramContent = str_replace(['<mxfile>', '</mxfile>'], '', $rawXml);
        $fileIdentifier = md5($diagramContent);
        $vendorPath = VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram', false);
        $casperBin = TIKI_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'casperjs';
        $scriptPath = TIKI_PATH . DIRECTORY_SEPARATOR . 'lib/jquery_tiki/tiki-diagram.js';
        $htmlFile = TIKI_PATH . DIRECTORY_SEPARATOR . 'lib/core/File/DiagramHelperExportCasperJS.html';
        $jsfile = TIKI_PATH . DIRECTORY_SEPARATOR . 'temp/do_' . $fileIdentifier . '.js';
        $imgFile = TIKI_PATH . DIRECTORY_SEPARATOR . 'temp/diagram_' . $fileIdentifier . '.png';

        if (! empty($vendorPath) && file_exists($casperBin) && file_exists($scriptPath) && file_exists($htmlFile)) {
            $jsContent = <<<EOF
            var data = {};
            data.xml = '$rawXml';
            try
            {
                render(data);
            }
            catch(e)
            {
                console.log(e);
            }
            EOF;

            if (file_exists($jsfile)) {
                unlink($jsfile);
            }

            if (file_exists($imgFile)) {
                unlink($imgFile);
            }

            file_put_contents($jsfile, $jsContent, FILE_APPEND);
        }

        if (! file_exists($jsfile)) {
            throw new HeadlessException(tr('Error while capturing diagram as image.'));
        }

        try {
            $command = [$casperBin, $scriptPath, '--htmlfile=' . $htmlFile, '--filename=' . $fileIdentifier];
            $process = new Process($command);
            $process->run();
            if (! $process->isSuccessful() || ! file_exists($imgFile)) {
                throw new \Exception(tr('Error while capturing diagram as image.'));
            }
            $imgData = file_get_contents($imgFile);
            return base64_encode($imgData);
        } catch (\Exception $e) {
            throw new HeadlessException($e->getMessage());
        } finally {
            if (file_exists($imgFile)) {
                unlink($imgFile);
            }
            if (file_exists($jsfile)) {
                unlink($jsfile);
            }
        }
    }

    private function scriptPrefix()
    {
        $baseMarker = self::BASE_MARKER;
        $prefix = <<<EOT

var tikiBridge = function(){
    var baseMarker='{$baseMarker}';
    var results = {};

    function add(key, result){
        results[key] = result;
    };

    function addGeneric(casperInstance){
        if (typeof casperInstance === 'undefined' || typeof casperInstance.getCurrentUrl === 'undefined'){
            // do not look like a casper Instance
            return;
        }
        add('tikibridge_url', casperInstance.getCurrentUrl());
        add('tikibridge_title', casperInstance.getTitle());
        add('tikibridge_content', casperInstance.getPageContent());
        add('tikibridge_html', casperInstance.getHTML());
    };

    function done(casperInstance){
        addGeneric(casperInstance);
        console.log(baseMarker + '_EXPORT' + JSON.stringify(results));
    };

    var exports = {
        add: add,
        addGeneric: addGeneric,
        done: done
    };

    console.log(baseMarker + '_START');
    return exports;
}();
/* ********************* Tiki Bridge Prefix ********************* */

EOT;

        return $prefix;
    }

    private function scriptPostfix($script, $casperInstance = null)
    {
        if ($casperInstance === null) {
            $casperInstance = "casper";
        }
        if (str_contains($script, $casperInstance . '.run')) {
            return "";
        }

        $postfix = <<<EOT

/* ********************* Tiki Bridge PostFix ********************* */
{$casperInstance}.run(function () {
    tikiBridge.done({$casperInstance});
    {$casperInstance}.exit();
});

EOT;

        return $postfix;
    }
}
