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
 * Exit codes (CI mode with --ci flag):
 *   0 - No stability issues detected OR all existing issues are [MITIGATED]
 *   1 - Current branch introduces new issues OR baseline has unmitigated issues [WARNING] (fail)
 */

require_once __DIR__ . '/../../path_constants.php';
require_once TIKI_VENDOR_BUNDLED_PATH . '/autoload.php';

use Composer\Semver\VersionParser;
use Composer\Semver\Comparator;

static $versionParser = null;
$isCiMode = false;

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
    $currentVersion = getCurrentTikiVersion();
    if ($currentVersion && preg_match('/^(\d+)\.(\d+)/', $currentVersion, $matches)) {
        $detectedBranch = $matches[1] . '.x';
        $availableVersions = getAvailableTikiVersions();
        if (in_array($detectedBranch, $availableVersions)) {
            return $detectedBranch;
        }
    }

    return 'master';
}

/**
 * Get the current working branch name
 */
function getCurrentBranch()
{
    if (getenv('CI_COMMIT_REF_NAME')) {
        return getenv('CI_COMMIT_REF_NAME');
    }

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
        return logError("No valid path or URL for composer.json");
    }

    if ($source['type'] === 'local' && ! empty($source['path'])) {
        if (! file_exists($source['path'])) {
            return logError("Local composer.json not found at {$source['path']}");
        }
        $content = file_get_contents($source['path']);
        if ($content === false) {
            return logError("Could not read local composer.json");
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorCode = json_last_error();
            $errorMsg = "Invalid JSON in composer.json: " . json_last_error_msg();
            $errorMsg .= "\nFile: {$source['path']}";
            $errorMsg .= "\nError code: {$errorCode}";
            return logError($errorMsg);
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
            return logError("Could not fetch remote composer.json from {$source['url']}");
        }
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorCode = json_last_error();
            return logError(
                "Invalid JSON in remote composer.json: " . json_last_error_msg() .
                "\nURL: {$source['url']}" .
                "\nError code: {$errorCode}"
            );
        }
        return $json;
    }
    return null;
}

/**
 * Get stability-ignore list from a composer.json
 */
function getStabilityIgnoreList($composerJson)
{
    if (! $composerJson || ! isset($composerJson['extra']['stability-ignore'])) {
        return [];
    }

    return $composerJson['extra']['stability-ignore'];
}

/**
 * Build stability ignore map for all versions
 */
function buildStabilityIgnoreMap($packageHistory)
{
    $ignoreMap = [];

    if (isset($packageHistory['_composer_json'])) {
        foreach ($packageHistory['_composer_json'] as $version => $composerJson) {
            $ignoreMap[$version] = getStabilityIgnoreList($composerJson);
        }
    }

    return $ignoreMap;
}

/**
 * Check if a package is ignored for stability issues in a specific version
 */
function isPackageIgnored($package, $version, $ignoreMap)
{
    return isset($ignoreMap[$version][$package]);
}

/**
 * Get ignore reason for a package in a specific version
 */
