<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Package Stability Checker for Tiki
 *
 * This script analyzes composer.json files across different Tiki versions to detect:
 * 1. Packages with unstable versions (dev, alpha, beta, RC)
 * 2. Actual version downgrades (e.g., from ^1.27 to ~1.8)
 * 3. Stability regressions (e.g., from stable to beta)
 *
  * Usage:
 *   php check_composer_stability.php [tiki-version1] [tiki-version2] ...
 *
 * Examples:
 *   php check_composer_stability.php              # Compare all available versions
 *   php check_composer_stability.php 24.x 27.x    # Compare only 24.x and 27.x
 *   php check_composer_stability.php 24.x 26.x master  # Compare specific versions
 *   php check_composer_stability.php 28.x current # Compare 28.x with current branch
 *   php check_composer_stability.php current 29.x master # Compare current branch with 29.x and master
 *
 * Available versions: 24.x, 27.x, 30.x, master
 *
 * Output:
 * - Lists packages with unstable versions
 * - Shows actual version downgrades with specific version comparisons
 * - Provides a summary of all detected issues
 *
 * Exit codes:
 *   0 - No stability issues detected
 *   1 - Current branch introduces stability issues (fail)
 *   2 - Only baseline issues present (warning)
 */

require_once __DIR__ . '/../../path_constants.php';
require_once TIKI_VENDOR_BUNDLED_PATH . '/autoload.php';

use Composer\Semver\VersionParser;
use Composer\Semver\Comparator;

/**
 * Global VersionParser instance for reuse across functions
 */
static $versionParser = null;

/**
 * Get or create the global VersionParser instance
 */
function getVersionParser()
{
    global $versionParser;
    if ($versionParser === null) {
        $versionParser = new VersionParser();
    }
    return $versionParser;
}

/**
 * Composer stability levels as defined in Composer's stability flags
 * @see https://getcomposer.org/doc/articles/versions.md#stabilities
 */
const STABILITY_LEVELS = [
    'dev' => 'dev',
    'alpha' => 'alpha',
    'beta' => 'beta',
    'RC' => 'RC',
    'stable' => 'stable'
];

/**
 * Get the target branch based on current Tiki version
 * This helps determine the logical order for version comparisons
 */
function getTargetBranch()
{
    // Get current Tiki version and extract the major.minor part
    $currentVersion = getCurrentTikiVersion();
    if ($currentVersion && preg_match('/^(\d+)\.(\d+)/', $currentVersion, $matches)) {
        $detectedBranch = $matches[1] . '.x';

        // Check if the detected branch actually exists in available versions
        $availableVersions = getAvailableTikiVersions();
        if (in_array($detectedBranch, $availableVersions)) {
            return $detectedBranch;
        }
    }

    // Fallback to master if detected branch doesn't exist
    return 'master';
}

/**
 * Get the current working branch name
 */
function getCurrentBranch()
{
    // In GitLab CI
    if (getenv('CI_COMMIT_REF_NAME')) {
        return getenv('CI_COMMIT_REF_NAME');
    }

    // For local development
    $gitOutput = shell_exec('git rev-parse --abbrev-ref HEAD 2>nul');
    if ($gitOutput && ! preg_match('/fatal|error/i', $gitOutput)) {
        return trim($gitOutput);
    }

    return null;
}

/**
 * Get the current running Tiki version
 */
function getCurrentTikiVersion()
{
    $tikiPath = __DIR__ . '/../..';
    $versionFile = $tikiPath . '/' . LIB_PATH . '/setup/twversion.class.php';
    if (file_exists($versionFile)) {
        require_once $versionFile;
        $TWV = new TWVersion();
        return $TWV->version;
    }
    return null;
}

/**
 * Get composer.json content safely
 */
function getComposerJson($source)
{
    if (empty($source['path']) && empty($source['url'])) {
        echo "Error: No valid path or URL for composer.json\n";
        return null;
    }

    if ($source['type'] === 'local' && ! empty($source['path'])) {
        if (! file_exists($source['path'])) {
            echo "Error: Local composer.json not found at {$source['path']}\n";
            return null;
        }
        $content = file_get_contents($source['path']);
        if ($content === false) {
            echo "Error: Could not read local composer.json\n";
            return null;
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Invalid JSON in composer.json: " . json_last_error_msg() . "\n";
            return null;
        }
        return $json;
    } elseif ($source['type'] === 'remote' && ! empty($source['url'])) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => ['User-Agent: PHP/Tiki-Stability-Check'],
                'timeout' => 15
            ]
        ]);
        $content = file_get_contents($source['url'], false, $context);
        if ($content === false) {
            echo "Error: Could not fetch remote composer.json from {$source['url']}\n";
            return null;
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "Error: Invalid JSON in remote composer.json: " . json_last_error_msg() . "\n";
            return null;
        }
        return $json;
    }
    return null;
}

