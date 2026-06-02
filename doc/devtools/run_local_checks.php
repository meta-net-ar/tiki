<?php

$options = getopt('h', ['help', 'skip:']);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
Usage:
  php doc/devtools/run_local_checks.php [options]

Options:
  --skip=1,3,8   Skip checks by step number
  -h, --help     Show this help message

Examples:
  php doc/devtools/run_local_checks.php
  php doc/devtools/run_local_checks.php --skip=2,5,9

HELP;
    exit(0);
}

$skipSteps = [];

if (! empty($options['skip'])) {
    $skipSteps = array_map('intval', explode(',', $options['skip']));
    echo "Skipping steps: " . implode(', ', $skipSteps) . PHP_EOL . PHP_EOL;
}
require_once __DIR__ . '/get_base_commit.php';
$baseCommit = getBaseCommitOrAbort();

exec("git diff --name-only --diff-filter=d {$baseCommit} HEAD", $affectedFiles);

//exec('git diff --name-only --diff-filter=d origin/master...HEAD', $affectedFiles);

if (empty($affectedFiles)) {
    echo "✅ No relevant files changed. Skipping checks." . PHP_EOL;
    exit(0);
}

function filesByExtension(array $files, array $extensions): array
{
    return array_values(array_filter($files, function ($file) use ($extensions) {
        return in_array(pathinfo($file, PATHINFO_EXTENSION), $extensions, true);
    }));
}

function quoteFiles(array $files): string
{
    return implode(' ', array_map('escapeshellarg', $files));
}

function hasComposerChanges(array $files): bool
{
    foreach ($files as $file) {
        if (str_contains($file, 'composer.json') || str_contains($file, 'composer.lock')) {
            return true;
        }
    }
    return false;
}

$phpFiles = filesByExtension($affectedFiles, ['php']);
$jsFiles = filesByExtension($affectedFiles, ['js']);
$tplFiles = filesByExtension($affectedFiles, ['tpl']);

if (hasComposerChanges($affectedFiles)) {
    $steps = [
        ['Composer extension check', 'php doc/devtools/check_composer_extensions.php'],
        ['Composer validate', 'composer validate -d vendor_bundled --no-check-all'],
        ['Composer dry-run update', 'composer update -d vendor_bundled --dry-run'],
        ['Composer Operator check', 'php doc/devtools/check_caret_operator.php'],
    ];
}

if (! empty($phpFiles)) {
    $steps[] = [
        'PHPCS',
        'php vendor_bundled/vendor/squizlabs/php_codesniffer/bin/phpcs -s --runtime-set ignore_warnings_on_exit true --parallel=1 ' . quoteFiles($phpFiles),
    ];
    $steps[] = [
        'Static security check (PHP)',
        'php -d display_errors=On doc/devtools/securitycheck.php ' . quoteFiles($phpFiles),
    ];
    $steps[] = [
        'PHPLint',
        'php vendor_bundled/vendor/overtrue/phplint/bin/phplint ' . quoteFiles($phpFiles) . '--no-interaction --no-cache --progress path',
    ];
    $steps[] = [
        'Rector',
        'php bin/rector process --dry-run ' . quoteFiles($phpFiles),
    ];

    $steps[] = [
        'PHPStan',
        'php bin/phpstan --configuration=phpstan-tikiCi.neon analyse ' . quoteFiles($phpFiles),
    ];
}

if (! empty($tplFiles)) {
    $steps[] = [
        'SmartyLint',
        'php vendor_bundled/vendor/smarty/smarty-lint/smartyl -p --rules=doc/devtools/smartyl.rules.xml ' . quoteFiles($tplFiles),
    ];
    $steps[] = [
        'Smarty syntax check',
        'php doc/devtools/check_smarty_syntax.php ' . quoteFiles($tplFiles),
    ];
    $steps[] = ['Translation standards', 'php doc/devtools/check_template_translation_standards.php --all'];
}

if (! empty($jsFiles)) {
    $steps[] = [
        'ESLint',
        'npx eslint ' . quoteFiles($jsFiles),
    ];
}

$allAffected = quoteFiles($affectedFiles);

$steps[] = ['BOM encoding', 'php doc/devtools/check_bom_encoding.php ' . $allAffected];
$steps[] = ['Unix line ending', 'php doc/devtools/check_unix_ending_line.php ' . $allAffected];
$steps[] = ['Platform binaries', 'php doc/devtools/check_platform_binaries.php'];

$steps[] = ['SQL engine', 'php -d display_errors=On doc/devtools/check_sql_engine.php'];
$steps[] = ['Schema SQL drop', 'php -d display_errors=On doc/devtools/check_schema_sql_drop.php'];
$steps[] = ['Schema naming convention', 'php -d display_errors=On doc/devtools/check_schema_naming_convention.php'];
$steps[] = ['Check packages alphabetical list', 'php doc/devtools/check_alphabetical_list.php'];

$failedChecks = [];

foreach ($steps as $index => [$label, $cmd]) {
    $stepNumber = $index + 1;

    if (in_array($stepNumber, $skipSteps, true)) {
        echo "⏭️  Skipping step $stepNumber: $label" . PHP_EOL . PHP_EOL;
        continue;
    }

    echo "▶️ Step $stepNumber: $label" . PHP_EOL;
    echo "   $cmd" . PHP_EOL;

    $output = [];
    exec($cmd . ' 2>&1', $output, $code);

    if ($code !== 0) {
        echo PHP_EOL . "❌ FAILED step $stepNumber: $label" . PHP_EOL;

        foreach ($output as $line) {
            echo "   $line" . PHP_EOL;
        }

        echo PHP_EOL . "=====================================================" . PHP_EOL;
        echo "❌ Push blocked due to failure at step $stepNumber ($label)." . PHP_EOL;
        echo "Please fix the failing check above and try pushing again." . PHP_EOL;

        // ⚡ FAIL FAST: Exit immediately so the user doesn't waste time waiting
        exit(1);
    }

    echo PHP_EOL . "✅ PASSED: $label" . PHP_EOL . PHP_EOL;
    echo "-----------------------------------------------------------------" . PHP_EOL;
}
echo "=====================================================" . PHP_EOL;
echo "✅ All checks passed. Push allowed." . PHP_EOL . PHP_EOL;
exit(0);