function getIgnoreReason($package, $version, $ignoreMap)
{
    if (! isPackageIgnored($package, $version, $ignoreMap)) {
        return null;
    }

    $ignoreData = $ignoreMap[$version][$package];
    $reason = isset($ignoreData['reason']) ? $ignoreData['reason'] : 'No reason provided';
    $mitigation = isset($ignoreData['mitigation']) ? $ignoreData['mitigation'] : 'No mitigation details';

    return [
        'reason' => $reason,
        'mitigation' => $mitigation,
        'reviewed' => isset($ignoreData['reviewed']) ? $ignoreData['reviewed'] : 'Unknown'
    ];
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
            if ($currentBranch) {
                $versionsToCheck[] = $currentBranch;
                logInfo("'current' replaced with '{$currentBranch}'");
            } else {
                logWarning("Could not determine current branch, ignoring 'current' keyword");
            }
        } elseif (in_array($arg, $availableVersions)) {
                $versionsToCheck[] = $arg;
        } else {
                logWarning("Ignoring invalid version '$arg'. Available versions are: " . implode(', ', $availableVersions) . " or 'current'");
        }
    }

    if (empty($versionsToCheck)) {
        logError("No valid versions specified. Using smart version ordering.");
        return getSmartVersionOrder();
    }

    $versionsToCheck = array_unique($versionsToCheck, SORT_STRING);

    usort($versionsToCheck, function ($a, $b) use ($availableVersions) {
        $order = array_flip($availableVersions);
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

    if (! $currentBranch || $currentBranch === $targetBranch) {
        return $availableVersions;
    }

    $orderedVersions = $availableVersions;

    $targetPosition = array_search($targetBranch, $availableVersions);
    if ($targetPosition === false) {
        return $availableVersions;
    }

    if ($currentBranch !== $targetBranch) {
        $orderedVersions = array_merge(
            array_slice($orderedVersions, 0, $targetPosition + 1),
            [$currentBranch],
            array_slice($orderedVersions, $targetPosition + 1)
        );
    }

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

        if ($stability1 !== $stability2) {
            $levels = array_keys(STABILITY_LEVELS);
            return array_search($stability1, $levels) < array_search($stability2, $levels);
        }

        $normalized1 = getVersionParser()->normalize($version1);
        $normalized2 = getVersionParser()->normalize($version2);
        return Comparator::lessThan($normalized1, $normalized2);
    } catch (\UnexpectedValueException $e) {
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

    if (str_starts_with($constraint, 'dev-') || $constraint === 'dev-master') {
        return $constraint;
    }

    try {
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
    $levels = array_keys(STABILITY_LEVELS);
    $numericLevels = array_flip($levels);
    return $numericLevels[$stability] ?? 4;
}

/**
 * Get version constraint type for stable versions
 */
function getVersionConstraintType($version)
{
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
 * Sort versions by Tiki version order
 */
function sortVersionsByTikiOrder($versions, $versionsToCheck)
{
    if (empty($versions) || empty($versionsToCheck)) {
        return $versions;
    }

    $firstVersion = reset($versions);
    if (! is_array($firstVersion) || ! isset($firstVersion['tiki_version'])) {
        return $versions;
    }

    usort($versions, function ($a, $b) use ($versionsToCheck) {
        $order = array_flip($versionsToCheck);

        $aVersion = $a['tiki_version'] ?? '';
        $bVersion = $b['tiki_version'] ?? '';
        if (! isset($order[$aVersion])) {
            return 1;
        }
        if (! isset($order[$bVersion])) {
            return -1;
        }

        return $order[$aVersion] <=> $order[$bVersion];
    });
    return $versions;
}

/**
 * Check if a version change is just a constraint style change
 */
function isConstraintStyleChange($prevBase, $currentBase)
{
    if (! $prevBase || ! $currentBase) {
        return false;
    }
    $prevParts = explode('.', $prevBase);
    $currentParts = explode('.', $currentBase);
    return $prevParts[0] === $currentParts[0] && $prevParts[1] === $currentParts[1];
}

/**
 * Determine package status (warnings and mitigation)
 */
function determinePackageStatus($package, $history, $ignoreMap)
{
    $hasWarnings = false;
    $allVersionsMitigated = true;
    $hasUnstableVersions = false;

    foreach ($history as $tikiVersion => $constraint) {
        if ($tikiVersion === '_metadata') {
            continue;
        }

        $stability = extractStabilityFromVersion($constraint);
        $versionMitigated = isPackageIgnored($package, $tikiVersion, $ignoreMap);

        if ($stability !== 'stable') {
            $hasUnstableVersions = true;

            if (! $versionMitigated) {
                $hasWarnings = true;
            }
        }

        if ($stability !== 'stable' && ! $versionMitigated) {
            $allVersionsMitigated = false;
        }
    }

    if (! $hasUnstableVersions) {
        $allVersionsMitigated = false;
    }

    return ['hasWarnings' => $hasWarnings, 'allVersionsMitigated' => $allVersionsMitigated];
}

/**
 * Check if a package is ignored in baseline versions
 */
function isPackageIgnoredInBaseline($package, $versionsToCheck, $ignoreMap)
{
    $currentBranch = getCurrentBranch();
    if (! $currentBranch) {
        return false;
    }

    foreach ($versionsToCheck as $version) {
        if ($version !== $currentBranch && isPackageIgnored($package, $version, $ignoreMap)) {
            return true;
        }
    }
    return false;
}

/**
 * Create regression info array
 */
function createRegressionInfo($package, $prev, $current, $prevVersion, $currentVersion)
{
    return [
        'package' => $package,
        'from_version' => $prev,
        'from_tiki' => $prevVersion,
        'to_version' => $current,
        'to_tiki' => $currentVersion
    ];
}

/**
 * Check if version downgrade is mitigated
 * Only the downgrade target (toVersion) needs to be mitigated, not the source version
 */
function isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap)
{
    return isPackageIgnored($package, $toVersion, $ignoreMap);
}

/**
 * Build JUnit report data structure from existing issues
 */
function buildJUnitReportData($existingIssues)
{
    $junitIssues = [];

    foreach ($existingIssues as $issue) {
        $package = $issue['package'];
        if (! isset($junitIssues[$package])) {
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

    return $junitIssues;
}

/**
 * Centralized error handling functions
 */

/**
 * Log an error message with consistent formatting
 * @param string $message The error message
 * @param array $context Additional context data
 * @param mixed $returnValue Value to return (default: null)
 * @return mixed The return value
 */
function logError($message, $context = [], $returnValue = null)
{
    global $isCiMode;

    if (! $isCiMode) {
        echo "Error: {$message}\n";
    }

    return $returnValue;
}

/**
 * Log a warning message with consistent formatting
 * @param string $message The warning message
 * @param array $context Additional context data
 * @param mixed $returnValue Value to return (default: null)
 * @return mixed The return value
 */
function logWarning($message, $context = [], $returnValue = null)
{
    global $isCiMode;

    if (! $isCiMode) {
        echo "Warning: {$message}\n";
    }

    return $returnValue;
}

/**
 * Log an info message with consistent formatting
 * @param string $message The info message
 * @param array $context Additional context data
 * @param mixed $returnValue Value to return (default: null)
 * @return mixed The return value
 */
function logInfo($message, $context = [], $returnValue = null)
{
    global $isCiMode;

    if (! $isCiMode) {
        echo "Info: {$message}\n";
    }

    return $returnValue;
}

/**
 * Build complete package history across all versions
 */
function buildPackageHistory($versionsToCheck)
{
    global $isCiMode;
    $packageHistory = [];
    $currentBranch = getCurrentBranch();

    global $globalComposerData;
    $globalComposerData = fetchComposerDataForVersions($versionsToCheck, $currentBranch);
    $composerData = $globalComposerData;

    $packageHistory = buildInitialPackageStructure($composerData);

    analyzePackageStabilityAndRegressions($packageHistory, $versionsToCheck);

    return $packageHistory;
}

/**
 * Fetch composer.json data for all versions
 */
function fetchComposerDataForVersions($versionsToCheck, $currentBranch)
{
    $composerData = [];

    foreach ($versionsToCheck as $version) {
        $composer = null;

        if ($version === $currentBranch) {
            $localComposerPath = PRIMARY_COMPOSERJSON_FILE_PATH;
            if (file_exists($localComposerPath)) {
                $composer = getComposerJson([
                    'path' => $localComposerPath,
                    'type' => 'local'
                ]);
                if ($composer) {
                    logInfo("Using local composer.json for current branch: {$currentBranch}");
                } else {
                    logError("Failed to parse local composer.json. Please check for JSON syntax errors.");
                    logError("File: {$localComposerPath}");
                    logError("You can validate your JSON at: https://jsonlint.com/");
                }
            } else {
                logError("Local composer.json not found at: {$localComposerPath}");
            }
        } else {
            $composer = getComposerJson([
                'url' => "https://gitlab.com/tikiwiki/tiki/-/raw/$version/" . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . "/composer.json",
                'type' => 'remote'
            ]);
        }

        if (! $composer || ! isset($composer['require'])) {
            logWarning("Could not get composer.json for version $version");
            continue;
        }

        $composerData[$version] = $composer;
    }

    return $composerData;
}

/**
 * Get packages changed between baseline and current branch
 */
function getTouchedPackages($composerData, $currentBranch)
{
    $touched = [];

    if (! isset($composerData[$currentBranch])) {
        return $touched;
    }

    $versions = array_keys($composerData);
    $baseline = null;

    foreach ($versions as $version) {
        if ($version === $currentBranch) {
            break;
        }
        $baseline = $version;
    }

    if (! $baseline || ! isset($composerData[$baseline])) {
        return $touched;
    }

    $currentPackages = $composerData[$currentBranch]['require'] ?? [];
    $baselinePackages = $composerData[$baseline]['require'] ?? [];

    $allPackages = array_unique(array_merge(
        array_keys($currentPackages),
        array_keys($baselinePackages)
    ));

    foreach ($allPackages as $package) {
        if ($package === 'php' || str_starts_with($package, 'ext-')) {
            continue;
        }

        $currentVersion = $currentPackages[$package] ?? null;
        $baselineVersion = $baselinePackages[$package] ?? null;

        if ($currentVersion !== $baselineVersion) {
            $touched[$package] = [
                'from' => $baselineVersion,
                'to' => $currentVersion
            ];
        }
    }

    return $touched;
}

/**
 * Build initial package structure from composer data
 */
function buildInitialPackageStructure($composerData)
{
    $packageHistory = [];

    $packageHistory['_composer_json'] = $composerData;

    foreach ($composerData as $version => $composer) {
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

    return $packageHistory;
}

/**
 * Analyze package stability and regressions
 */
function analyzePackageStabilityAndRegressions(&$packageHistory, $versionsToCheck)
{
    foreach ($packageHistory as $package => &$history) {
        if (! isset($history['_metadata']) || ! isset($history['_metadata']['versions'])) {
            continue;
        }

        $versions = $history['_metadata']['versions'];

        $versions = sortVersionsByTikiOrder($versions, $versionsToCheck);

        $highestStability = 0;
        $previousStability = null;
        $highestBaseVersion = null;
        $highestBaseVersionSeen = null;

        foreach ($versions as $ver) {
            $currentStability = extractStabilityFromVersion($ver['constraint']);
            $currentVersion = $ver['constraint'];

            if (getStabilityNumeric($currentStability) > $highestStability) {
                $highestStability = getStabilityNumeric($currentStability);
            }

            $currentBase = getNormalizedBaseVersion($currentVersion);

            if ($currentBase && (! $highestBaseVersionSeen || version_compare($currentBase, $highestBaseVersionSeen) > 0)) {
                $highestBaseVersionSeen = $currentBase;
                $highestBaseVersion = $currentVersion;
            }

            if (
                $previousStability !== null &&
                getStabilityNumeric($currentStability) < getStabilityNumeric($previousStability)
            ) {
                $history['_metadata']['has_regressions'] = true;
                if ($currentStability === 'stable' && $previousStability === 'stable') {
                    $history['_metadata']['has_stable_regressions'] = true;
                }
            }

            if ($currentStability === 'stable' && $currentBase && $highestBaseVersionSeen) {
                $highestBase = getNormalizedBaseVersion($highestBaseVersion);
                if ($highestBase && version_compare($currentBase, $highestBase) < 0) {
                    if (isConstraintStyleChange($currentBase, $highestBase)) {
                        continue;
                    }
                    $history['_metadata']['has_regressions'] = true;
                    $history['_metadata']['has_stable_regressions'] = true;
                }
            }

            if ($currentStability !== 'stable') {
                $history['_metadata']['has_unstable'] = true;
            }

            $previousStability = $currentStability;
        }

        $history['_metadata']['max_stability'] = $highestStability;
    }
}

/**
 * Print package version history with stability information
 */
function printPackageHistory(string $package, array $history, string $icon = '⚠️', $ignoreMap = [], $currentVersion = null): void
{
    $isIgnored = false;
    if ($currentVersion === 'baseline') {
        foreach ($ignoreMap as $version => $packages) {
            if (isset($packages[$package])) {
                $isIgnored = true;
                break;
            }
        }
    } elseif ($currentVersion && isset($ignoreMap[$currentVersion][$package])) {
        $isIgnored = true;
    }

    $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
    $hasWarnings = $packageStatus['hasWarnings'];
    $allVersionsMitigated = $packageStatus['allVersionsMitigated'];

    echo "\n{$icon} Package: {$package}";

    if ($hasWarnings) {
        echo " [WARNING]";
    } elseif ($allVersionsMitigated) {
        echo " [MITIGATED]";
    }
    echo "\n";
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

        $versionMitigated = false;
        if (isset($ignoreMap[$tikiVersion][$package])) {
            $versionMitigated = true;
        }

        echo "   - Tiki {$tikiVersion}: {$constraint} ({$stabilityText})";

        if ($versionMitigated) {
            echo " [MITIGATED]";
        } elseif ($stability !== 'stable') {
            echo " [WARNING]";
        }

        echo "\n";
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
function generateReport($packageHistory, $versionsToCheck, $ignoreMap = [])
{
    global $isCiMode;

    $currentVersion = getCurrentTikiVersion();

    if (! $isCiMode) {
        echo "\nCurrent Tiki Version: {$currentVersion}\n";
        echo "\nPackage Stability Analysis:\n==================\n";
    }

    $reportData = processPackageData($packageHistory, $versionsToCheck);
    $hasIssues = $reportData['hasIssues'];
    $issuesByPackage = $reportData['issuesByPackage'];
    $stableRegressions = $reportData['stableRegressions'];

    if (! $isCiMode) {
        if (! $hasIssues && empty($stableRegressions)) {
            echo "\n✅ No stability issues detected across all versions!\n";
            return;
        }

        generateStabilityIssuesReport($issuesByPackage, $versionsToCheck, $ignoreMap);
        generateStableRegressionsReport($stableRegressions, $versionsToCheck, $ignoreMap);
        generateIssuesSummary($issuesByPackage, $stableRegressions);
    }

    global $globalIssuesByPackage, $globalStableRegressions;
    $globalIssuesByPackage = $issuesByPackage;
    $globalStableRegressions = $stableRegressions;
}

/**
 * Process package data to identify issues and regressions
 */
function processPackageData($packageHistory, $versionsToCheck)
{
    $hasIssues = false;
    $issuesByPackage = [];
    $stableRegressions = [];

    foreach ($packageHistory as $package => $history) {
        if (! isset($history['_metadata'])) {
            continue;
        }

        $metadata = $history['_metadata'];
        unset($history['_metadata']);

        $regressionInfo = processStableRegressions($package, $metadata, $history, $versionsToCheck);
        if ($regressionInfo) {
            $stableRegressions[$package] = [
                'history' => $history,
                'metadata' => $metadata,
                'regression_info' => $regressionInfo
            ];
            continue;
        }

        $issueInfo = processStabilityIssues($package, $metadata, $history, $versionsToCheck);
        if ($issueInfo !== null) {
            $hasIssues = true;
            $issuesByPackage[$package] = [
                'history' => $history,
                'metadata' => $metadata,
                'regression_info' => $issueInfo
            ];
        }
    }

    return [
        'hasIssues' => $hasIssues,
        'issuesByPackage' => $issuesByPackage,
        'stableRegressions' => $stableRegressions
    ];
}

/**
 * Process stable regressions for a package
 */
function processStableRegressions($package, $metadata, $history, $versionsToCheck)
{
    if (! $metadata['has_stable_regressions']) {
        return null;
    }

    $versions = sortVersionsByTikiOrder($metadata['versions'], $versionsToCheck);

    for ($i = 1; $i < count($versions); $i++) {
        $prev = $versions[$i - 1]['constraint'];
        $current = $versions[$i]['constraint'];
        $prevVersion = $versions[$i - 1]['tiki_version'];
        $currentVersion = $versions[$i]['tiki_version'];

        $prevBase = getNormalizedBaseVersion($prev);
        $currentBase = getNormalizedBaseVersion($current);

        if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
            if (isConstraintStyleChange($prevBase, $currentBase)) {
                continue; // Just a constraint style change
            }

            return createRegressionInfo($package, $prev, $current, $prevVersion, $currentVersion);
        }
    }

    return null;
}

/**
 * Process stability issues for a package
 */
function processStabilityIssues($package, $metadata, $history, $versionsToCheck)
{
    if (! $metadata['has_regressions'] && ! $metadata['has_unstable']) {
        return null;
    }

    $regressionInfo = null;
    if ($metadata['has_regressions']) {
        $versions = sortVersionsByTikiOrder($metadata['versions'], $versionsToCheck);

        for ($i = 1; $i < count($versions); $i++) {
            $prev = $versions[$i - 1]['constraint'];
            $current = $versions[$i]['constraint'];
            $prevVersion = $versions[$i - 1]['tiki_version'];
            $currentVersion = $versions[$i]['tiki_version'];

            if (isLessStable($current, $prev)) {
                $regressionInfo = createRegressionInfo($package, $prev, $current, $prevVersion, $currentVersion);
                break;
            }
        }
    }

    // Return regression info if available, or an empty array if just unstable versions
    // This ensures packages with only unstable versions (no regressions) are still processed
    return $regressionInfo ?: [];
}

/**
 * Generate stability issues report
 */
function generateStabilityIssuesReport($issuesByPackage, $versionsToCheck, $ignoreMap)
{
    if (empty($issuesByPackage)) {
        return;
    }

    echo "\n❌ Stability issues detected in packages:\n";

    foreach ($issuesByPackage as $package => $data) {
        $isIgnored = isPackageIgnoredInBaseline($package, $versionsToCheck, $ignoreMap);

        printPackageHistory($package, $data['history'], '⚠️', $ignoreMap, $isIgnored ? 'baseline' : 'current');

        if ($data['metadata']['has_regressions'] && isset($data['regression_info'])) {
            $info = $data['regression_info'];
            echo "\n   ❗ {$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}\n";
        }

        if ($data['metadata']['has_unstable']) {
            echo "\n   ❗ Unstable versions used in this package's history\n";
        }
    }
}

/**
 * Generate stable regressions report
 */
function generateStableRegressionsReport($stableRegressions, $versionsToCheck, $ignoreMap)
{
    if (empty($stableRegressions)) {
        return;
    }

    echo "\n⚠️ Packages with stable version downgrades (potential regressions):\n";

    foreach ($stableRegressions as $package => $data) {
        $isIgnored = isPackageIgnoredInBaseline($package, $versionsToCheck, $ignoreMap);

                // For stable regressions, we need to determine the overall package status
                // based on whether any versions have warnings (unstable and not mitigated)
                // or if there are version downgrades that are not mitigated
        $packageStatus = determinePackageStatus($package, $data['history'], $ignoreMap);
        $hasWarnings = $packageStatus['hasWarnings'];
        $allVersionsMitigated = $packageStatus['allVersionsMitigated'];
        $hasUnmitigatedDowngrade = false;

        if (isset($data['regression_info'])) {
            $regressionInfo = $data['regression_info'];
            $fromVersion = $regressionInfo['from_tiki'];
            $toVersion = $regressionInfo['to_tiki'];

            $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap);
        }

        $icon = '🔸';
        $status = '';
        if ($hasWarnings || $hasUnmitigatedDowngrade) {
            $icon = '⚠️';
            $status = ' [WARNING]';
        } elseif ($allVersionsMitigated || (! $hasUnmitigatedDowngrade && isset($data['regression_info']))) {
            // Show [MITIGATED] if:
            // 1. All unstable versions are mitigated, OR
            // 2. There's a downgrade but it IS mitigated
            $status = ' [MITIGATED]';
        }

        echo "\n{$icon} Package: {$package}{$status}\n";
        echo "   Versions:\n";

        // Print individual version details
        printStableRegressionVersions($data, $package, $hasUnmitigatedDowngrade, $ignoreMap);

        if (isset($data['regression_info'])) {
            $info = $data['regression_info'];
            echo "\n   ❗ {$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}\n";
        }
    }
}

/**
 * Print version details for stable regressions
 */
function printStableRegressionVersions($data, $package, $hasUnmitigatedDowngrade, $ignoreMap)
{
    foreach ($data['history'] as $tikiVersion => $constraint) {
        if ($tikiVersion === '_metadata') {
            continue;
        }

        $stability = extractStabilityFromVersion($constraint);
        $stabilityText = $stability;

        if ($stability === 'stable') {
            $constraintType = getVersionConstraintType($constraint);
            $stabilityText .= " - " . $constraintType;
        }

        $versionMitigated = isPackageIgnored($package, $tikiVersion, $ignoreMap);

        $isPartOfUnmitigatedDowngrade = false;
        if (isset($data['regression_info']) && $hasUnmitigatedDowngrade) {
            $regressionInfo = $data['regression_info'];
            if ($tikiVersion === $regressionInfo['to_tiki']) {
                $isPartOfUnmitigatedDowngrade = true;
            }
        }

        echo "   - Tiki {$tikiVersion}: {$constraint} ({$stabilityText})";

        if ($versionMitigated) {
            echo " [MITIGATED]";
        } elseif ($stability !== 'stable' || $isPartOfUnmitigatedDowngrade) {
            echo " [WARNING]";
        }

        echo "\n";
    }
}

/**
 * Generate summary of all issues
 */
function generateIssuesSummary($issuesByPackage, $stableRegressions)
{
    echo "\nSummary of Issues:\n";
    echo "-----------------\n";
    echo "Packages with unstable versions: " .
        count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_unstable'])) . "\n";
    echo "Packages with stability regressions: " .
        count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_regressions'])) . "\n";
    echo "Packages with stable version downgrades: " .
        count($stableRegressions) . "\n";
}

/**
 * Split existing baseline issues into stability issues and version downgrades.
 */
function splitExistingIssuesByType($existingIssues)
{
    $stabilityIssues = [];
    $versionDowngrades = [];

    foreach ($existingIssues as $issue) {
        if (isset($issue['has_downgrade']) && $issue['has_downgrade']) {
            $versionDowngrades[] = $issue;
        }

        if (
            (isset($issue['has_unstable']) && $issue['has_unstable']) ||
            (isset($issue['has_regression']) && $issue['has_regression'])
        ) {
            $stabilityIssues[] = $issue;
        }
    }

    return [$stabilityIssues, $versionDowngrades];
}

/**
 * Summary
 */
function renderExecutiveSummary($baselineAnalysis, $hasWarnings, $packageHistory, $ignoreMap)
{
    if ($baselineAnalysis['shouldFail']) {
        echo "\n## 🚨 Current Branch Introduces New Stability Issues\n\n";

        if (! empty($baselineAnalysis['newIssues'])) {
            echo "### Issues Introduced by Current Branch:\n";
            foreach ($baselineAnalysis['newIssues'] as $issue) {
                switch ($issue['type']) {
                    case 'new_issue':
                        $stability = $issue['stability'] ?? 'unknown';
                        $version = $issue['version'] ?? 'unknown';
                        $reason = isset($issue['reason']) ? " - {$issue['reason']}" : "";
                        echo "- **{$issue['package']}**: Introduced unstable version `{$version}` ({$stability}){$reason}\n";
                        break;
                    case 'worsened_existing':
                        $baselineStability = $issue['baseline_stability'] ?? 'unknown';
                        $baselineVersion = $issue['baseline_version'] ?? 'unknown';
                        $currentStability = $issue['current_stability'] ?? 'unknown';
                        $currentVersion = $issue['current_version'] ?? 'unknown';
                        echo "- **{$issue['package']}**: Made existing issue worse: from `{$baselineVersion}` ({$baselineStability}) to `{$currentVersion}` ({$currentStability})\n";
                        break;
                    case 'new_version_downgrade':
                        $fromVersion = $issue['from_version'] ?? 'unknown';
                        $toVersion = $issue['to_version'] ?? 'unknown';
                        echo "- **{$issue['package']}**: Introduced version downgrade from `{$fromVersion}` to `{$toVersion}`\n";
                        break;
                    case 'version_downgrade':
                        $fromVersion = $issue['from_version'] ?? 'unknown';
                        $toVersion = $issue['to_version'] ?? 'unknown';
                        echo "- **{$issue['package']}**: Existing version downgrade from `{$fromVersion}` to `{$toVersion}`\n";
                        break;
                    default:
                        echo "- **{$issue['package']}**: Unknown issue type: {$issue['type']}\n";
                        break;
                }
            }
        }

        echo "\n**Pipeline Failed**: Current branch introduces stability issues that must be fixed.\n";
        return;
    }

    if (! empty($baselineAnalysis['existingIssues'])) {
        if ($hasWarnings) {
            echo "\n## ⚠️ Stability Issues Detected in Baseline\n\n";
            echo "**Reason**: Baseline contains packages with [WARNING] tags (not mitigated).\n\n";
        } else {
            echo "\n## ⚠️ Stability Issues Detected (All Mitigated)\n\n";
            echo "**Status**: All baseline issues are documented and mitigated.\n\n";
        }

        [$stabilityIssues, $versionDowngrades] = splitExistingIssuesByType($baselineAnalysis['existingIssues']);

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

                $package = $issue['package'];
                $history = $packageHistory[$package];
                $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
                $hasWarningsForPackage = $packageStatus['hasWarnings'];
                $allVersionsMitigated = $packageStatus['allVersionsMitigated'];

                if ($hasWarningsForPackage) {
                    echo " [WARNING]";
                } elseif ($allVersionsMitigated) {
                    echo " [MITIGATED]";
                }

                echo "\n";
            }
        }

        if (! empty($versionDowngrades)) {
            echo "\n### Version Downgrades (Existing):\n";
            foreach ($versionDowngrades as $issue) {
                echo "- **{$issue['package']}**: Version downgrade detected";

                $package = $issue['package'];
                $hasWarningsForPackage = false;
                $hasUnmitigatedDowngrade = false;

                if (isset($issue['from_tiki']) && isset($issue['to_tiki'])) {
                    $fromVersion = $issue['from_tiki'];
                    $toVersion = $issue['to_tiki'];

                    $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap);
                }

                if ($hasWarningsForPackage || $hasUnmitigatedDowngrade) {
                    echo " [WARNING]";
                } elseif (isset($issue['is_ignored']) && $issue['is_ignored']) {
                    echo " [MITIGATED]";
                }

                echo "\n";
            }
        }

        if ($hasWarnings) {
            echo "\n**Pipeline Passes**: Current branch did not introduce/worsen issues on touched packages.\n";
            echo "**Action Suggested**:\n";
            echo "1. Fix unstable package versions in baseline where possible, OR\n";
            echo "2. Add them to the `stability-ignore` list in `vendor_bundled/composer.json` with:\n";
            echo "   - `reason`: Why this unstable version is acceptable\n";
            echo "   - `mitigation`: How you're handling the risk\n";
            echo "   - `reviewed`: Date of review\n";
        } else {
            echo "\n**Pipeline Warning**: All baseline issues are properly mitigated.\n";
            echo "**Note**: These mitigated issues are documented and tracked for future cleanup.\n";
        }

        return;
    }

    echo "\n## ✅ No Composer stability issues detected\n";
}

/**
 * Render detailed package-level report.
 */
function renderDetailedReport($packageHistory, $versionsToCheck, $ignoreMap = [], $showTitle = true)
{
    $reportData = processPackageData($packageHistory, $versionsToCheck);
    $hasIssues = $reportData['hasIssues'];
    $issuesByPackage = $reportData['issuesByPackage'];
    $stableRegressions = $reportData['stableRegressions'];

    if ($showTitle) {
        echo "\nPackage Stability Analysis:\n==================\n";
    }

    if (! $hasIssues && empty($stableRegressions)) {
        echo "\n✅ No stability issues detected across all versions!\n";
    } else {
        generateStabilityIssuesReport($issuesByPackage, $versionsToCheck, $ignoreMap);
        generateStableRegressionsReport($stableRegressions, $versionsToCheck, $ignoreMap);
        generateIssuesSummary($issuesByPackage, $stableRegressions);
    }

    global $globalIssuesByPackage, $globalStableRegressions;
    $globalIssuesByPackage = $issuesByPackage;
    $globalStableRegressions = $stableRegressions;
}

/**
 * Render non-CI executive summary.
 */
function renderNonCiExecutiveSummary($packageHistory, $versionsToCheck)
{
    $reportData = processPackageData($packageHistory, $versionsToCheck);
    $issuesByPackage = $reportData['issuesByPackage'];
    $stableRegressions = $reportData['stableRegressions'];

    $unstableCount = count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_unstable']));
    $regressionCount = count(array_filter($issuesByPackage, fn($p) => $p['metadata']['has_regressions']));
    $downgradeCount = count($stableRegressions);
    $totalIssuePackages = count(array_unique(array_merge(array_keys($issuesByPackage), array_keys($stableRegressions))));

    echo "\n## Report Summary\n\n";

    if ($totalIssuePackages === 0) {
        echo "- No stability issues detected across checked versions.\n";
        return;
    }

    echo "- Packages with issues: {$totalIssuePackages}\n";
    echo "- Unstable versions: {$unstableCount}\n";
    echo "- Stability regressions: {$regressionCount}\n";
    echo "- Stable version downgrades: {$downgradeCount}\n";
}

/**
 * Analyze current branch against baseline to determine if it introduces new issues
 * This helps CI pipelines fail only when the current branch is responsible
 */
function analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck, $ignoreMap = [])
{
    global $isCiMode, $globalComposerData;
    $currentBranch = getCurrentBranch();

    if (! $currentBranch || ! $isCiMode) {
        return ['shouldFail' => false, 'newIssues' => [], 'existingIssues' => []];
    }

    $stableVersions = array_filter($versionsToCheck, function ($version) use ($currentBranch) {
        return $version !== $currentBranch;
    });

    $currentBranchIssues = [];
    $existingIssues = [];

    foreach ($packageHistory as $package => $history) {
        if (! isset($history['_metadata'])) {
            continue;
        }

        $metadata = $history['_metadata'];

        $baselineAnalysis = analyzePackageBaseline($package, $history, $metadata, $stableVersions, $ignoreMap);
        if ($baselineAnalysis) {
            $existingIssues[] = $baselineAnalysis;
        }

        $currentBranchAnalysis = analyzePackageCurrentBranch($package, $history, $metadata, $currentBranch, $baselineAnalysis, $ignoreMap);
        if ($currentBranchAnalysis) {
            $currentBranchIssues[] = $currentBranchAnalysis;
        }

        $downgradeAnalysis = analyzePackageVersionDowngrades($package, $history, $metadata, $versionsToCheck, $currentBranch, $ignoreMap);
        if ($downgradeAnalysis) {
            $existingIssues[] = $downgradeAnalysis;
        }
    }

    $existingIssues = aggregateExistingIssues($existingIssues);

    $touchedPackages = getTouchedPackages($globalComposerData, $currentBranch);
    $filteredIssues = [];

    foreach ($currentBranchIssues as $issue) {
        $pkg = $issue['package'];
        if (isset($touchedPackages[$pkg])) {
            $filteredIssues[] = $issue;
        }
    }

    $shouldFail = ! empty($filteredIssues);

    if ($isCiMode) {
        echo "Issues Introduced by Current Branch: " . count($currentBranchIssues) . "\n";
        echo "Touched packages: " . count($touchedPackages) . "\n";
        echo "Issues on touched packages: " . count($filteredIssues) . "\n";
        echo "Existing Issues in Baseline: " . count($existingIssues) . "\n";
    }

    return [
        'shouldFail' => $shouldFail,
        'newIssues' => $filteredIssues,
        'existingIssues' => $existingIssues
    ];
}

