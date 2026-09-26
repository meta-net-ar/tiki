<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Used to automatically update all lang/*/language.php files when a English
// string is changed in Tiki source code
// This script is experimental. Always review the changes to language.php file before
// committing.
//
// Also see: src/devtools/mass_wording_corrections.pl
//

use Tiki\Lib\Language\LangStringEscaper;

if ($argc < 3) {
    die("\nUsage: php src/devtools/update_english_strings.php \"oldString\" \"newString\"\n\n");
}

set_include_path(get_include_path() . PATH_SEPARATOR . '../../');

require_once('lib/language/LangStringEscaper.php');

$oldString = LangStringEscaper::addPhpSlashes($argv[1]);
$newString = LangStringEscaper::addPhpSlashes($argv[2]);

$totalFiles = 0;
$updatedFiles = 0;

$iterator = new FilesystemIterator('lang/', FilesystemIterator::SKIP_DOTS);

echo "Processing languages: ";

foreach ($iterator as $dirInfo) {
    if (! $dirInfo->isDir()) {
        continue;
    }

    $filePath = $dirInfo->getPathname() . '/language.php';

    if (! file_exists($filePath)) {
        continue;
    }

    $totalFiles++;

    $content = file_get_contents($filePath);

    $pattern = '~^([ \t]*(?://[ \t]*)?)"' . preg_quote($oldString, '~') . '"([ \t]*=>[ \t]*)~m';
    $replacement = '$1"' . $newString . '"$2';

    $updatedContent = preg_replace($pattern, $replacement, $content, -1, $replacements);
    file_put_contents($filePath, $updatedContent);
    $updatedFiles++;
    echo ".";
}

echo "\n\nSummary\n";
printf("  Files checked:     %d\n", $totalFiles);
printf("  Files updated:     %d\n", $updatedFiles);