/**
 * Get all available Tiki versions in chronological order
 */
function getAvailableTikiVersions()
{
    return ['24.x', '27.x', '30.x', 'master'];
}

/**
 * Get versions to check based on command line arguments and current context
 */
function getVersionsToCheck()
{
    global $argv, $isCiMode;
    $args = array_slice($argv, 1);

    $ciMode = false;
    $filteredArgs = [];

    foreach ($args as $arg) {
        if ($arg === '--ci') {
            $ciMode = true;
        } else {
            $filteredArgs[] = $arg;
        }
    }

    // Store CI mode globally
    global $isCiMode;
    $isCiMode = $ciMode;

    if (empty($filteredArgs)) {
        // No specific versions specified, use smart detection
        return getSmartVersionOrder();
    }

    $availableVersions = getAvailableTikiVersions();
    $currentBranch = getCurrentBranch();
    $versionsToCheck = [];

    foreach ($filteredArgs as $arg) {
        if ($arg === 'current') {
            // Replace 'current' with the actual current branch name
            if ($currentBranch) {
                $versionsToCheck[] = $currentBranch;
                if (! $ciMode) {
                    echo "Info: 'current' replaced with '{$currentBranch}'\n";
                }
            } else {
                if (! $ciMode) {
                    echo "Warning: Could not determine current branch, ignoring 'current' keyword\n";
                }
            }
        } elseif (in_array($arg, $availableVersions)) {
            $versionsToCheck[] = $arg;
        } else {
            if (! $ciMode) {
                echo "Warning: Ignoring invalid version '$arg'. Available versions are: " . implode(', ', $availableVersions) . " or 'current'\n";
            }
        }
    }

    if (empty($versionsToCheck)) {
        if (! $ciMode) {
            echo "Error: No valid versions specified. Using smart version ordering.\n";
        }
        return getSmartVersionOrder();
    }

    // Remove duplicates while preserving order
    $versionsToCheck = array_unique($versionsToCheck, SORT_STRING);

    // Sort versions chronologically based on their position in available versions
    usort($versionsToCheck, function ($a, $b) use ($availableVersions) {
        $order = array_flip($availableVersions);
        // If either version is not in available versions (e.g., current branch),
        // place it after the last available version for sorting purposes
        $posA = $order[$a] ?? count($availableVersions);
        $posB = $order[$b] ?? count($availableVersions);
        return $posA <=> $posB;
    });

    return $versionsToCheck;
}

/**
 * Get smart version ordering based on current branch and target branch
 * Returns versions in chronological order (oldest to newest) for proper comparison
 */
function getSmartVersionOrder()
{
    global $isCiMode;
    $targetBranch = getTargetBranch();
    $currentBranch = getCurrentBranch();
    $availableVersions = getAvailableTikiVersions();

    // If we can't determine the current branch, fall back to standard chronological ordering
    if (! $currentBranch || $currentBranch === $targetBranch) {
        return $availableVersions;
    }

    // Start with the standard chronological order (oldest to newest)
    $orderedVersions = $availableVersions;

    // Find the position of the target branch in the available versions
    $targetPosition = array_search($targetBranch, $availableVersions);
    if ($targetPosition === false) {
        // Target branch not found, fall back to standard ordering
        return $availableVersions;
    }

    // Insert the current branch after the target branch
    // This maintains the chronological order while placing feature branches logically
    if ($currentBranch !== $targetBranch) {
        $orderedVersions = array_merge(
            array_slice($orderedVersions, 0, $targetPosition + 1),
            [$currentBranch],
            array_slice($orderedVersions, $targetPosition + 1)
        );

    }

    // Filter out any versions that don't exist in available versions, but keep the current branch
    $orderedVersions = array_filter($orderedVersions, function ($version) use ($availableVersions, $currentBranch) {
        return in_array($version, $availableVersions) || $version === $currentBranch;
    });

    return array_values($orderedVersions);
}

/**
 * Extract stability level from a version string using Composer's parser
 * Handles various version constraint formats including:
 * - dev-master
 * - 1.0.*@dev
 * - ^2.1@RC
 * - ^1.0@beta
 */
function extractStabilityFromVersion($version)
{
    try {
        return getVersionParser()->parseStability($version);
    } catch (\UnexpectedValueException $e) {
        // Fallback for very unusual cases
        if (str_starts_with($version, 'dev-') || $version === 'dev-master') {
            return 'dev';
        }
        // For constraints with explicit stability flags (e.g. ^1.0@beta)
        if (preg_match('/@(dev|alpha|beta|RC|stable)$/i', $version, $matches)) {
            return strtolower($matches[1]);
        }
        return 'stable';
    }
}

/**
 * Compare package versions for stability
 */