/**
 * Analyze baseline issues for a specific package
 */
function analyzePackageBaseline($package, $history, $metadata, $stableVersions, $ignoreMap)
{
    $baselineIssue = checkBaselineStabilityIssues($package, $history, $stableVersions, $ignoreMap);
    if ($baselineIssue) {
        return $baselineIssue;
    }

    $baselineRegression = checkBaselineStabilityRegressions($package, $metadata, $stableVersions, $ignoreMap);
    if ($baselineRegression) {
        return $baselineRegression;
    }

    return null;
}

/**
 * Check for stability issues in baseline versions
 */
function checkBaselineStabilityIssues($package, $history, $stableVersions, $ignoreMap)
{
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

    if (! $hasBaselineIssues) {
        return null;
    }

    $ignoreInfo = checkPackageIgnoreStatus($package, $stableVersions, $ignoreMap);

    return [
        'package' => $package,
        'type' => 'existing_issue',
        'stability' => $baselineStability ?? 'unknown',
        'version' => $baselineVersion ?? 'unknown',
        'tiki_version' => 'baseline',
        'is_ignored' => $ignoreInfo['is_ignored'],
        'ignore_reason' => $ignoreInfo['ignore_reason']
    ];
}

/**
 * Check for stability regressions in baseline versions
 */
