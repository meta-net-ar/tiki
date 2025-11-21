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
            // Provide more specific error information
            $errorMsg = "Invalid JSON in composer.json: " . json_last_error_msg();
            $errorMsg .= "\nFile: {$source['path']}";
            $errorMsg .= "\nLine: " . (json_last_error() === JSON_ERROR_SYNTAX ? "Syntax error" : "Unknown error");
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
            return logError("Invalid JSON in remote composer.json: " . json_last_error_msg());
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

    // The _composer_json data is stored at the root level of packageHistory
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
            $levels = array_keys(STABILITY_LEVELS);
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
 * Sort versions by Tiki version order
 */
function sortVersionsByTikiOrder($versions, $versionsToCheck)
{
    // Ensure we have the correct structure and handle potential missing keys
    if (empty($versions) || empty($versionsToCheck)) {
        return $versions;
    }

    // Check if the versions array has the expected structure
    $firstVersion = reset($versions);
    if (! is_array($firstVersion) || ! isset($firstVersion['tiki_version'])) {
        // If the structure is different, return as-is
        return $versions;
    }

    usort($versions, function ($a, $b) use ($versionsToCheck) {
        $order = array_flip($versionsToCheck);

        $aVersion = $a['tiki_version'] ?? '';
        $bVersion = $b['tiki_version'] ?? '';
        // If either version is not in the order array, put it at the end
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

        // Check for unstable versions
        if ($stability !== 'stable') {
            $hasUnstableVersions = true;

            // If an unstable version is not mitigated, it's a warning
            if (! $versionMitigated) {
                $hasWarnings = true;
            }
        }

        // For determining if all (unstable) versions are mitigated,
        // only count unstable versions
        if ($stability !== 'stable' && ! $versionMitigated) {
            $allVersionsMitigated = false;
        }
    }

    // If there are no unstable versions, allVersionsMitigated should be false
    // (nothing to mitigate)
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
    // Only check if the downgrade target (toVersion) is mitigated
    // The fromVersion is the good/higher version and doesn't need mitigation
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

    // Fetch composer data for all versions
    $composerData = fetchComposerDataForVersions($versionsToCheck, $currentBranch);

    // Build initial package structure
    $packageHistory = buildInitialPackageStructure($composerData);

    // Analyze stability and regressions
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

        // For the current branch, use local composer.json if available
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
                //file:///c:/laragon/www/tiki/mockcomposer/$version/composer.json
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
 * Build initial package structure from composer data
 */
function buildInitialPackageStructure($composerData)
{
    $packageHistory = [];

        // Store the composer.json data for stability-ignore processing
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
        // Skip packages that don't have metadata
        if (! isset($history['_metadata']) || ! isset($history['_metadata']['versions'])) {
            continue;
        }

        $versions = $history['_metadata']['versions'];

        // Sort versions chronologically based on the order they were provided
        $versions = sortVersionsByTikiOrder($versions, $versionsToCheck);

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
                    if (isConstraintStyleChange($currentBase, $highestBase)) {
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
}

/**
 * Print package version history with stability information
 */
function printPackageHistory(string $package, array $history, string $icon = '⚠️', $ignoreMap = [], $currentVersion = null): void
{
    // Check if package is ignored
    $isIgnored = false;
    if ($currentVersion === 'baseline') {
        // Check if package is ignored in any baseline version
        foreach ($ignoreMap as $version => $packages) {
            if (isset($packages[$package])) {
                $isIgnored = true;
                break;
            }
        }
    } elseif ($currentVersion && isset($ignoreMap[$currentVersion][$package])) {
        // Check if package is ignored in specific version
        $isIgnored = true;
    }

    // Determine overall package status based on version statuses
    $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
    $hasWarnings = $packageStatus['hasWarnings'];
    $allVersionsMitigated = $packageStatus['allVersionsMitigated'];

    echo "\n{$icon} Package: {$package}";

    // Determine final package status
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

        // Check if this specific version has the package mitigated
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

    // Process package data to identify issues
    $reportData = processPackageData($packageHistory, $versionsToCheck);
    $hasIssues = $reportData['hasIssues'];
    $issuesByPackage = $reportData['issuesByPackage'];
    $stableRegressions = $reportData['stableRegressions'];

    if (! $isCiMode) {
        if (! $hasIssues && empty($stableRegressions)) {
            echo "\n✅ No stability issues detected across all versions!\n";
            return;
        }

        // Generate detailed reports
        generateStabilityIssuesReport($issuesByPackage, $versionsToCheck, $ignoreMap);
        generateStableRegressionsReport($stableRegressions, $versionsToCheck, $ignoreMap);
        generateIssuesSummary($issuesByPackage, $stableRegressions);
    }

    // Store results globally for exit code calculation
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

        // Process stable regressions
        $regressionInfo = processStableRegressions($package, $metadata, $history, $versionsToCheck);
        if ($regressionInfo) {
            $stableRegressions[$package] = [
                'history' => $history,
                'metadata' => $metadata,
                'regression_info' => $regressionInfo
            ];
            continue;
        }

        // Process stability issues
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
            // Check if this package is ignored in any baseline version
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
                // Check if this package is ignored in any baseline version
        $isIgnored = isPackageIgnoredInBaseline($package, $versionsToCheck, $ignoreMap);

                // For stable regressions, we need to determine the overall package status
                // based on whether any versions have warnings (unstable and not mitigated)
                // or if there are version downgrades that are not mitigated
        $packageStatus = determinePackageStatus($package, $data['history'], $ignoreMap);
        $hasWarnings = $packageStatus['hasWarnings'];
        $allVersionsMitigated = $packageStatus['allVersionsMitigated'];
        $hasUnmitigatedDowngrade = false;

        // Check if the version downgrade itself is mitigated
        if (isset($data['regression_info'])) {
            $regressionInfo = $data['regression_info'];
            $fromVersion = $regressionInfo['from_tiki'];
            $toVersion = $regressionInfo['to_tiki'];

            // If either the from or to version is not mitigated, the downgrade is not mitigated
            $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap);
        }

        // Determine the icon and status for the package
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

        // Check if this specific version has the package mitigated
        $versionMitigated = isPackageIgnored($package, $tikiVersion, $ignoreMap);

        // Check if this version is the TARGET of an unmitigated downgrade (not the source)
        $isPartOfUnmitigatedDowngrade = false;
        if (isset($data['regression_info']) && $hasUnmitigatedDowngrade) {
            $regressionInfo = $data['regression_info'];
            // Only mark the "to" version (downgrade target) as WARNING, not the "from" version (which is the better version)
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
 * Analyze current branch against baseline to determine if it introduces new issues
 * This helps CI pipelines fail only when the current branch is responsible
 */
function analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck, $ignoreMap = [])
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
        if (! isset($history['_metadata'])) {
            continue;
        }

        $metadata = $history['_metadata'];

        // Analyze baseline issues for this package
        $baselineAnalysis = analyzePackageBaseline($package, $history, $metadata, $stableVersions, $ignoreMap);
        if ($baselineAnalysis) {
            $existingIssues[] = $baselineAnalysis;
        }

        // Analyze current branch issues for this package
        $currentBranchAnalysis = analyzePackageCurrentBranch($package, $history, $metadata, $currentBranch, $baselineAnalysis, $ignoreMap);
        if ($currentBranchAnalysis) {
            $currentBranchIssues[] = $currentBranchAnalysis;
        }

        // Analyze version downgrades for this package
        $downgradeAnalysis = analyzePackageVersionDowngrades($package, $history, $metadata, $versionsToCheck, $currentBranch, $ignoreMap);
        if ($downgradeAnalysis) {
            $existingIssues[] = $downgradeAnalysis;
        }
    }

    // Aggregate existing issues by package name to combine multiple issue types
    $existingIssues = aggregateExistingIssues($existingIssues);

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

/**
 * Analyze baseline issues for a specific package
 */
function analyzePackageBaseline($package, $history, $metadata, $stableVersions, $ignoreMap)
{
    // Check if this package has issues in stable branches (baseline)
    $baselineIssue = checkBaselineStabilityIssues($package, $history, $stableVersions, $ignoreMap);
    if ($baselineIssue) {
        return $baselineIssue;
    }

    // Check for stability regressions in baseline (not involving current branch)
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

    // Check if this package is ignored in any baseline version
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

    // Only look at versions that are in stableVersions (exclude current branch)
    $stableVersionNames = array_keys($stableVersions);
    $baselineVersions = array_filter($metadata['versions'], function ($version) use ($stableVersionNames) {
        return in_array($version['tiki_version'], $stableVersionNames);
    });

    // Sort baseline versions chronologically
    $baselineVersions = sortVersionsByTikiOrder($baselineVersions, $stableVersionNames);

    // Find stability regressions ONLY in baseline (stable versions)
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

    // Check if this package is ignored in any baseline version
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

    // Check for stability regressions (unstable versions)
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
            // If mitigated, don't flag it (it's a documented decision)
        }
    }

    // Check for version downgrades (stable versions with lower numbers)
    if ($currentStability === 'stable' && $metadata['has_stable_regressions']) {
        $versions = sortVersionsByTikiOrder($metadata['versions'], array_keys($history));

        // Find the highest stable version before current branch
        $highestStableVersion = null;
        $highestStableTikiVersion = null;

        foreach ($versions as $version) {
            if ($version['tiki_version'] === $currentBranch) {
                break; // Stop at current branch
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
                // Check if this is just a constraint style change
                if (! isConstraintStyleChange($currentBaseVersion, $highestStableVersion)) {
                    // Check if this version downgrade is mitigated by ignore rules
                    if (isVersionDowngradeMitigated($package, $highestStableTikiVersion, $currentBranch, $ignoreMap)) {
                        return null; // Downgrade is mitigated
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

    // Find all downgrades (both in baseline and current branch)
    for ($i = 1; $i < count($versions); $i++) {
        $prev = $versions[$i - 1];
        $current = $versions[$i];

        $prevBase = getNormalizedBaseVersion($prev['constraint']);
        $currentBase = getNormalizedBaseVersion($current['constraint']);

        if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
            // Check if this is just a constraint style change
            if (isConstraintStyleChange($prevBase, $currentBase)) {
                continue; // Just a constraint style change
            }

            if ($current['tiki_version'] === $currentBranch) {
                // Current branch introduces the downgrade - this should be handled by analyzePackageCurrentBranch
                continue;
            } else {
                // Downgrade exists in baseline
                // Check if this package is ignored in the version where downgrade occurs
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

    // Convert back to array format for counting
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

        // Check for stability warnings (unstable versions not mitigated)
        $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
        if ($packageStatus['hasWarnings']) {
            return true;
        }

        // Check for unmitigated version downgrades
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

// Build stability ignore map for all versions
$ignoreMap = buildStabilityIgnoreMap($packageHistory);

// Determine exit code based on CI mode and baseline analysis
if ($isCiMode) {
    $baselineAnalysis = analyzeCurrentBranchAgainstBaseline($packageHistory, $versionsToCheck, $ignoreMap);

    // Calculate warning status once for consistency between display and exit code
    $hasWarnings = false;
    if (! empty($baselineAnalysis['existingIssues'])) {
        // Check if there are unmitigated warnings (stability issues AND version downgrades)
        $hasWarnings = hasUnmitigatedWarnings($baselineAnalysis['existingIssues'], $packageHistory, $ignoreMap);
    }

    // Handle CI mode output here to avoid duplication
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
        echo "**Tip**: To see detailed issue information, run `php doc/devtools/check_composer_stability.php` (without --ci) from your project root.\n";
    } elseif (! empty($baselineAnalysis['existingIssues'])) {
        // Display appropriate heading based on warning status (using pre-calculated $hasWarnings)
        if ($hasWarnings) {
            echo "\n## 🚨 Pipeline Failed: Unmitigated Stability Issues Detected\n\n";
            echo "**Reason**: Baseline contains packages with [WARNING] tags (not mitigated).\n\n";
        } else {
            echo "\n## ⚠️ Stability Issues Detected (All Mitigated)\n\n";
            echo "**Status**: All baseline issues are documented and mitigated.\n\n";
        }

        // Separate stability issues from version downgrades
        $stabilityIssues = [];
        $versionDowngrades = [];

        foreach ($baselineAnalysis['existingIssues'] as $issue) {
            if (isset($issue['has_downgrade']) && $issue['has_downgrade']) {
                $versionDowngrades[] = $issue;
            }

            // Check if package has stability issues (unstable or regression)
            if (
                (isset($issue['has_unstable']) && $issue['has_unstable']) ||
                (isset($issue['has_regression']) && $issue['has_regression'])
            ) {
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

                // Determine the actual package status using the same logic as non-CI mode
                $package = $issue['package'];
                $history = $packageHistory[$package];
                $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
                $hasWarningsForPackage = $packageStatus['hasWarnings'];
                $allVersionsMitigated = $packageStatus['allVersionsMitigated'];

                // Determine final package status
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

                // Determine the actual package status using the aggregated issue data
                $package = $issue['package'];
                $hasWarningsForPackage = false;
                $hasUnmitigatedDowngrade = false;

                // Check if the version downgrade itself is mitigated
                if (isset($issue['from_tiki']) && isset($issue['to_tiki'])) {
                    $fromVersion = $issue['from_tiki'];
                    $toVersion = $issue['to_tiki'];

                    // If either the from or to version is not mitigated, the downgrade is not mitigated
                    $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap);
                }

                // Determine final package status
                if ($hasWarningsForPackage || $hasUnmitigatedDowngrade) {
                    echo " [WARNING]";
                } elseif (isset($issue['is_ignored']) && $issue['is_ignored']) {
                    echo " [MITIGATED]";
                }

                echo "\n";
            }
        }

        // Display appropriate footer message based on warning status
        if ($hasWarnings) {
            echo "\n**Action Required**:\n";
            echo "1. Fix the unstable package versions, OR\n";
            echo "2. Add them to the `stability-ignore` list in `vendor_bundled/composer.json` with:\n";
            echo "   - `reason`: Why this unstable version is acceptable\n";
            echo "   - `mitigation`: How you're handling the risk\n";
            echo "   - `reviewed`: Date of review\n\n";
            echo "**Tip**: Run `php doc/devtools/check_composer_stability.php` (without --ci) for detailed information.\n";
        } else {
            echo "\n**Pipeline Warning**: All baseline issues are properly mitigated.\n";
            echo "**Note**: These mitigated issues are documented and tracked for future cleanup.\n";
            echo "**Tip**: Run `php doc/devtools/check_composer_stability.php` (without --ci) for detailed information.\n";
        }
    } else {
            echo "\n## ✅ No Composer stability issues detected\n";
            echo "**Tip**: To see detailed issue information, run `php doc/devtools/check_composer_stability.php` (without --ci) from your project root.\n";
    }

    // Generate JUnit XML report for CI artifacts
    $junitIssues = buildJUnitReportData($baselineAnalysis['existingIssues']);
    $junitReport = generateJUnitReport($junitIssues, []);
    file_put_contents('composer-stability-report.xml', $junitReport);

    // Exit codes:
    // 0 = no issues OR all existing issues are mitigated - SUCCESS
    // 1 = current branch introduces issues OR existing issues have warnings (not mitigated) - FAIL
    $exitCode = 0;
    if ($baselineAnalysis['shouldFail']) {
        $exitCode = 1;
    } elseif ($hasWarnings) {
        // FAIL if there are any packages with warnings (not mitigated)
        $exitCode = 1;
    } else {
        // No issues or all existing issues are mitigated - exit with 0 (success)
        $exitCode = 0;
    }

    exit($exitCode);
} else {
    // Local mode: generate report and fail if any issues exist
    generateReport($packageHistory, $versionsToCheck, $ignoreMap);
    exit(empty($globalIssuesByPackage) && empty($globalStableRegressions) ? 0 : 1);
}