function isLessStable($version1, $version2)
{
    try {
        $stability1 = extractStabilityFromVersion($version1);
        $stability2 = extractStabilityFromVersion($version2);

        // If stabilities differ, compare stability levels
        if ($stability1 !== $stability2) {
            $levels = ['dev', 'alpha', 'beta', 'RC', 'stable'];
            return array_search($stability1, $levels) < array_search($stability2, $levels);
        }

        // For same stability, compare actual versions
        $normalized1 = getVersionParser()->normalize($version1);
        $normalized2 = getVersionParser()->normalize($version2);
        return Comparator::lessThan($normalized1, $normalized2);
    } catch (\UnexpectedValueException $e) {
        // Fallback to simple string comparison if parsing fails
        return version_compare($version1, $version2) < 0;
    }
}

/**
 * Extract a normalized base version from a Composer version constraint for comparison purposes.
 *
 * This function attempts to extract a comparable version string from various Composer version
 * constraint formats. It handles:
 * - Normalized versions (e.g., "1.2.3")
 * - Dev branches (e.g., "dev-master", "dev-feature")
 * - Version constraints with stability flags (e.g., "^1.2@beta")
 * - Inequality constraints (e.g., ">=1.2")
 *
 * The function prioritizes using Composer's own normalization, falling back to regex-based
 * extraction only when necessary. Dev branches are returned as-is since they cannot be
 * normalized to semantic versions.
 *
 * @param string $constraint The version constraint to normalize
 * @return string A normalized version string suitable for comparison, or the original
 *                constraint if normalization fails
 * @throws \UnexpectedValueException Only if the constraint is completely invalid
 */
function getNormalizedBaseVersion(string $constraint): string
{
    $parser = getVersionParser();

    // Return raw dev branches as-is since they cannot be normalized
    if (str_starts_with($constraint, 'dev-') || $constraint === 'dev-master') {
        return $constraint;
    }

    try {
        // First try Composer's normalization
        $normalized = $parser->normalize($constraint);

        // Extract just the version part (without stability flags)
        if (preg_match('/^(\d+\.\d+\.\d+)(?:\-|$)/', $normalized, $matches)) {
            return $matches[1];
        }

        // Handle constraints with explicit stability flags
        if (preg_match('/^([\^~>=<]+\s*\d+\.\d+(?:\.\d+)?(?:\.[x*])?)(?:@[a-z]+)?$/i', $constraint, $matches)) {
            return $matches[1];
        }

        return $normalized;
    } catch (\UnexpectedValueException $e) {
        // Fallback to simple string extraction for malformed constraints
        $base = preg_replace(['/^[\^~>=<]+\s*/', '/@[a-z]+$/i'], '', $constraint);
        if (preg_match('/^\d+\.\d+(?:\.\d+)?/', $base, $matches)) {
            return $matches[0];
        }
        return $base;
    }
}

/**
 * Get numeric stability level from stability string
 */
function getStabilityNumeric($stability)
{
    $levels = [
        'dev' => 0,
        'alpha' => 1,
        'beta' => 2,
        'RC' => 3,
        'stable' => 4
    ];
    return $levels[$stability] ?? 4;
}

/**
 * Get version constraint type for stable versions
 */
function getVersionConstraintType($version)
{
    // Skip non-stable versions
    if (extractStabilityFromVersion($version) !== 'stable') {
        return '';
    }

    try {
        $normalized = getVersionParser()->normalize($version);
        $parts = explode('.', $normalized);

        if (count($parts) >= 3 && is_numeric($parts[2])) {
            return 'strict version';
        }
        return 'flexible version';
    } catch (\UnexpectedValueException $e) {
        return '';
    }
}

/**
 * Build complete package history across all versions
 * Using the efficient logic from the old code
 */