function checkBaselineStabilityRegressions($package, $metadata, $stableVersions, $ignoreMap)
{
    if (! $metadata['has_regressions']) {
        return null;
    }

    $stableVersionNames = array_keys($stableVersions);
    $baselineVersions = array_filter($metadata['versions'], function ($version) use ($stableVersionNames) {
        return in_array($version['tiki_version'], $stableVersionNames);
    });

    $baselineVersions = sortVersionsByTikiOrder($baselineVersions, $stableVersionNames);

    $hasBaselineRegression = false;
    for ($i = 1; $i < count($baselineVersions); $i++) {
        $prev = $baselineVersions[$i - 1];
        $current = $baselineVersions[$i];

        $prevStability = extractStabilityFromVersion($prev['constraint']);
        $currentStability = extractStabilityFromVersion($current['constraint']);

        if (getStabilityNumeric($currentStability) < getStabilityNumeric($prevStability)) {
            $hasBaselineRegression = true;
            break; // Only need to know if there's a regression, not count each one
        }
    }

    if (! $hasBaselineRegression) {
        return null;
    }

    $ignoreInfo = checkPackageIgnoreStatus($package, $stableVersions, $ignoreMap);

    return [
        'package' => $package,
        'type' => 'existing_stability_regression',
        'from_stability' => 'baseline',
        'from_version' => 'baseline',
        'from_tiki' => 'baseline',
        'to_stability' => 'baseline',
        'to_version' => 'baseline',
        'to_tiki' => 'baseline',
        'is_ignored' => $ignoreInfo['is_ignored'],
        'ignore_reason' => $ignoreInfo['ignore_reason']
    ];
}

