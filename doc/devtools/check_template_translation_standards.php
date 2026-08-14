<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Console\Input\ArgvInput;

if (isset($_SERVER['REQUEST_METHOD'])) {
    die('Only available through command-line.');
}

$dir = __DIR__;
require_once $dir . '/../../tiki-filter-base.php';
require __DIR__ . '/vcscommons.php';

$input = new ArgvInput();
$file = $input->getParameterOption(['--file']);
$all = $input->hasParameterOption(['--all']);
$templates = getAllTemplateFiles($dir);
$langDir = $dir . '/../../lang';
$translationFiles = getAllTranslationFiles($langDir);

if (empty($file) && empty($all)) {
    error('Params not found. ' . PHP_EOL . 'Valid params: --file [file], --all');
    die();
}

if (! empty($file)) {
    $file = $dir . '/' . $file;
    if (! in_array($file, $templates) || ! file_exists($file)) {
        error('File not found or is not .tpl');
        die();
    }
    $message = '';
    $check = check($file);
    if (isset($check)) {
        $message .= $check . PHP_EOL;
    }
    if (! empty($message)) {
        info(color('File has ":" or "," outside of translation in the following lines in red:', 'yellow'));
        info(trim($message, PHP_EOL));
        exit(1);
    } else {
        info(basename($file) . ' ' . color('OK', 'green'));
    }
}

if (! empty($translationFiles)) {
    $message = '';
    foreach ($translationFiles as $file) {
        $check = checkTranslationFile($file);
        if (isset($check)) {
            $message .= $check . PHP_EOL;
        }
    }
    if (! empty($message)) {
        info(color('The following translation files have issues in the following lines in red:', 'yellow'));
        info(trim($message, PHP_EOL));
        exit(1);
    } else {
        important('All translation files OK');
    }
}

if (! empty($all)) {
    $message = '';
    foreach ($templates as $file) {
        $check = check($file);
        if (isset($check)) {
            $message .= $check . PHP_EOL;
        }
    }
    if (! empty($message)) {
        info(color('The following files have ":" or "," outside of translation in the following lines in red:', 'yellow'));
        info(trim($message, PHP_EOL));
        exit(1);
    } else {
        important('All template files OK');
    }
}

function getAllTemplateFiles($currentDir)
{
    $templateDir = new RecursiveDirectoryIterator($currentDir . '/../../templates');
    $ite = new RecursiveIteratorIterator($templateDir);
    $files = new RegexIterator($ite, '/.*tpl/', RegexIterator::GET_MATCH);
    $templateList = [];
    foreach ($files as $file) {
        if (file_exists($file[0])) {
            $templateList = array_merge($templateList, $file);
        }
    }
    return $templateList;
}

function check($file)
{
    if (file_exists($file)) {
        $message = realpath($file);
        $lineNumber = '';
        if ($fileHandler = fopen($file, "r")) {
            $i = 0;
            while ($line = fgets($fileHandler)) {
                $i++;
                if (str_contains($line, '{/tr}:') || str_contains($line, '{/tr},')) {
                    $lineNumber .= $i . ",";
                }
            }
            fclose($fileHandler);
        }
        if (! empty($lineNumber)) {
            return color($message . ':', 'blue') . color(substr($lineNumber, 0, -1), 'red');
        }
    }
}

// Get all PHP translation files in the specified directory and its subdirectories
function getAllTranslationFiles($dir)
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    $files = [];
    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getFilename()) === 'language.php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function checkTranslationFile($file)
{
    if (! file_exists($file)) {
        return null;
    }

    $message = realpath($file);
    $lineNumber = '';
    $fileHandler = fopen($file, "r");

    if ($fileHandler) {
        $i = 0;
        while (($line = fgets($fileHandler)) !== false) {
            $i++;
            $lineTrim = trim($line);

            // Skip empty lines and comment lines
            if ($lineTrim === '' || str_starts_with($lineTrim, '//') || str_starts_with($lineTrim, '#')) {
                continue;
            }

            // Ensure that key => value pairs use only double quotes.
            // Invalid examples: 'key' => "value", "key" => 'value', 'key' => 'value'
            // Only the correct format is: "key" => "value",
            if (preg_match('/^(\s*)"[^"]*"\s*=>\s*"[^"]*"(?!,)\s*$/', $lineTrim) || preg_match('/(\'[^\']*\'\s*=>\s*".*?")|(".*?"\s*=>\s*\'[^\']*\')|(\'[^\']*\'\s*=>\s*\'[^\']*\')/', $lineTrim)) {
                $lineNumber .= $i . ",";
            }
        }
        fclose($fileHandler);
    }

    if (! empty($lineNumber)) {
        // Remove trailing comma
        $lineNumber = rtrim($lineNumber, ',');
        return color($message . ':', 'blue') . color($lineNumber, 'red');
    }
}