function buildPackageHistory($versionsToCheck)
{
    global $isCiMode;
    $packageHistory = [];
    $currentBranch = getCurrentBranch();

    foreach ($versionsToCheck as $version) {
        $composer = null;

        // For the current branch, use local composer.json if available
        if ($version === $currentBranch) {
            $localComposerPath = PRIMARY_COMPOSERJSON_FILE_PATH;
            if (file_exists($localComposerPath)) {
                $composer = getComposerJson([
                    'path' => $localComposerPath,
                    'type' => 'local'
                ]);
                if ($composer) {
                    echo "Using local composer.json for current branch: {$currentBranch}\n";
                }
            }
        }

        if (! $composer) {
            $composer = getComposerJson([
                'url' => "https://gitlab.com/tikiwiki/tiki/-/raw/$version/" . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . "/composer.json",
                'type' => 'remote'
            ]);
        }

        if (! $composer || ! isset($composer['require'])) {
            echo "Warning: Could not get composer.json for version $version\n";
            continue;
        }

        foreach ($composer['require'] as $package => $versionConstraint) {
            if ($package === 'php' || str_starts_with($package, 'ext-')) {
                continue;
            }

            if (! isset($packageHistory[$package])) {
                $packageHistory[$package] = [
                    '_metadata' => [
                        'has_regressions' => false,
                        'has_unstable' => false,
                        'has_stable_regressions' => false,
                        'max_stability' => 0,
                        'versions' => []
                    ]
                ];
            }

            $packageHistory[$package][$version] = $versionConstraint;
            $packageHistory[$package]['_metadata']['versions'][] = [
                'tiki_version' => $version,
                'constraint' => $versionConstraint,
                'stability' => extractStabilityFromVersion($versionConstraint)
            ];
        }
    }

    foreach ($packageHistory as $package => &$history) {
        $versions = $history['_metadata']['versions'];

        // Sort versions chronologically based on the order they were provided
        usort($versions, function ($a, $b) use ($versionsToCheck) {
            $order = array_flip($versionsToCheck);
            return $order[$a['tiki_version']] <=> $order[$b['tiki_version']];
        });

        $highestStability = 0;
        $previousStability = null;
        $highestBaseVersion = null;
        $highestBaseVersionSeen = null;

        foreach ($versions as $ver) {
            $currentStability = extractStabilityFromVersion($ver['constraint']);
            $currentVersion = $ver['constraint'];

            // Track highest stability reached
            if (getStabilityNumeric($currentStability) > $highestStability) {
                $highestStability = getStabilityNumeric($currentStability);
            }

            // Extract base version
            $currentBase = getNormalizedBaseVersion($currentVersion);

            // Track highest base version seen
            if ($currentBase && (! $highestBaseVersionSeen || version_compare($currentBase, $highestBaseVersionSeen) > 0)) {
                $highestBaseVersionSeen = $currentBase;
                $highestBaseVersion = $currentVersion;
            }

            // Check for stability regressions
            if (
                $previousStability !== null &&
                getStabilityNumeric($currentStability) < getStabilityNumeric($previousStability)
            ) {
                $history['_metadata']['has_regressions'] = true;
                if ($currentStability === 'stable' && $previousStability === 'stable') {
                    $history['_metadata']['has_stable_regressions'] = true;
                }
            }

            // Check for version regressions (only for stable versions)
            if ($currentStability === 'stable' && $currentBase && $highestBaseVersionSeen) {
                $highestBase = getNormalizedBaseVersion($highestBaseVersion);
                if ($highestBase && version_compare($currentBase, $highestBase) < 0) {
                    // Check if this is just a constraint style change
                    $currentParts = explode('.', $currentBase);
                    $highestParts = explode('.', $highestBase);
                    if ($currentParts[0] === $highestParts[0] && $currentParts[1] === $highestParts[1]) {
                        continue; // Just a constraint style change
                    }
                    $history['_metadata']['has_regressions'] = true;
                    $history['_metadata']['has_stable_regressions'] = true;
                }
            }

            // Flag if any version is unstable
            if ($currentStability !== 'stable') {
                $history['_metadata']['has_unstable'] = true;
            }

            $previousStability = $currentStability;
        }

        $history['_metadata']['max_stability'] = $highestStability;
    }

    return $packageHistory;
}

/**
 * Print package version history with stability information
 */
function printPackageHistory(string $package, array $history, string $icon = '⚠️'): void
{
    echo "\n{$icon} Package: {$package}\n";
    echo "   Versions:\n";

    foreach ($history as $tikiVersion => $constraint) {
        if ($tikiVersion === '_metadata') {
            continue;
        }

        $stability = extractStabilityFromVersion($constraint);
        $stabilityText = $stability;

        if ($stability === 'stable') {
            $constraintType = getVersionConstraintType($constraint);
            $stabilityText .= " - " . $constraintType;
        }

        echo "   - Tiki {$tikiVersion}: {$constraint} ({$stabilityText})\n";
    }
}

/**
 * Generate JUnit XML report for CI
 */
function generateJUnitReport($issuesByPackage, $stableRegressions)
{
    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->setIndentString('  ');

    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('testsuites');
    $xml->writeAttribute('name', 'Composer Stability Check');

    $xml->startElement('testsuite');
    $xml->writeAttribute('name', 'Package Stability');
    $xml->writeAttribute('tests', count($issuesByPackage) + count($stableRegressions));

    $failures = 0;

    // Add test cases for packages with issues
    foreach ($issuesByPackage as $package => $data) {
        $xml->startElement('testcase');
        $xml->writeAttribute('name', $package);
        $xml->writeAttribute('classname', 'PackageStability');

        if ($data['metadata']['has_regressions'] || $data['metadata']['has_unstable']) {
            $xml->startElement('failure');
            $xml->writeAttribute('type', 'StabilityIssue');
            $xml->writeAttribute('message', 'Stability issues detected');

            $failureMessage = "Package: {$package}\n";
            if ($data['metadata']['has_regressions']) {
                $failureMessage .= "- Stability regression detected\n";
            }
            if ($data['metadata']['has_unstable']) {
                $failureMessage .= "- Unstable versions used\n";
            }

            $xml->text($failureMessage);
            $xml->endElement(); // failure
            $failures++;
        }

        $xml->endElement(); // testcase
    }

    // Add test cases for packages with stable regressions
    foreach ($stableRegressions as $package => $data) {
        $xml->startElement('testcase');
        $xml->writeAttribute('name', $package);
        $xml->writeAttribute('classname', 'PackageStability');

        $xml->startElement('failure');
        $xml->writeAttribute('type', 'VersionDowngrade');
        $xml->writeAttribute('message', 'Version downgrade detected');
        $xml->text("Package: {$package} - Version downgrade detected");
        $xml->endElement(); // failure

        $failures++;
        $xml->endElement(); // testcase
    }

    $xml->writeAttribute('failures', $failures);
    $xml->endElement(); // testsuite
    $xml->endElement(); // testsuites

    return $xml->outputMemory();
}