/**
 * Check if a package is ignored in any of the specified versions
 */
function checkPackageIgnoreStatus($package, $versions, $ignoreMap)
{
    $isIgnored = false;
    $ignoreReason = null;

    foreach ($versions as $version) {
        if (isPackageIgnored($package, $version, $ignoreMap)) {
            $isIgnored = true;
            $ignoreReason = getIgnoreReason($package, $version, $ignoreMap);
            break;
        }
    }

    return [
        'is_ignored' => $isIgnored,
        'ignore_reason' => $ignoreReason
    ];
}

/**
 * Analyze current branch issues for a specific package
 */
function analyzePackageCurrentBranch($package, $history, $metadata, $currentBranch, $baselineAnalysis, $ignoreMap)
{
    if (! isset($history[$currentBranch])) {
        return null;
    }

    $currentConstraint = $history[$currentBranch];
    $currentStability = extractStabilityFromVersion($currentConstraint);

    if ($currentStability !== 'stable') {
        if ($baselineAnalysis && $baselineAnalysis['stability'] !== 'stable') {
            // Issue exists in baseline AND baseline is unstable - check if current branch makes it worse
            $baselineStability = $baselineAnalysis['stability'] ?? 'unknown';
            $baselineVersion = $baselineAnalysis['version'] ?? 'unknown';
            $baselineWasMitigated = $baselineAnalysis['is_ignored'] ?? false;
            $currentIsMitigated = isPackageIgnored($package, $currentBranch, $ignoreMap);

            // Check if current branch loses the mitigation
            if ($baselineWasMitigated && ! $currentIsMitigated) {
                return [
                    'package' => $package,
                    'type' => 'new_issue',
                    'stability' => $currentStability,
                    'version' => $currentConstraint,
                    'reason' => 'Mitigation lost - package was mitigated in baseline but not in current branch'
                ];
            }

            if (getStabilityNumeric($currentStability) < getStabilityNumeric($baselineStability)) {
                return [
                    'package' => $package,
                    'type' => 'worsened_existing',
                    'baseline_stability' => $baselineStability,
                    'baseline_version' => $baselineVersion,
                    'current_stability' => $currentStability,
                    'current_version' => $currentConstraint
                ];
            }
            // Note: existing issues are already counted above when scanning baseline (if still mitigated or same)
        } else {
            // New issue introduced by current branch (either no baseline analysis or baseline was stable)
            // Check if the new unstable package is mitigated
            $currentIsMitigated = isPackageIgnored($package, $currentBranch, $ignoreMap);

            if (! $currentIsMitigated) {
                return [
                    'package' => $package,
                    'type' => 'new_issue',
                    'stability' => $currentStability,
                    'version' => $currentConstraint
                ];
            }
        }
    }

    if ($currentStability === 'stable' && $metadata['has_stable_regressions']) {
        $versions = sortVersionsByTikiOrder($metadata['versions'], array_keys($history));

        $highestStableVersion = null;
        $highestStableTikiVersion = null;

        foreach ($versions as $version) {
            if ($version['tiki_version'] === $currentBranch) {
                break;
            }

            $stability = extractStabilityFromVersion($version['constraint']);
            if ($stability === 'stable') {
                $baseVersion = getNormalizedBaseVersion($version['constraint']);
                if ($baseVersion && (! $highestStableVersion || version_compare($baseVersion, $highestStableVersion) > 0)) {
                    $highestStableVersion = $baseVersion;
                    $highestStableTikiVersion = $version['tiki_version'];
                }
            }
        }

        if ($highestStableVersion) {
            $currentBaseVersion = getNormalizedBaseVersion($currentConstraint);
            if ($currentBaseVersion && version_compare($currentBaseVersion, $highestStableVersion) < 0) {
                if (! isConstraintStyleChange($currentBaseVersion, $highestStableVersion)) {
                    if (isVersionDowngradeMitigated($package, $highestStableTikiVersion, $currentBranch, $ignoreMap)) {
                        return null;
                    }

                    return [
                        'package' => $package,
                        'type' => 'new_version_downgrade',
                        'from_version' => $highestStableVersion,
                        'from_tiki' => $highestStableTikiVersion,
                        'to_version' => $currentBaseVersion,
                        'current_version' => $currentConstraint
                    ];
                }
            }
        }
    }

    return null;
}