/**
 * Generate report with complete version history
 */
function generateReport($packageHistory, $versionsToCheck)
{
    global $isCiMode;

    $currentVersion = getCurrentTikiVersion();

    if (! $isCiMode) {
        echo "\nCurrent Tiki Version: {$currentVersion}\n";
        echo "\nPackage Stability Analysis:\n==================\n";
    }

    $hasIssues = false;
    $issuesByPackage = [];
    $stableRegressions = [];

    foreach ($packageHistory as $package => $history) {
        $metadata = $history['_metadata'];
        unset($history['_metadata']);

        // Track packages with stable regressions separately
        if ($metadata['has_stable_regressions']) {
            $versions = $metadata['versions'];
            usort($versions, function ($a, $b) use ($versionsToCheck) {
                $order = array_flip($versionsToCheck);
                return $order[$a['tiki_version']] <=> $order[$b['tiki_version']];
            });

            $regressionInfo = null;
            for ($i = 1; $i < count($versions); $i++) {
                $prev = $versions[$i - 1]['constraint'];
                $current = $versions[$i]['constraint'];
                $prevVersion = $versions[$i - 1]['tiki_version'];
                $currentVersion = $versions[$i]['tiki_version'];

                $prevBase = getNormalizedBaseVersion($prev);
                $currentBase = getNormalizedBaseVersion($current);

                if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
                    $prevParts = explode('.', $prevBase);
                    $currentParts = explode('.', $currentBase);
                    if ($prevParts[0] === $currentParts[0] && $prevParts[1] === $currentParts[1]) {
                        continue; // Just a constraint style change
                    }

                    $regressionInfo = [
                        'package' => $package,
                        'from_version' => $prev,
                        'from_tiki' => $prevVersion,
                        'to_version' => $current,
                        'to_tiki' => $currentVersion
                    ];
                    break;
                }
            }

            if ($regressionInfo) {
                $stableRegressions[$package] = [
                    'history' => $history,
                    'metadata' => $metadata,
                    'regression_info' => $regressionInfo
                ];
                continue;
            }
        }

        // Add to issuesByPackage if it has regressions or unstable versions
        if ($metadata['has_regressions'] || $metadata['has_unstable']) {
            $regressionInfo = null;
            if ($metadata['has_regressions']) {
                $versions = $metadata['versions'];
                usort($versions, function ($a, $b) use ($versionsToCheck) {
                    $order = array_flip($versionsToCheck);
                    return $order[$a['tiki_version']] <=> $order[$b['tiki_version']];
                });

                for ($i = 1; $i < count($versions); $i++) {
                    $prev = $versions[$i - 1]['constraint'];
                    $current = $versions[$i]['constraint'];
                    $prevVersion = $versions[$i - 1]['tiki_version'];
                    $currentVersion = $versions[$i]['tiki_version'];

                    if (isLessStable($current, $prev)) {
                        $regressionInfo = [
                            'package' => $package,
                            'from_version' => $prev,
                            'from_tiki' => $prevVersion,
                            'to_version' => $current,
                            'to_tiki' => $currentVersion
                        ];
                        break;
                    }
                }
            }

            $hasIssues = true;
            $issuesByPackage[$package] = [
                'history' => $history,
                'metadata' => $metadata,
                'regression_info' => $regressionInfo
            ];
        }
    }

    if (! $isCiMode) {
        if (! $hasIssues && empty($stableRegressions)) {
            echo "\n✅ No stability issues detected across all versions!\n";
            return;
        }

        // First show packages with unstable versions or stability regressions
        if ($hasIssues) {
            echo "\n❌ Stability issues detected in packages:\n";

            foreach ($issuesByPackage as $package => $data) {
                printPackageHistory($package, $data['history']);

                if ($data['metadata']['has_regressions'] && isset($data['regression_info'])) {
                    $info = $data['regression_info'];
                    echo "\n   ❗ {$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}\n";
                }

                if ($data['metadata']['has_unstable']) {
                    echo "\n   ❗ Unstable versions used in this package's history\n";
                }
            }
        }

        // Then show packages with only stable versions but have actual regressions
        if (! empty($stableRegressions)) {
            echo "\n⚠️ Packages with stable version downgrades (potential regressions):\n";

            foreach ($stableRegressions as $package => $data) {
                printPackageHistory($package, $data['history'], '🔸');

                if (isset($data['regression_info'])) {
                    $info = $data['regression_info'];
                    echo "\n   ❗ {$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}\n";
                }
            }
        }

        echo "\nSummary of Issues:\n";
        echo "-----------------\n";
        echo "Packages with unstable versions: " .
            count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_unstable'])) . "\n";
        echo "Packages with stability regressions: " .
            count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_regressions'])) . "\n";
        echo "Packages with stable version downgrades: " .
            count($stableRegressions) . "\n";
    } else {
        // CI mode: output is handled in main execution to avoid duplication
        // Just generate JUnit report here
        if ($hasIssues || ! empty($stableRegressions)) {
            $junitReport = generateJUnitReport($issuesByPackage, $stableRegressions);
            file_put_contents('composer-stability-report.xml', $junitReport);
        } else {
            $junitReport = generateJUnitReport([], []);
            file_put_contents('composer-stability-report.xml', $junitReport);
        }
    }

    // Store results globally for exit code calculation
    global $globalIssuesByPackage, $globalStableRegressions;
    $globalIssuesByPackage = $issuesByPackage;
    $globalStableRegressions = $stableRegressions;
}