/**
 * Analyze version downgrades for a specific package
 */
function analyzePackageVersionDowngrades($package, $history, $metadata, $versionsToCheck, $currentBranch, $ignoreMap)
{
    if (! $metadata['has_stable_regressions']) {
        return null;
    }

    $versions = sortVersionsByTikiOrder($metadata['versions'], $versionsToCheck);

    for ($i = 1; $i < count($versions); $i++) {
        $prev = $versions[$i - 1];
        $current = $versions[$i];

        $prevBase = getNormalizedBaseVersion($prev['constraint']);
        $currentBase = getNormalizedBaseVersion($current['constraint']);

        if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
            if (isConstraintStyleChange($prevBase, $currentBase)) {
                continue;
            }

            if ($current['tiki_version'] === $currentBranch) {
                continue;
            } else {
                $isIgnored = isPackageIgnored($package, $current['tiki_version'], $ignoreMap);
                $ignoreReason = $isIgnored ? getIgnoreReason($package, $current['tiki_version'], $ignoreMap) : null;

                return [
                    'package' => $package,
                    'type' => 'existing_version_downgrade',
                    'from_version' => $prev['constraint'],
                    'from_tiki' => $prev['tiki_version'],
                    'to_version' => $current['constraint'],
                    'to_tiki' => $current['tiki_version'],
                    'is_ignored' => $isIgnored,
                    'ignore_reason' => $ignoreReason
                ];
            }
        }
    }

    return null;
}