/**
 * Analyze current branch against baseline to determine if it introduces new issues
 * This helps CI pipelines fail only when the current branch is responsible
 */
function analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck)
{
    global $isCiMode;
    $currentBranch = getCurrentBranch();

    if (! $currentBranch || ! $isCiMode) {
        return ['shouldFail' => false, 'newIssues' => [], 'existingIssues' => []];
    }

    // Separate stable branches from current branch
    $stableVersions = array_filter($versionsToCheck, function ($version) use ($currentBranch) {
        return $version !== $currentBranch;
    });

    $currentBranchIssues = [];
    $existingIssues = [];

    foreach ($packageHistory as $package => $history) {
        $metadata = $history['_metadata'];

        // Check if this package has issues in stable branches (baseline)
        $hasBaselineIssues = false;
        $baselineStability = null;
        $baselineVersion = null;

        foreach ($stableVersions as $stableVersion) {
            if (isset($history[$stableVersion])) {
                $constraint = $history[$stableVersion];
                $stability = extractStabilityFromVersion($constraint);

                if ($stability !== 'stable') {
                    $hasBaselineIssues = true;
                    $baselineStability = $stability;
                    $baselineVersion = $constraint;
                }
            }
        }

        if ($hasBaselineIssues) {
            $existingIssues[] = [
                'package' => $package,
                'type' => 'existing_issue',
                'stability' => $baselineStability,
                'version' => $baselineVersion,
                'tiki_version' => 'baseline'
            ];
        }

        // Check for stability regressions in baseline (not involving current branch)
        if ($metadata['has_regressions']) {
            $versions = $metadata['versions'];
            usort($versions, function ($a, $b) use ($versionsToCheck) {
                $order = array_flip($versionsToCheck);
                return $order[$a['tiki_version']] <=> $order[$b['tiki_version']];
            });

            // Find stability regressions in baseline
            $hasBaselineRegression = false;
            for ($i = 1; $i < count($versions); $i++) {
                $prev = $versions[$i - 1];
                $current = $versions[$i];

                // Skip if current branch is involved (handled separately)
                if ($current['tiki_version'] === $currentBranch || $prev['tiki_version'] === $currentBranch) {
                    continue;
                }

                $prevStability = extractStabilityFromVersion($prev['constraint']);
                $currentStability = extractStabilityFromVersion($current['constraint']);

                if (getStabilityNumeric($currentStability) < getStabilityNumeric($prevStability)) {
                    $hasBaselineRegression = true;
                    break; // Only need to know if there's a regression, not count each one
                }
            }

            // Only add to existingIssues once per package if it has baseline regressions
            if ($hasBaselineRegression) {
                $existingIssues[] = [
                    'package' => $package,
                    'type' => 'existing_stability_regression',
                    'from_stability' => 'baseline',
                    'from_version' => 'baseline',
                    'from_tiki' => 'baseline',
                    'to_stability' => 'baseline',
                    'to_version' => 'baseline',
                    'to_tiki' => 'baseline'
                ];
            }
        }

        // Check current branch
        if (isset($history[$currentBranch])) {
            $currentConstraint = $history[$currentBranch];
            $currentStability = extractStabilityFromVersion($currentConstraint);

            if ($currentStability !== 'stable') {
                if ($hasBaselineIssues) {
                    // Issue exists in baseline - check if current branch makes it worse
                    if (getStabilityNumeric($currentStability) < getStabilityNumeric($baselineStability)) {
                        $currentBranchIssues[] = [
                            'package' => $package,
                            'type' => 'worsened_existing',
                            'baseline_stability' => $baselineStability,
                            'baseline_version' => $baselineVersion,
                            'current_stability' => $currentStability,
                            'current_version' => $currentConstraint
                        ];
                    }
                    // Note: existing issues are already counted above when scanning baseline
                } else {
                    // New issue introduced by current branch
                    $currentBranchIssues[] = [
                        'package' => $package,
                        'type' => 'new_issue',
                        'stability' => $currentStability,
                        'version' => $currentConstraint
                    ];
                }
            }
        }

        // Check for version downgrades in current branch and baseline
        if ($metadata['has_stable_regressions']) {
            $versions = $metadata['versions'];
            usort($versions, function ($a, $b) use ($versionsToCheck) {
                $order = array_flip($versionsToCheck);
                return $order[$a['tiki_version']] <=> $order[$b['tiki_version']];
            });

            // Find all downgrades (both in baseline and current branch)
            for ($i = 1; $i < count($versions); $i++) {
                $prev = $versions[$i - 1];
                $current = $versions[$i];

                    $prevBase = getNormalizedBaseVersion($prev['constraint']);
                    $currentBase = getNormalizedBaseVersion($current['constraint']);

                    if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
                    // Check if this is just a constraint style change
                    $prevParts = explode('.', $prevBase);
                    $currentParts = explode('.', $currentBase);
                    if ($prevParts[0] === $currentParts[0] && $prevParts[1] === $currentParts[1]) {
                        continue; // Just a constraint style change
                    }

                    if ($current['tiki_version'] === $currentBranch) {
                        // Current branch introduces the downgrade
                        $currentBranchIssues[] = [
                            'package' => $package,
                            'type' => 'version_downgrade',
                            'from_version' => $prev['constraint'],
                            'from_tiki' => $prev['tiki_version'],
                            'to_version' => $current['constraint'],
                            'to_tiki' => $currentBranch
                        ];
                                         } else {
                         // Downgrade exists in baseline
                         $existingIssues[] = [
                             'package' => $package,
                             'type' => 'existing_version_downgrade',
                             'from_version' => $prev['constraint'],
                             'from_tiki' => $prev['tiki_version'],
                             'to_version' => $current['constraint'],
                             'to_tiki' => $current['tiki_version']
                        ];
                    }
                }
            }
        }
    }

    // Aggregate existingIssues by package name to combine multiple issue types
    if (!empty($existingIssues)) {
        $aggregatedIssues = [];
        
        foreach ($existingIssues as $issue) {
            $package = $issue['package'];
            
            if (! isset($aggregatedIssues[$package])) {
                $aggregatedIssues[$package] = [
                    'package' => $package,
                    'types' => [],
                    'has_unstable' => false,
                    'has_regression' => false,
                    'has_downgrade' => false
                ];
            }
            
            // Track the different types of issues for this package
            if ($issue['type'] === 'existing_issue') {
                $aggregatedIssues[$package]['has_unstable'] = true;
            } elseif ($issue['type'] === 'existing_stability_regression') {
                $aggregatedIssues[$package]['has_regression'] = true;
            } elseif ($issue['type'] === 'existing_version_downgrade') {
                $aggregatedIssues[$package]['has_downgrade'] = true;
            }
        }
        
        // Convert back to array format for counting
        $existingIssues = array_values($aggregatedIssues);
    }

    $shouldFail = ! empty($currentBranchIssues);

    if ($isCiMode) {
        echo "Issues Introduced by Current Branch: " . count($currentBranchIssues) . "\n";
        echo "Existing Issues in Baseline: " . count($existingIssues) . "\n";
    }

    return [
        'shouldFail' => $shouldFail,
        'newIssues' => $currentBranchIssues,
        'existingIssues' => $existingIssues
    ];
}