/**
 * Aggregate existing issues by package name to combine multiple issue types
 */
function aggregateExistingIssues($existingIssues)
{
    if (empty($existingIssues)) {
        return [];
    }

    $aggregatedIssues = [];

    foreach ($existingIssues as $issue) {
        $package = $issue['package'];

        if (! isset($aggregatedIssues[$package])) {
            $aggregatedIssues[$package] = [
                'package' => $package,
                'types' => [],
                'has_unstable' => false,
                'has_regression' => false,
                'has_downgrade' => false,
                'is_ignored' => false,
                'ignore_reason' => null
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

        // Track ignore status (if any issue type is ignored, mark the package as ignored)
        if (isset($issue['is_ignored']) && $issue['is_ignored']) {
            $aggregatedIssues[$package]['is_ignored'] = true;
            $aggregatedIssues[$package]['ignore_reason'] = $issue['ignore_reason'];
        }

        // For version downgrades, we need to track the from and to versions to determine if the downgrade is mitigated
        if ($issue['type'] === 'existing_version_downgrade') {
            $aggregatedIssues[$package]['from_version'] = $issue['from_version'];
            $aggregatedIssues[$package]['from_tiki'] = $issue['from_tiki'];
            $aggregatedIssues[$package]['to_version'] = $issue['to_version'];
            $aggregatedIssues[$package]['to_tiki'] = $issue['to_tiki'];
        }
    }

    return array_values($aggregatedIssues);
}

/**
 * Check if there are any unmitigated warnings in existing issues
 * This includes both stability issues (unstable versions not mitigated) and
 * unmitigated version downgrades.
 *
 * @param array $existingIssues Array of existing issues from baseline analysis
 * @param array $packageHistory Complete package history across all versions
 * @param array $ignoreMap Map of ignored packages per version
 * @return bool True if there are any unmitigated warnings, false otherwise
 */
function hasUnmitigatedWarnings($existingIssues, $packageHistory, $ignoreMap)
{
    if (empty($existingIssues)) {
        return false;
    }

    foreach ($existingIssues as $issue) {
        $package = $issue['package'];
        $history = $packageHistory[$package];

        $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
        if ($packageStatus['hasWarnings']) {
            return true;
        }

        if (isset($issue['has_downgrade']) && $issue['has_downgrade']) {
            if (isset($issue['from_tiki']) && isset($issue['to_tiki'])) {
                $fromVersion = $issue['from_tiki'];
                $toVersion = $issue['to_tiki'];
                $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap);
                if ($hasUnmitigatedDowngrade) {
                    return true;
                }
            }
        }
    }

    return false;
}

// Main execution
$currentVersion = getCurrentTikiVersion();
if (! $currentVersion) {
    logError("Could not determine current Tiki version.");
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

$ignoreMap = buildStabilityIgnoreMap($packageHistory);

if ($isCiMode) {
    $baselineAnalysis = analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck, $ignoreMap);

    $hasWarnings = false;
    if (! empty($baselineAnalysis['existingIssues'])) {
        $hasWarnings = hasUnmitigatedWarnings($baselineAnalysis['existingIssues'], $packageHistory, $ignoreMap);
    }

    renderExecutiveSummary($baselineAnalysis, $hasWarnings, $packageHistory, $ignoreMap);
    echo "\n\n## Detailed Package Report\n";
    renderDetailedReport($packageHistory, $versionsToCheck, $ignoreMap, true);

    $junitIssues = buildJUnitReportData($baselineAnalysis['existingIssues']);
    $junitReport = generateJUnitReport($junitIssues, []);
    file_put_contents('composer-stability-report.xml', $junitReport);

    $exitCode = 0;
    if ($baselineAnalysis['shouldFail']) {
        $exitCode = 1;
    } else {
        $exitCode = 0;
    }

    exit($exitCode);
} else {
    renderNonCiExecutiveSummary($packageHistory, $versionsToCheck);
    echo "\n## Detailed Package Report\n";
    renderDetailedReport($packageHistory, $versionsToCheck, $ignoreMap, true);
    exit(empty($globalIssuesByPackage) && empty($globalStableRegressions) ? 0 : 1);
}