// Main execution
$currentVersion = getCurrentTikiVersion();
if (! $currentVersion) {
    echo "Error: Could not determine current Tiki version.\n";
    exit(1);
}

$versionsToCheck = getVersionsToCheck();
$currentBranch = getCurrentBranch();
$targetBranch = getTargetBranch();

echo "Current Tiki Version: {$currentVersion}\n";
if ($currentBranch && $currentBranch !== $targetBranch) {
    echo "Current Branch: {$currentBranch}\n";
    echo "Target Branch: {$targetBranch}\n";
}
echo "Comparing Tiki versions: " . implode(', ', $versionsToCheck) . "\n";

$packageHistory = buildPackageHistory($versionsToCheck);

// Determine exit code based on CI mode and baseline analysis
if ($isCiMode) {
    $baselineAnalysis = analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck);

    // Handle CI mode output here to avoid duplication
    if ($baselineAnalysis['shouldFail']) {
        echo "\n## 🚨 Current Branch Introduces New Stability Issues\n\n";

        if (! empty($baselineAnalysis['newIssues'])) {
            echo "### Issues Introduced by Current Branch:\n";
            foreach ($baselineAnalysis['newIssues'] as $issue) {
                switch ($issue['type']) {
                    case 'new_issue':
                        echo "- **{$issue['package']}**: New unstable version `{$issue['version']}` ({$issue['stability']})\n";
                        break;
                    case 'worsened_existing':
                        echo "- **{$issue['package']}**: Worsened stability from `{$issue['baseline_version']}` ({$issue['baseline_stability']}) to `{$issue['version']}` ({$issue['stability']})\n";
                        break;
                    case 'version_downgrade':
                        echo "- **{$issue['package']}**: Version downgrade from `{$issue['from_version']}` to `{$issue['to_version']}\n";
                        break;
                }
            }
        }

        echo "\n**Pipeline Failed**: Current branch introduces stability issues that must be fixed.\n";
        echo "**Tip**: To see detailed issue information, run `php doc/devtools/check_composer_stability.php` (without --ci) from your project root.\n";
    } elseif (! empty($baselineAnalysis['existingIssues'])) {
        echo "\n## ⚠️ Stability Issues Detected (Existing in Baseline)\n\n";

        // Separate stability issues from version downgrades
        $stabilityIssues = [];
        $versionDowngrades = [];

        foreach ($baselineAnalysis['existingIssues'] as $issue) {
            if (isset($issue['has_downgrade']) && $issue['has_downgrade']) {
                $versionDowngrades[] = $issue;
            }

            // Check if package has stability issues (unstable or regression)
            if ((isset($issue['has_unstable']) && $issue['has_unstable']) || 
                (isset($issue['has_regression']) && $issue['has_regression'])) {
                $stabilityIssues[] = $issue;
            }
        }

        if (! empty($stabilityIssues)) {
            echo "### Stability Issues (Existing):\n";
            foreach ($stabilityIssues as $issue) {
                echo "- **{$issue['package']}**: ";
                $issueTypes = [];

                if ($issue['has_regression']) {
                    $issueTypes[] = "Stability regression detected";
                }
                if ($issue['has_unstable']) {
                    $issueTypes[] = "Unstable versions used";
                }

                echo implode(" & ", $issueTypes);
                echo "\n";
            }
        }

        if (! empty($versionDowngrades)) {
            echo "\n### Version Downgrades (Existing):\n";
            foreach ($versionDowngrades as $issue) {
                echo "- **{$issue['package']}**: Version downgrade detected\n";
            }
        }

        echo "\n**Pipeline Warning**: Baseline stability issues detected, but current branch does not introduce new ones.\n";
        echo "**Note**: These issues should be addressed in separate maintenance work.\n";
        echo "**Tip**: To see detailed issue information, run `php doc/devtools/check_composer_stability.php` (without --ci) from your project root.\n";
    } else {
        echo "\n## ✅ No Composer stability issues detected\n";
        echo "**Tip**: To see detailed issue information, run `php doc/devtools/check_composer_stability.php` (without --ci) from your project root.\n";
    }
    
    // Generate JUnit XML report for CI artifacts
    // Convert baseline analysis to JUnit format
    $junitIssues = [];
    $junitRegressions = [];
    
    foreach ($baselineAnalysis['existingIssues'] as $issue) {
        $package = $issue['package'];
        if (!isset($junitIssues[$package])) {
            $junitIssues[$package] = [
                'metadata' => [
                    'has_regressions' => false,
                    'has_unstable' => false
                ]
            ];
        }
        
        if (isset($issue['has_regression']) && $issue['has_regression']) {
            $junitIssues[$package]['metadata']['has_regressions'] = true;
        }
        if (isset($issue['has_unstable']) && $issue['has_unstable']) {
            $junitIssues[$package]['metadata']['has_unstable'] = true;
        }
    }
    
    $junitReport = generateJUnitReport($junitIssues, []);
    file_put_contents('composer-stability-report.xml', $junitReport);
    
    // Exit codes:
    // 1 = current branch introduces issues (fail)
    // 2 = only baseline issues present (mark as warning in CI)
    // 0 = no issues
    $exitCode = 0;
    if ($baselineAnalysis['shouldFail']) {
        $exitCode = 1;
    } elseif (! empty($baselineAnalysis['existingIssues'])) {
        $exitCode = 2;
    }

    exit($exitCode);
} else {
    // Local mode: generate report and fail if any issues exist
    generateReport($packageHistory, $versionsToCheck);
    exit(empty($globalIssuesByPackage) && empty($globalStableRegressions) ? 0 : 1);
}
