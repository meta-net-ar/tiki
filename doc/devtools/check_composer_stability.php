<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Package Stability Checker for Tiki
 *
 * This script analyzes composer.json files across different Tiki branches to detect:
 * 1. Packages with unstable versions (dev, alpha, beta, RC)
 * 2. Actual version downgrades (e.g., from ^1.27 to ~1.8)
 * 3. Stability regressions (e.g., from stable to beta)
 *
 * Usage:
 *   php check_composer_stability.php [tiki-version1] [tiki-version2] ...
 *
 * Examples:
 *   php check_composer_stability.php              # Compare all available branches
 *   php check_composer_stability.php 24.x 27.x    # Compare only 24.x and 27.x
 *   php check_composer_stability.php 24.x 26.x master  # Compare specific branches
 *   php check_composer_stability.php 28.x current # Compare 28.x with current branch
 *   php check_composer_stability.php current 29.x master # Compare current branch with 29.x and master
 *   php check_composer_stability.php --ci current  # CI mode
 *
 * Available versions: 24.x, 27.x, 30.x, master
 *
 * Options:
 *   --ci       Enable CI mode (responsibility filtering, exit codes, JUnit report)
 *
 * Output:
 * - Lists packages with unstable versions
 * - Shows actual version downgrades with specific version comparisons
 * - Provides a summary of all detected issues
 *
 * Exit codes (CI mode with --ci flag):
 *   0 - No stability issues detected OR all existing issues are [MITIGATED]
 *   1 - Current branch introduces new issues OR accepted state has unmitigated issues [WARNING] (fail)
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
function getCheckedOutBranchName()
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
    // Packages in composer.json extra.stability-ignore are treated as [MITIGATED]
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
    $reason = $ignoreData['reason'] ?? null;
    $mitigation = $ignoreData['mitigation'] ?? null;
    $alternative = $ignoreData['alternative'] ?? ($ignoreData['alternatives'] ?? null);
    $reviewed = $ignoreData['reviewed'] ?? null;

    return [
        'reason' => $reason,
        'mitigation' => $mitigation,
        'alternative' => $alternative,
        'reviewed' => $reviewed
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
    // global $isCiMode: write file-scope flag so logging/reporting functions see --ci
    global $argv, $isCiMode;
    $args = array_slice($argv, 1);

    $isCiMode = false;
    $filteredArgs = [];

    // Strip --ci from args; remaining tokens are branch names or "current"
    for ($i = 0, $count = count($args); $i < $count; $i++) {
        $arg = $args[$i];
        if ($arg === '--ci') {
            $isCiMode = true;
        } else {
            $filteredArgs[] = $arg;
        }
    }

    if (empty($filteredArgs)) {
        // No specific versions specified, use smart detection
        return getSmartVersionOrder();
    }

    $availableVersions = getAvailableTikiVersions();
    $currentBranch = getCheckedOutBranchName();
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
            logWarning("Ignoring invalid branch '$arg'. Available branches are: " . implode(', ', $availableVersions) . " or 'current'");
        }
    }

    if (empty($versionsToCheck)) {
        logError("No valid branches specified. Using smart branch ordering.");
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
    $targetBranch = getTargetBranch();
    $currentBranch = getCheckedOutBranchName();
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
        // Insert the current feature branch right after its merge target
        // so chronological comparisons include it in the right place
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
        // Fallback when Composer cannot normalize the constraint
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
    // Treat ^1.2 vs ~1.2 as a constraint-style change, not a version downgrade
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
        // [MITIGATED] only applies when unstable versions exist and are all ignored
        $allVersionsMitigated = false;
    }

    return ['hasWarnings' => $hasWarnings, 'allVersionsMitigated' => $allVersionsMitigated];
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

        if ($issue['has_regression']) {
            $junitIssues[$package]['metadata']['has_regressions'] = true;
        }
        if ($issue['has_unstable']) {
            $junitIssues[$package]['metadata']['has_unstable'] = true;
        }
    }

    return $junitIssues;
}

/**
 * Build JUnit report section from accepted-state model data.
 *
 * Source of truth is accepted_state.packages[].issue flags already derived from
 * existing accepted-state analysis. stableRegressions is always empty in CI because
 * accepted-state JUnit reporting uses issue metadata flags only; version-downgrade
 * testcases are not emitted on the current CI path.
 *
 * @return array{
 *     applicable: bool,
 *     issuesByPackage: array<string, array{metadata: array{has_regressions: bool, has_unstable: bool}}>,
 *     stableRegressions: array
 * }
 */
function buildJUnitReportModel(array $acceptedState): array
{
    if (empty($acceptedState['applicable'])) {
        return [
            'applicable' => false,
            'issuesByPackage' => [],
            'stableRegressions' => [],
        ];
    }

    $existingIssues = array_values(array_map(
        fn(array $entry) => $entry['issue'],
        $acceptedState['packages']
    ));

    return [
        'applicable' => true,
        'issuesByPackage' => buildJUnitReportData($existingIssues),
        'stableRegressions' => [],
    ];
}

/**
 * Render JUnit XML from the report model JUnit section (formatting only).
 */
function renderJUnitReport(array $junitReport): string
{
    if (empty($junitReport['applicable'])) {
        return generateJUnitReport([], []);
    }

    return generateJUnitReport(
        $junitReport['issuesByPackage'],
        $junitReport['stableRegressions']
    );
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
 * Detect stability regressions across ordered version entries.
 *
 * @param array<int, array<string, mixed>> $versions
 */
function hasStabilityRegressionInVersions($versions)
{
    for ($i = 1; $i < count($versions); $i++) {
        $prevStability = extractStabilityFromVersion($versions[$i - 1]['constraint']);
        $currentStability = extractStabilityFromVersion($versions[$i]['constraint']);

        if (getStabilityNumeric($currentStability) < getStabilityNumeric($prevStability)) {
            return true;
        }
    }

    return false;
}

/**
 * Build complete package history across all versions
 */
function buildPackageHistory($versionsToCheck)
{
    $packageHistory = [];
    $currentBranch = getCheckedOutBranchName();

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
                // 'url' => "C:/laragon/www/Tiki/temps/$version.json",
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
 * Resolve the merge-base SHA for touched-package comparison.
 *
 * Single entry point for "which commit should we compare against?".
 * Git ref resolution is an internal implementation detail.
 */
function resolveMergeBaseSha(): ?string
{
    // Prefer GitLab-provided MR diff base; fall back to git merge-base if unavailable
    $diffBaseSha = getenv('CI_MERGE_REQUEST_DIFF_BASE_SHA');
    if ($diffBaseSha !== false && $diffBaseSha !== '') {
        $diffBaseSha = trim($diffBaseSha);
        if (preg_match('/^[0-9a-f]{40}$/i', $diffBaseSha) && gitCommitObjectExists($diffBaseSha)) {
            return $diffBaseSha;
        }
        if (preg_match('/^[0-9a-f]{40}$/i', $diffBaseSha)) {
            logWarning(
                'CI_MERGE_REQUEST_DIFF_BASE_SHA is not available in this repository; computing merge-base'
            );
        }
    }

    $targetBranch = resolveMergeTargetBranchName();

    $targetRef = resolveTargetRefForMergeBase($targetBranch);
    if ($targetRef === null) {
        return null;
    }

    $mergeBase = getGitMergeBase($targetRef, 'HEAD');
    if ($mergeBase === null) {
        logWarning("Could not determine merge-base between {$targetBranch} and HEAD");
        return null;
    }

    return $mergeBase;
}

/**
 * Resolve the merge target branch name (not the merge-base SHA).
 */
function resolveMergeTargetBranchName(): string
{
    $mrTarget = getenv('CI_MERGE_REQUEST_TARGET_BRANCH_NAME');
    if ($mrTarget !== false && $mrTarget !== '') {
        return $mrTarget;
    }

    return getTargetBranch();
}

/**
 * Resolve the Git ref used to compute merge-base for a target branch name.
 *
 * Uses origin/<branch> in GitLab CI (detached HEAD) and the local branch otherwise.
 * Fails explicitly if the expected ref does not exist.
 */
function resolveTargetRefForMergeBase(string $targetBranch): ?string
{
    if (isGitLabCiEnvironment()) {
        $ref = 'origin/' . $targetBranch;
        if (! gitRefExists($ref)) {
            logWarning(
                "Target ref '{$ref}' not found. Ensure the target branch was fetched (check GIT_DEPTH)."
            );
            return null;
        }
        return $ref;
    }

    if (! gitRefExists($targetBranch)) {
        logWarning("Local branch '{$targetBranch}' not found. Checkout or fetch it before running.");
        return null;
    }

    return $targetBranch;
}

/**
 * Whether the script is running inside a GitLab CI (or generic CI) environment.
 */
function isGitLabCiEnvironment(): bool
{
    $gitlabCi = getenv('GITLAB_CI');
    if ($gitlabCi !== false && $gitlabCi !== '' && $gitlabCi !== '0') {
        return true;
    }

    $ci = getenv('CI');
    return $ci !== false && $ci !== '' && $ci !== '0';
}

/**
 * Check whether a Git ref exists in the current repository.
 */
function gitRefExists(string $ref): bool
{
    $output = [];
    exec('git rev-parse --verify ' . escapeshellarg($ref) . ' 2>nul', $output, $returnCode);

    return $returnCode === 0 && ! empty($output[0]);
}

/**
 * Check whether a commit object exists in the current repository.
 */
function gitCommitObjectExists(string $sha): bool
{
    exec('git cat-file -e ' . escapeshellarg($sha . '^{commit}') . ' 2>nul', $_, $returnCode);

    return $returnCode === 0;
}

/**
 * Find the merge-base between two Git refs.
 */
function getGitMergeBase($refA, $refB)
{
    $mergeBase = trim((string) shell_exec(
        'git merge-base ' . escapeshellarg($refA) . ' ' . escapeshellarg($refB) . ' 2>nul'
    ));

    if ($mergeBase === '' || preg_match('/fatal|error/i', $mergeBase)) {
        return null;
    }

    return $mergeBase;
}

/**
 * Load and parse composer.json at a given Git ref.
 */
function getComposerJsonFromGitRef($gitRef)
{
    $path = PRIMARY_COMPOSERJSON_FILE_PATH;
    $content = shell_exec(
        'git show ' . escapeshellarg($gitRef . ':' . $path) . ' 2>nul'
    );

    if ($content === null || $content === '') {
        return null;
    }

    $json = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        logWarning('Invalid JSON in composer.json at Git ref ' . $gitRef . ': ' . json_last_error_msg());
        return null;
    }

    return $json;
}

/**
 * Diff two composer require sections and return touched packages.
 */
function diffComposerRequireSections($baseRequire, $headRequire)
{
    $touched = [];
    $allPackages = array_unique(array_merge(
        array_keys($baseRequire),
        array_keys($headRequire)
    ));

    foreach ($allPackages as $package) {
        if ($package === 'php' || str_starts_with($package, 'ext-')) {
            continue;
        }

        $headVersion = $headRequire[$package] ?? null;
        $baseVersion = $baseRequire[$package] ?? null;

        if ($headVersion !== $baseVersion) {
            $touched[$package] = [
                'from' => $baseVersion,
                'to' => $headVersion
            ];
        }
    }

    return $touched;
}

/**
 * Detect packages modified by the current feature branch since it diverged from its target.
 *
 * Uses resolveMergeBaseSha() to compare composer.json at the fork point against
 * the current branch HEAD. CI responsibility is restricted to these packages.
 */
function getTouchedPackages($composerData, $currentBranch)
{
    if (! isset($composerData[$currentBranch])) {
        return [];
    }

    $mergeBase = resolveMergeBaseSha();
    if (! $mergeBase) {
        return [];
    }

    $baseComposer = getComposerJsonFromGitRef($mergeBase);
    if (! $baseComposer) {
        logWarning("Could not load composer.json at merge-base {$mergeBase}");
        return [];
    }

    $baseRequire = $baseComposer['require'] ?? [];
    $headRequire = $composerData[$currentBranch]['require'] ?? [];

    return diffComposerRequireSections($baseRequire, $headRequire);
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
 * Build the global stability and regression analysis across all analyzed branches.
 *
 * This function computes:
 * - unstable package usage
 * - stability regressions
 * - stable version downgrades
 *
 * The resulting metadata is later used by the CI responsibility layer
 * to determine whether the current branch should fail the pipeline.
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
                // Stability regression across Tiki branches (e.g. stable -> beta)
                $history['_metadata']['has_regressions'] = true;
                if ($currentStability === 'stable' && $previousStability === 'stable') {
                    $history['_metadata']['has_stable_regressions'] = true;
                }
            }

            if ($currentStability === 'stable' && $currentBase && $highestBaseVersionSeen) {
                // Stable numeric downgrade (e.g. ^2.1 -> ^1.8); skip constraint-style changes
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
 * Transform global package analysis data into categorized issue reports.
 *
 * This step prepares the reporting structures used by:
 * - detailed reports
 * - CI summaries
 * - current branch responsibility analysis
 *
 * Repository-analysis mutual-exclusion invariant:
 *   issuesByPackage ∩ stableRegressions = ∅
 *
 * Each package belongs to issuesByPackage, stableRegressions, or neither — never both.
 * Stable-regression classification has priority: when processStableRegressions() returns
 * regression info, the package is stored in stableRegressions and the loop continues
 * without evaluating processStabilityIssues() for that package.
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
    // This ensures packages with only unstable versions (no regressions) are processed
    return $regressionInfo ?: [];
}

/**
 * Extract all historical dependency transitions for a single package.
 *
 * Returns every adjacent branch-to-branch transition of type:
 * - stability_regression
 * - version_downgrade
 *
 * This is a semantic extraction layer only. Reporting and CI paths are unchanged.
 *
 * Authoritative structured representation of all adjacent repository branch
 * transitions for historical_analysis. Repository normalized findings do not duplicate
 * structured transition fields; they carry type and message only.
 *
 * @param array<string, mixed> $history
 * @param array<string, mixed> $metadata
 * @param list<string> $versionsToCheck
 * @return list<array{
 *     package: string,
 *     type: 'stability_regression'|'version_downgrade',
 *     from_tiki: string,
 *     to_tiki: string,
 *     from_constraint: string,
 *     to_constraint: string,
 *     from_stability: string,
 *     to_stability: string
 * }>
 */
function extractPackageTransitions($package, $history, $metadata, $versionsToCheck)
{
    $transitions = [];

    if (! $metadata['has_regressions'] && ! $metadata['has_stable_regressions']) {
        return $transitions;
    }

    $versions = sortVersionsByTikiOrder($metadata['versions'], $versionsToCheck);

    for ($i = 1; $i < count($versions); $i++) {
        $prev = $versions[$i - 1];
        $current = $versions[$i];

        $prevConstraint = $prev['constraint'];
        $currentConstraint = $current['constraint'];
        $prevStability = extractStabilityFromVersion($prevConstraint);
        $currentStability = extractStabilityFromVersion($currentConstraint);

        if (
            $metadata['has_regressions']
            && getStabilityNumeric($currentStability) < getStabilityNumeric($prevStability)
        ) {
            $transitions[] = [
                'package' => $package,
                'type' => 'stability_regression',
                'from_tiki' => $prev['tiki_version'],
                'to_tiki' => $current['tiki_version'],
                'from_constraint' => $prevConstraint,
                'to_constraint' => $currentConstraint,
                'from_stability' => $prevStability,
                'to_stability' => $currentStability,
            ];
        }

        if (
            $metadata['has_stable_regressions']
            && $prevStability === 'stable'
            && $currentStability === 'stable'
        ) {
            $prevBase = getNormalizedBaseVersion($prevConstraint);
            $currentBase = getNormalizedBaseVersion($currentConstraint);

            if ($prevBase && $currentBase && version_compare($prevBase, $currentBase) > 0) {
                if (isConstraintStyleChange($prevBase, $currentBase)) {
                    continue;
                }

                $transitions[] = [
                    'package' => $package,
                    'type' => 'version_downgrade',
                    'from_tiki' => $prev['tiki_version'],
                    'to_tiki' => $current['tiki_version'],
                    'from_constraint' => $prevConstraint,
                    'to_constraint' => $currentConstraint,
                    'from_stability' => $prevStability,
                    'to_stability' => $currentStability,
                ];
            }
        }
    }

    return $transitions;
}

/**
 * Compute non-CI exit code from repository analysis (no globals or renderer side effects).
 *
 * @param array{issuesByPackage: array, stableRegressions: array} $repositoryAnalysis
 */
function computeNonCiExitCode(array $repositoryAnalysis): int
{
    return (
        empty($repositoryAnalysis['issuesByPackage'])
        && empty($repositoryAnalysis['stableRegressions'])
    ) ? 0 : 1;
}

/**
 * Build human-readable stability text for a package constraint (branch row display).
 */
function buildStabilityDisplayText(string $constraint): string
{
    $stability = extractStabilityFromVersion($constraint);
    $stabilityText = $stability;

    if ($stability === 'stable') {
        $stabilityText .= ' - ' . getVersionConstraintType($constraint);
    }

    return $stabilityText;
}

/**
 * Build normalized mitigation presentation for a branch row.
 *
 * @return array{applicable: bool, reason: ?string, mitigation: ?string, alternative: ?string, reviewed: ?string}
 */
function buildMitigationPresentation(string $package, string $tikiVersion, array $ignoreMap): array
{
    if (! isPackageIgnored($package, $tikiVersion, $ignoreMap)) {
        return [
            'applicable' => false,
            'reason' => null,
            'mitigation' => null,
            'alternative' => null,
            'reviewed' => null,
        ];
    }

    $info = getIgnoreReason($package, $tikiVersion, $ignoreMap) ?? [];

    return [
        'applicable' => true,
        'reason' => $info['reason'] ?? null,
        'mitigation' => $info['mitigation'] ?? null,
        'alternative' => $info['alternative'] ?? null,
        'reviewed' => $info['reviewed'] ?? null,
    ];
}

/**
 * Derive branch status for packages in issuesByPackage (stability-issues presentation).
 */
function deriveBranchStatusForStabilityIssues(
    string $package,
    string $tikiVersion,
    string $constraint,
    array $ignoreMap
): string {
    if (isPackageIgnored($package, $tikiVersion, $ignoreMap)) {
        return 'MITIGATED';
    }

    if (extractStabilityFromVersion($constraint) !== 'stable') {
        return 'WARNING';
    }

    return 'INFO';
}

/**
 * Derive branch status for packages in stableRegressions (stable-regression presentation).
 */
function deriveBranchStatusForStableRegression(
    string $package,
    string $tikiVersion,
    string $constraint,
    array $ignoreMap,
    bool $hasUnmitigatedDowngrade,
    ?array $regressionInfo
): string {
    if (isPackageIgnored($package, $tikiVersion, $ignoreMap)) {
        return 'MITIGATED';
    }

    $stability = extractStabilityFromVersion($constraint);
    $isPartOfUnmitigatedDowngrade = false;

    if ($regressionInfo && $hasUnmitigatedDowngrade && $tikiVersion === $regressionInfo['to_tiki']) {
        $isPartOfUnmitigatedDowngrade = true;
    }

    if ($stability !== 'stable' || $isPartOfUnmitigatedDowngrade) {
        return 'WARNING';
    }

    return 'INFO';
}

/**
 * Build normalized branch rows for a package card.
 *
 * @param 'stability_issues'|'stable_regression' $presentationMode
 * @return list<array<string, mixed>>
 */
function buildPackageBranchRows(
    string $package,
    array $history,
    array $ignoreMap,
    string $presentationMode,
    bool $hasUnmitigatedDowngrade = false,
    ?array $regressionInfo = null
): array {
    $rows = [];

    foreach ($history as $tikiVersion => $constraint) {
        if ($tikiVersion === '_metadata') {
            continue;
        }

        if ($presentationMode === 'stable_regression') {
            $status = deriveBranchStatusForStableRegression(
                $package,
                $tikiVersion,
                $constraint,
                $ignoreMap,
                $hasUnmitigatedDowngrade,
                $regressionInfo
            );
        } else {
            $status = deriveBranchStatusForStabilityIssues($package, $tikiVersion, $constraint, $ignoreMap);
        }

        $rows[] = [
            'tiki_version' => $tikiVersion,
            'constraint' => $constraint,
            'stability' => buildStabilityDisplayText($constraint),
            'status' => $status,
            'mitigation' => buildMitigationPresentation($package, $tikiVersion, $ignoreMap),
        ];
    }

    return $rows;
}

/**
 * Derive package-level presentation for repository package cards.
 *
 * @param 'stability_issues'|'stable_regression' $presentationMode
 * @return array{icon: string, overall_status: string, has_unmitigated_downgrade: bool}
 */
function deriveRepositoryPackagePresentation(
    string $package,
    array $history,
    array $ignoreMap,
    string $presentationMode,
    ?array $regressionInfo = null
): array {
    $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
    $hasWarnings = $packageStatus['hasWarnings'];
    $allVersionsMitigated = $packageStatus['allVersionsMitigated'];
    $hasUnmitigatedDowngrade = false;

    if ($presentationMode === 'stable_regression' && $regressionInfo) {
        $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated(
            $package,
            $regressionInfo['from_tiki'],
            $regressionInfo['to_tiki'],
            $ignoreMap
        );
    }

    if ($presentationMode === 'stable_regression') {
        $icon = '🔸';
        $overallStatus = 'INFO';

        if ($hasWarnings || $hasUnmitigatedDowngrade) {
            $icon = '⚠️';
            $overallStatus = 'WARNING';
        } elseif ($allVersionsMitigated || (! $hasUnmitigatedDowngrade && $regressionInfo)) {
            $overallStatus = 'MITIGATED';
        }

        return [
            'icon' => $icon,
            'overall_status' => $overallStatus,
            'has_unmitigated_downgrade' => $hasUnmitigatedDowngrade,
        ];
    }

    $overallStatus = 'INFO';
    if ($hasWarnings) {
        $overallStatus = 'WARNING';
    } elseif ($allVersionsMitigated) {
        $overallStatus = 'MITIGATED';
    }

    return [
        'icon' => '⚠️',
        'overall_status' => $overallStatus,
        'has_unmitigated_downgrade' => false,
    ];
}

/**
 * Derive package-level presentation for CI responsible package cards from package
 * history and stability-ignore status.
 *
 * @return array{icon: string, overall_status: string}
 */
function deriveCiResponsiblePackagePresentation(string $package, array $history, array $ignoreMap): array
{
    $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
    $overallStatus = 'INFO';

    if ($packageStatus['hasWarnings']) {
        $overallStatus = 'WARNING';
    } elseif ($packageStatus['allVersionsMitigated']) {
        $overallStatus = 'MITIGATED';
    }

    return [
        'icon' => '⚠️',
        'overall_status' => $overallStatus,
    ];
}

/**
 * Normalize repository findings from processPackageData() output only.
 *
 * Do not use extractPackageTransitions() here; historical transitions belong in historical_analysis.
 *
 * Repository findings contain only type and message for renderer/control-flow use.
 * Structured repository transition endpoints live in historical_analysis.transitions[]
 * and in regression_info used for card presentation. Renderers do not read transition
 * fields from repository findings.
 *
 * Renderer contract: type and message are the active consumer-facing fields.
 * Type may participate in package classification; message is rendered output.
 *
 * @return list<array<string, mixed>>
 */
function normalizeRepositoryFindings(string $package, ?array $issuesEntry, ?array $stableRegressionEntry): array
{
    $findings = [];

    if ($issuesEntry !== null) {
        $metadata = $issuesEntry['metadata'];

        if (! empty($issuesEntry['regression_info']) && ! empty($metadata['has_regressions'])) {
            $info = $issuesEntry['regression_info'];
            $findings[] = [
                'type' => 'repository_stability_regression',
                'message' => "{$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}",
            ];
        }

        if (! empty($metadata['has_unstable'])) {
            $findings[] = [
                'type' => 'repository_unstable_usage',
                'message' => "Unstable versions used in this package's history",
            ];
        }
    }

    if ($stableRegressionEntry !== null && ! empty($stableRegressionEntry['regression_info'])) {
        $info = $stableRegressionEntry['regression_info'];
        $findings[] = [
            'type' => 'repository_version_downgrade',
            'message' => "{$package} {$info['to_version']} in {$info['to_tiki']} is less stable than {$package} {$info['from_version']} in {$info['from_tiki']}",
        ];
    }

    return $findings;
}

/**
 * Normalize CI responsibility findings for package cards.
 *
 * One upstream newIssues entry produces exactly one normalized finding.
 * The normalized CI finding schema contains only type and message.
 * ci_decision.newIssues[] remains the authoritative structured CI issue-state source.
 * Normalized CI findings are renderer-oriented projections.
 * Renderers and control flow consume the projected type/message fields.
 *
 * @param list<array<string, mixed>> $issues
 * @return list<array<string, mixed>>
 */
function normalizeCiFindings(array $issues): array
{
    $findings = [];

    foreach ($issues as $issue) {
        switch ($issue['type']) {
            case 'new_issue':
                $stability = $issue['stability'] ?? 'unknown';
                $version = $issue['version'] ?? 'unknown';
                $reason = isset($issue['reason']) ? " - {$issue['reason']}" : '';
                $findings[] = [
                    'type' => 'new_issue',
                    'message' => "Introduced unstable version `{$version}` ({$stability}){$reason}",
                ];
                break;
            case 'worsened_existing':
                $previousStability = $issue['previous_stability'] ?? 'unknown';
                $previousVersion = $issue['previous_version'] ?? 'unknown';
                $currentStability = $issue['current_stability'] ?? 'unknown';
                $currentVersion = $issue['current_version'] ?? 'unknown';
                $findings[] = [
                    'type' => 'worsened_existing',
                    'message' => "Made existing issue worse: from `{$previousVersion}` ({$previousStability}) to `{$currentVersion}` ({$currentStability})",
                ];
                break;
            case 'new_version_downgrade':
                $fromVersion = $issue['from_version'] ?? 'unknown';
                $toVersion = $issue['to_version'] ?? 'unknown';
                $findings[] = [
                    'type' => 'new_version_downgrade',
                    'message' => "Introduced version downgrade from `{$fromVersion}` to `{$toVersion}`",
                ];
                break;
            default:
                $findings[] = [
                    'type' => $issue['type'],
                    'message' => "CI issue: {$issue['type']}",
                ];
                break;
        }
    }

    return $findings;
}

/**
 * Build a normalized package card from pre-selected analysis data.
 *
 * PackageCard.scope records card origin at build time (repository vs ci_responsible).
 * CI cards receive pre-normalized findings; this builder does not mutate finding shape.
 *
 * @param 'repository'|'ci_responsible' $scope
 * @param 'stability_issues'|'stable_regression' $presentationMode
 * @return array<string, mixed>
 */
function buildPackageCard(
    string $package,
    string $scope,
    array $history,
    array $ignoreMap,
    array $findings,
    string $presentationMode,
    ?array $regressionInfo = null,
    bool $hasUnmitigatedDowngrade = false
): array {
    if ($scope === 'ci_responsible') {
        $presentation = deriveCiResponsiblePackagePresentation($package, $history, $ignoreMap);
    } else {
        $presentation = deriveRepositoryPackagePresentation($package, $history, $ignoreMap, $presentationMode, $regressionInfo);
        if ($presentationMode === 'stable_regression') {
            $hasUnmitigatedDowngrade = $presentation['has_unmitigated_downgrade'];
        }
    }

    return [
        'package' => $package,
        'overall_status' => $presentation['overall_status'],
        'scope' => $scope,
        'icon' => $presentation['icon'],
        'branches' => buildPackageBranchRows(
            $package,
            $history,
            $ignoreMap,
            $presentationMode,
            $hasUnmitigatedDowngrade,
            $regressionInfo
        ),
        'findings' => $findings,
    ];
}

/**
 * Whether a repository package card represents a stable-regression (downgrade) package.
 *
 * Control-flow contract: true when any normalized finding has type
 * repository_version_downgrade. Other finding types do not affect classification.
 */
function isStableRegressionPackageCard(array $card): bool
{
    foreach ($card['findings'] as $finding) {
        if ($finding['type'] === 'repository_version_downgrade') {
            return true;
        }
    }

    return false;
}

/**
 * Build repository-state section of the Report Model from repository analysis output.
 *
 * Relies on the processPackageData() invariant that issuesByPackage and
 * stableRegressions are disjoint. packageCount equals the number of cards built
 * from both maps, which matches unique package names while that invariant holds.
 *
 * PackageCard + findings[] is in place; disjoint issuesByPackage/stableRegressions
 * already yields one repository card per package. A package in issuesByPackage may
 * produce multiple findings (e.g. stability regression and unstable usage).
 * Optional future refactor: merge the two build loops into one per-package builder.
 * Any merge must preserve classification priority (stable regressions win) and
 * current CLI report semantics (finding lines, branch presentation, section grouping).
 *
 * Repository normalized findings contain only type and message for renderer/control-flow
 * use. historical_analysis.transitions[] owns the complete structured transition list;
 * regression_info remains the first-transition analysis structure for card presentation.
 *
 * @param array{issuesByPackage: array, stableRegressions: array} $repositoryAnalysis
 */
function buildRepositoryStateModel(array $repositoryAnalysis, array $ignoreMap): array
{
    $packages = [];
    $issuesByPackage = $repositoryAnalysis['issuesByPackage'];
    $stableRegressions = $repositoryAnalysis['stableRegressions'];

    foreach ($issuesByPackage as $package => $data) {
        $findings = normalizeRepositoryFindings($package, $data, null);
        $regressionInfo = $data['regression_info'] ?? null;
        $packages[] = buildPackageCard(
            $package,
            'repository',
            $data['history'],
            $ignoreMap,
            $findings,
            'stability_issues',
            $regressionInfo
        );
    }

    foreach ($stableRegressions as $package => $data) {
        $findings = normalizeRepositoryFindings($package, null, $data);
        $regressionInfo = $data['regression_info'] ?? null;
        $hasUnmitigatedDowngrade = $regressionInfo
            ? ! isVersionDowngradeMitigated($package, $regressionInfo['from_tiki'], $regressionInfo['to_tiki'], $ignoreMap)
            : false;
        $packages[] = buildPackageCard(
            $package,
            'repository',
            $data['history'],
            $ignoreMap,
            $findings,
            'stable_regression',
            $regressionInfo,
            $hasUnmitigatedDowngrade
        );
    }

    $unstableCount = count(array_filter(
        $issuesByPackage,
        fn($entry) => ! empty($entry['metadata']['has_unstable'])
    ));
    $regressionCount = count(array_filter(
        $issuesByPackage,
        fn($entry) => ! empty($entry['metadata']['has_regressions'])
    ));

    return [
        'hasIssues' => ! empty($packages),
        'packageCount' => count($packages),
        'counts' => [
            'stability_issues' => count($issuesByPackage),
            'stable_regressions' => count($stableRegressions),
            'unstable' => $unstableCount,
            'regressions' => $regressionCount,
        ],
        'packages' => $packages,
    ];
}

/**
 * Build CI responsible package cards from already-filtered newIssues.
 *
 * ci_decision.newIssues[] is the authoritative structured CI issue-state source.
 * PackageCard findings are a deterministic renderer-oriented projection grouped by package.
 *
 * Package grouping preserves first package appearance order from newIssues[].
 * Finding order within each card preserves upstream issue order for that package.
 * Normalized CI findings contain only type and message. Structured CI issue metadata
 * remains exclusively in ci_decision.newIssues[].
 *
 * @param list<array<string, mixed>> $newIssues
 * @return list<array<string, mixed>>
 */
function buildCiResponsiblePackageCards(array $newIssues, array $packageHistory, array $ignoreMap): array
{
    $issuesByPackage = [];
    $packageOrder = [];

    foreach ($newIssues as $issue) {
        $package = $issue['package'];
        if (! isset($issuesByPackage[$package])) {
            $issuesByPackage[$package] = [];
            $packageOrder[] = $package;
        }
        $issuesByPackage[$package][] = $issue;
    }

    $cards = [];
    foreach ($packageOrder as $package) {
        $fullHistory = $packageHistory[$package] ?? [];
        $history = $fullHistory;
        unset($history['_metadata']);

        $cards[] = buildPackageCard(
            $package,
            'ci_responsible',
            $history,
            $ignoreMap,
            normalizeCiFindings($issuesByPackage[$package]),
            'stability_issues'
        );
    }

    return $cards;
}

/**
 * Build accepted-state section of report model from existing accepted-state analysis.
 *
 * Source of truth for accepted-state issue existence is acceptedStateAnalysis['existingIssues'].
 * This function enriches those issues with package-level history and mitigation status.
 *
 * @param array{existingIssues?: array<int, array<string, mixed>>}|null $acceptedStateAnalysis
 * @return array{
 *     applicable: bool,
 *     packages: array<int, array<string, mixed>>,
 *     hasIssues: bool,
 *     unmitigatedCount: int,
 *     hasWarnings: bool
 * }
 */
function buildAcceptedStateModel($acceptedStateAnalysis, $packageHistory, $ignoreMap)
{
    if ($acceptedStateAnalysis === null) {
        return [
            'applicable' => false,
            'packages' => [],
            'stability_issues' => [],
            'version_downgrades' => [],
            'hasIssues' => false,
            'unmitigatedCount' => 0,
            'hasWarnings' => false,
        ];
    }

    $existingIssues = $acceptedStateAnalysis['existingIssues'] ?? [];
    $packages = [];
    $stabilityIssues = [];
    $versionDowngrades = [];

    // Preserve existingIssues count, order, and issue payloads exactly.
    // buildJUnitReportModel() consumes accepted_state.packages[].issue in this order.
    foreach ($existingIssues as $issue) {
        $package = $issue['package'];
        $history = $packageHistory[$package] ?? [];
        $packageStatus = determinePackageStatus($package, $history, $ignoreMap);

        $hasUnmitigatedDowngrade = false;
        if (isset($issue['from_tiki'], $issue['to_tiki'])) {
            $hasUnmitigatedDowngrade = ! isVersionDowngradeMitigated(
                $package,
                $issue['from_tiki'],
                $issue['to_tiki'],
                $ignoreMap
            );
        }

        $packages[] = [
            'package' => $package,
            'issue' => $issue,
            'history' => $history,
            'hasWarnings' => $packageStatus['hasWarnings'] || $hasUnmitigatedDowngrade,
            'allVersionsMitigated' => $packageStatus['allVersionsMitigated'],
            'hasUnmitigatedDowngrade' => $hasUnmitigatedDowngrade,
        ];

        if ($issue['has_unstable'] || $issue['has_regression']) {
            $issueTypes = [];
            if ($issue['has_regression']) {
                $issueTypes[] = 'Stability regression detected';
            }
            if ($issue['has_unstable']) {
                $issueTypes[] = 'Unstable versions used';
            }

            $statusSuffix = '';
            if ($packageStatus['hasWarnings']) {
                $statusSuffix = ' [WARNING]';
            } elseif ($packageStatus['allVersionsMitigated']) {
                $statusSuffix = ' [MITIGATED]';
            }

            $stabilityIssues[] = [
                'package' => $package,
                'issue' => $issue,
                'issueTypes' => $issueTypes,
                'statusSuffix' => $statusSuffix,
            ];
        }

        if ($issue['has_downgrade']) {
            $statusSuffix = '';
            if ($hasUnmitigatedDowngrade) {
                $statusSuffix = ' [WARNING]';
            } elseif (isset($issue['is_ignored']) && $issue['is_ignored']) {
                $statusSuffix = ' [MITIGATED]';
            }

            $versionDowngrades[] = [
                'package' => $package,
                'issue' => $issue,
                'statusSuffix' => $statusSuffix,
            ];
        }
    }

    $unmitigatedCount = countUnmitigatedAcceptedStateIssues($existingIssues, $packageHistory, $ignoreMap);

    return [
        'applicable' => true,
        'packages' => $packages,
        'stability_issues' => $stabilityIssues,
        'version_downgrades' => $versionDowngrades,
        'hasIssues' => ! empty($existingIssues),
        'unmitigatedCount' => $unmitigatedCount,
        'hasWarnings' => $unmitigatedCount > 0,
    ];
}

/**
 * Build historical-analysis section of report model.
 *
 * Owns the authoritative full transition list per package via extractPackageTransitions().
 * Repository normalized findings do not duplicate structured transition fields.
 *
 * @return array{
 *     packages: array<string, array{
 *         transitions: list<array<string, mixed>>
 *     }>,
 *     transitionCount: int
 * }
 */
function buildHistoricalAnalysisModel($packageHistory, $versionsToCheck)
{
    $packages = [];
    $transitionCount = 0;

    foreach ($packageHistory as $package => $history) {
        if (! isset($history['_metadata'])) {
            continue;
        }

        $metadata = $history['_metadata'];
        $historyWithoutMetadata = $history;
        unset($historyWithoutMetadata['_metadata']);

        $transitions = extractPackageTransitions($package, $historyWithoutMetadata, $metadata, $versionsToCheck);
        if ($transitions === []) {
            continue;
        }

        $packages[$package] = [
            'transitions' => $transitions,
        ];
        $transitionCount += count($transitions);
    }

    return [
        'packages' => $packages,
        'transitionCount' => $transitionCount,
    ];
}

/**
 * Build CI decision section of report model from existing CI analysis outputs.
 *
 * @param array{shouldFail: bool, newIssues: array, existingIssues: array}|null $acceptedStateAnalysis
 * @param array{unmitigatedCount: int, hasWarnings: bool, exitCode: int}|null $outcome
 * @return array<string, mixed>
 */
function buildCiDecisionModel($acceptedStateAnalysis, $outcome, $packageHistory = [], $ignoreMap = [])
{
    if (! $acceptedStateAnalysis) {
        return [
            'applicable' => false,
            'shouldFail' => false,
            'newIssues' => [],
            'existingIssues' => [],
            'responsible_packages' => [],
            'unmitigatedCount' => 0,
            'hasWarnings' => false,
            'exitCode' => 0,
        ];
    }

    return [
        'applicable' => true,
        'shouldFail' => $acceptedStateAnalysis['shouldFail'],
        'newIssues' => $acceptedStateAnalysis['newIssues'],
        'existingIssues' => $acceptedStateAnalysis['existingIssues'],
        'responsible_packages' => buildCiResponsiblePackageCards(
            $acceptedStateAnalysis['newIssues'],
            $packageHistory,
            $ignoreMap
        ),
        'unmitigatedCount' => $outcome['unmitigatedCount'] ?? 0,
        'hasWarnings' => $outcome['hasWarnings'] ?? false,
        'exitCode' => $outcome['exitCode'] ?? 0,
    ];
}

/**
 * Build complete report model for rendering and CI contract checks.
 *
 * ci_decision.newIssues[] and ci_decision.responsible_packages coexist in the final
 * Report Model. newIssues[] is the authoritative structured CI issue-state source;
 * responsible_packages[].findings[] is the grouped renderer-oriented projection.
 *
 * @param array{issuesByPackage: array, stableRegressions: array} $repositoryAnalysis
 * @param array{shouldFail: bool, newIssues: array, existingIssues: array}|null $acceptedStateAnalysis
 * @param array{unmitigatedCount: int, hasWarnings: bool, exitCode: int}|null $outcome
 * @return array<string, mixed>
 */
function buildStabilityReportModel(
    $packageHistory,
    $versionsToCheck,
    $ignoreMap,
    array $repositoryAnalysis,
    $acceptedStateAnalysis = null,
    $outcome = null
) {
    $acceptedState = buildAcceptedStateModel($acceptedStateAnalysis, $packageHistory, $ignoreMap);

    return [
        'accepted_state' => $acceptedState,
        'repository_state' => buildRepositoryStateModel($repositoryAnalysis, $ignoreMap),
        'historical_analysis' => buildHistoricalAnalysisModel($packageHistory, $versionsToCheck),
        'ci_decision' => buildCiDecisionModel($acceptedStateAnalysis, $outcome, $packageHistory, $ignoreMap),
        'junit_report' => buildJUnitReportModel($acceptedState),
    ];
}

/**
 * Format a bracketed, comma-separated package list for CI summary lines.
 */
function formatPackageListForSummary(array $issues): string
{
    if ($issues === []) {
        return '';
    }

    $packages = array_values(array_unique(array_column($issues, 'package')));
    sort($packages, SORT_STRING);

    return ' [' . implode(', ', $packages) . ']';
}

/**
 * Render CI executive summary from the report model.
 */
function renderExecutiveSummary(array $reportModel)
{
    $ciDecision = $reportModel['ci_decision'];
    $acceptedState = $reportModel['accepted_state'];
    $hasWarnings = $ciDecision['hasWarnings'];
    $unmitigatedCount = $ciDecision['unmitigatedCount'];

    if ($ciDecision['shouldFail']) {
        echo "\n## 🚨 Current Branch Introduces New Stability Issues\n\n";

        if (! empty($ciDecision['newIssues'])) {
            echo "### Issues Introduced by Current Branch:\n";
            foreach ($ciDecision['newIssues'] as $issue) {
                switch ($issue['type']) {
                    case 'new_issue':
                        $stability = $issue['stability'] ?? 'unknown';
                        $version = $issue['version'] ?? 'unknown';
                        $reason = isset($issue['reason']) ? " - {$issue['reason']}" : "";
                        echo "- **{$issue['package']}**: Introduced unstable version `{$version}` ({$stability}){$reason}\n";
                        break;
                    case 'worsened_existing':
                        $previousStability = $issue['previous_stability'] ?? 'unknown';
                        $previousVersion = $issue['previous_version'] ?? 'unknown';
                        $currentStability = $issue['current_stability'] ?? 'unknown';
                        $currentVersion = $issue['current_version'] ?? 'unknown';
                        echo "- **{$issue['package']}**: Made existing issue worse: from `{$previousVersion}` ({$previousStability}) to `{$currentVersion}` ({$currentStability})\n";
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

        echo "\n**Pipeline Failed**: Current branch introduces stability issues that must be fixed or mitigated.\n";
        return;
    }

    if ($acceptedState['hasIssues']) {
        if ($hasWarnings) {
            echo "\n## ⚠️ Stability Issues Detected in Accepted State\n\n";
            echo "**Reason**: Accepted state contains packages with [WARNING] tags (not mitigated).\n\n";
        } else {
            echo "\n## ⚠️ Stability Issues Detected (All Mitigated)\n\n";
            echo "**Status**: All existing accepted-state issues are documented and mitigated.\n\n";
        }

        if (! empty($acceptedState['stability_issues'])) {
            echo "### Current Accepted-State Stability Warnings:\n";
            foreach ($acceptedState['stability_issues'] as $entry) {
                echo "- **{$entry['package']}**: ";
                echo implode(' & ', $entry['issueTypes']);
                echo $entry['statusSuffix'];
                echo "\n";
            }
        }

        if (! empty($acceptedState['version_downgrades'])) {
            echo "\n";
            echo "\n### Current Accepted-State Historical Stable Version Downgrades:\n";
            foreach ($acceptedState['version_downgrades'] as $entry) {
                $issue = $entry['issue'];
                echo "- **{$entry['package']}**: Version downgrade detected";

                if (
                    isset($issue['from_tiki'], $issue['to_tiki'], $issue['from_version'], $issue['to_version'])
                ) {
                    echo " in `{$issue['to_tiki']}` ";
                    echo "(from `{$issue['from_version']}` in `{$issue['from_tiki']}` to `{$issue['to_version']}` in `{$issue['to_tiki']}`)";
                }

                echo $entry['statusSuffix'];
                echo "\n";
            }
        }

        if ($hasWarnings) {
            if (! $ciDecision['shouldFail']) {
                $issueLabel = $unmitigatedCount === 1 ? 'issue' : 'issues';
                echo "\n**Current MR introduces no new dependency problems.**\n\n";
                echo "**Repository still contains {$unmitigatedCount} unmitigated accepted-state {$issueLabel}.**\n";
                echo "The pipeline fails due to existing dependency technical debt, not because of changes in this branch.\n";
            } else {
                echo "\n**Pipeline Failed**: Accepted state contains unmitigated stability warnings.\n";
            }
            echo "**Action Suggested**:\n";
            echo "1. Fix unstable package versions in accepted state where possible, OR\n";
            echo "2. Add them to the `stability-ignore` list in `vendor_bundled/composer.json` with:\n";
            echo "   - `reason`: Why this unstable version is acceptable\n";
            echo "   - `mitigation`: How you're handling the risk\n";
            echo "   - `reviewed`: Date of review\n";
        } else {
            echo "\n**Pipeline Warning**: All existing accepted-state issues are properly mitigated.\n";
            echo "**Note**: These mitigated issues are documented and tracked for future cleanup.\n";
        }

        return;
    }

    echo "\n## ✅ No Composer stability issues detected\n";
}

/**
 * Render a single package card (formatting only).
 *
 * Renderer contract: consume package-level presentation and finding message for CLI
 * output. Finding type participates in upstream section grouping via
 * isStableRegressionPackageCard(). Repository findings expose only type and message.
 */
function renderPackageCard(array $card): void
{
    $icon = $card['icon'] ?? '⚠️';
    $statusSuffix = '';

    if ($card['overall_status'] === 'WARNING') {
        $statusSuffix = ' [WARNING]';
    } elseif ($card['overall_status'] === 'MITIGATED') {
        $statusSuffix = ' [MITIGATED]';
    }

    echo "\n{$icon} Package: {$card['package']}{$statusSuffix}\n";
    echo "   Branches:\n";

    foreach ($card['branches'] as $branch) {
        echo "   🟥 Tiki {$branch['tiki_version']}: {$branch['constraint']} ({$branch['stability']})";

        if ($branch['status'] === 'MITIGATED') {
            echo " [MITIGATED]";
        } elseif ($branch['status'] === 'WARNING') {
            echo " [WARNING]";
        }

        echo "\n";

        if (! empty($branch['mitigation']['applicable'])) {
            $mitigation = $branch['mitigation'];
            $fields = [
                'reason' => 'reason',
                'mitigation' => 'mitigation',
                'alternative' => 'alternative',
                'reviewed' => 'reviewed',
            ];

            foreach ($fields as $key => $label) {
                if (! empty($mitigation[$key])) {
                    echo "     ↳ {$label}: {$mitigation[$key]}\n";
                }
            }
        }
    }

    foreach ($card['findings'] as $finding) {
        echo "\n   ❗ {$finding['message']}\n";
    }
}

/**
 * Package names already shown in the MR responsibility section (CI dedupe).
 */
function getMrResponsiblePackageNames(array $reportModel): array
{
    if (! $reportModel['ci_decision']['applicable']) {
        return [];
    }

    $names = [];
    foreach ($reportModel['ci_decision']['responsible_packages'] as $card) {
        if (! empty($card['package'])) {
            $names[] = $card['package'];
        }
    }

    return $names;
}

/**
 * Render MR-responsible package cards immediately after the executive summary.
 */
function renderMrResponsibilitySection(array $reportModel): void
{
    $ciDecision = $reportModel['ci_decision'];

    if (! $ciDecision['applicable'] || empty($ciDecision['responsible_packages'])) {
        return;
    }

    echo "\n## MR Responsibility (Action Required)\n\n";
    echo "### Packages your branch must address\n";

    foreach ($ciDecision['responsible_packages'] as $card) {
        renderPackageCard($card);
    }
}

/**
 * Render repository-state package cards for detailed report context.
 */
function renderRepositoryStateSection(array $reportModel): void
{
    $repositoryState = $reportModel['repository_state'];
    $excludePackages = array_flip(getMrResponsiblePackageNames($reportModel));

    if (! $repositoryState['hasIssues']) {
        echo "\n✅ No stability issues detected across all checked branches!\n";
        return;
    }

    $stabilityCards = [];
    $regressionCards = [];

    foreach ($repositoryState['packages'] as $card) {
        if (isset($excludePackages[$card['package'] ?? ''])) {
            continue;
        }

        if (isStableRegressionPackageCard($card)) {
            $regressionCards[] = $card;
        } else {
            $stabilityCards[] = $card;
        }
    }

    if (empty($stabilityCards) && empty($regressionCards)) {
        echo "\n- No additional repository-context package cards (MR-responsible packages shown above).\n";
        return;
    }

    if (! empty($stabilityCards)) {
        echo "\n❌ Stability issues detected in packages:\n";
        foreach ($stabilityCards as $card) {
            renderPackageCard($card);
        }
    }

    if (! empty($regressionCards)) {
        echo "\n⚠️ Packages with stable version downgrades (potential regressions):\n";
        foreach ($regressionCards as $card) {
            renderPackageCard($card);
        }
    }
}

/**
 * Render current accepted-state status section from report model.
 */
function renderAcceptedStateStatusSection(array $reportModel): void
{
    $acceptedState = $reportModel['accepted_state'];

    echo "\n### Current Accepted-State Status\n";

    if (! $acceptedState['hasIssues']) {
        echo "- No accepted-state stability issues detected.\n";
        return;
    }

    echo "- Accepted-state packages with issues: " . count($acceptedState['packages']) . "\n";
    echo "- Unmitigated accepted-state issue count: {$acceptedState['unmitigatedCount']}\n";
}

/**
 * Render historical dependency analysis section from report model.
 */
function renderHistoricalDependencyAnalysisSection(array $reportModel): void
{
    $historical = $reportModel['historical_analysis'];

    echo "\n### Historical Dependency Analysis\n";

    if ($historical['transitionCount'] === 0) {
        echo "- No historical dependency transitions detected.\n";
        return;
    }

    echo "- Total transitions detected: {$historical['transitionCount']}\n";
    foreach ($historical['packages'] as $package => $data) {
        echo "- **{$package}**:\n";
        foreach ($data['transitions'] as $transition) {
            $label = $transition['type'] === 'version_downgrade' ? 'Version downgrade' : 'Stability regression';
            echo "  - {$label}: `{$transition['from_tiki']}` `{$transition['from_constraint']}` ({$transition['from_stability']})";
            echo " -> `{$transition['to_tiki']}` `{$transition['to_constraint']}` ({$transition['to_stability']})\n";
        }
    }
}

/**
 * Render CI decision section from report model.
 */
function renderCiDecisionSection(array $reportModel): void
{
    $ciDecision = $reportModel['ci_decision'];

    echo "\n### CI Decision\n";

    if (! $ciDecision['applicable']) {
        echo "- Not applicable (non-CI mode).\n";
        return;
    }

    echo "- Current branch responsible findings: " . count($ciDecision['newIssues']) . "\n";
    echo "- Accepted-state warnings present: " . ($ciDecision['hasWarnings'] ? 'yes' : 'no') . "\n";
    echo "- shouldFail: " . ($ciDecision['shouldFail'] ? 'true' : 'false') . "\n";
    echo "- hasWarnings: " . ($ciDecision['hasWarnings'] ? 'true' : 'false') . "\n";
    echo "- exitCode: {$ciDecision['exitCode']}\n";
}

/**
 * Render detailed report from report model.
 */
function renderDetailedReport(array $reportModel)
{
    echo "\nPackage Stability Analysis:\n==================\n";

    if ($reportModel['ci_decision']['applicable']) {
        renderAcceptedStateStatusSection($reportModel);
    }

    renderRepositoryStateSection($reportModel);
    renderHistoricalDependencyAnalysisSection($reportModel);
    renderCiDecisionSection($reportModel);
}

/**
 * Render non-CI executive summary from report model.
 */
function renderNonCiExecutiveSummary(array $reportModel)
{
    $repositoryState = $reportModel['repository_state'];
    $counts = $repositoryState['counts'];
    $totalIssuePackages = $repositoryState['packageCount'];

    echo "\n## Report Summary\n\n";

    if ($totalIssuePackages === 0) {
        echo "- No stability issues detected across checked versions.\n";
        return;
    }

    echo "- Packages with issues: {$totalIssuePackages}\n";
    echo "- Unstable versions: {$counts['unstable']}\n";
    echo "- Stability regressions: {$counts['regressions']}\n";
    echo "- Stable version downgrades: {$counts['stable_regressions']}\n";
}

/**
 * CI responsibility model
 *
 * The stability checker analyzes dependency history across all relevant
 * Tiki branches in order to detect:
 * - unstable package usage
 * - stability regressions
 * - stable version downgrades
 *
 * However, CI failure responsibility is limited to packages modified
 * in the current branch.
 *
 * This means:
 * - existing accepted-state issues do not fail unrelated pipelines
 * - the current branch only fails if it introduces or worsens a problem
 * - cross-branch comparisons are performed to detect regressions
 *   relative to other branches (including master)
 *
 * The complete dependency history is analyzed globally.
 * Cross-branch analysis is performed globally, but pipeline failure responsibility
 * is restricted to packages modified in the current branch.
 */

/**
 * Pure CI responsibility analysis from prepared inputs.
 *
 * Architectural boundary: no globals, git, merge-base resolution, environment
 * lookups, or I/O. Touched packages must be resolved by the orchestration layer.
 *
 * @param array<string, array<string, mixed>> $touchedPackages
 * @return array{
 *     shouldFail: bool,
 *     newIssues: array<int, array<string, mixed>>,
 *     existingIssues: array<int, array<string, mixed>>,
 *     currentBranchIssues: array<int, array<string, mixed>>,
 *     touchedPackages: array<string, array<string, mixed>>
 * }
 */
function analyzeCiResponsibility(
    array $packageHistory,
    array $versionsToCheck,
    array $ignoreMap,
    string $currentBranch,
    array $touchedPackages
) {
    // Stable branches = all compared versions except the current feature branch
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

        $acceptedStateAnalysis = analyzePackageAcceptedState($package, $history, $metadata, $stableVersions, $ignoreMap);
        if ($acceptedStateAnalysis) {
            $existingIssues[] = $acceptedStateAnalysis;
        }

        $currentBranchAnalysis = analyzePackageCurrentBranch($package, $history, $metadata, $currentBranch, $acceptedStateAnalysis, $ignoreMap);
        if ($currentBranchAnalysis) {
            $currentBranchIssues[] = $currentBranchAnalysis;
        }

        $downgradeAnalysis = analyzePackageVersionDowngrades($package, $history, $metadata, $versionsToCheck, $currentBranch, $ignoreMap);
        if ($downgradeAnalysis) {
            $existingIssues[] = $downgradeAnalysis;
        }
    }

    $existingIssues = aggregateExistingIssues($existingIssues);

    // Only packages modified in the current branch can make the pipeline fail.
    // Cross-branch analysis is performed globally, but CI responsibility
    // is assigned only to touched packages.
    $filteredIssues = [];

    foreach ($currentBranchIssues as $issue) {
        $pkg = $issue['package'];
        if (isset($touchedPackages[$pkg])) {
            $filteredIssues[] = $issue;
        }
    }

    $shouldFail = ! empty($filteredIssues);

    return [
        'shouldFail' => $shouldFail,
        'newIssues' => $filteredIssues,
        'existingIssues' => $existingIssues,
        'currentBranchIssues' => $currentBranchIssues,
        'touchedPackages' => $touchedPackages,
    ];
}

/**
 * Determine whether the current branch introduces issues relative
 * to already accepted branch states.
 * To determine whether it introduces new issues or worsens existing accepted-state issues.
 */
function analyzeCurrentBranchAgainstAcceptedState($packageHistory, $versionsToCheck, $ignoreMap = [])
{
    global $isCiMode, $globalComposerData;
    $currentBranch = getCheckedOutBranchName();

    if (! $currentBranch || ! $isCiMode) {
        // Accepted-state / responsibility checks apply only in CI on a real branch
        return ['shouldFail' => false, 'newIssues' => [], 'existingIssues' => []];
    }

    $touchedPackages = getTouchedPackages($globalComposerData, $currentBranch);

    $result = analyzeCiResponsibility(
        $packageHistory,
        $versionsToCheck,
        $ignoreMap,
        $currentBranch,
        $touchedPackages
    );

    if ($isCiMode) {
        echo "Issues detected on current branch: "
            . count($result['currentBranchIssues'])
            . formatPackageListForSummary($result['currentBranchIssues']) . "\n";
        echo "Touched packages: " . count($result['touchedPackages']) . "\n";
        echo "Issues on touched packages (CI responsibility): "
            . count($result['newIssues'])
            . formatPackageListForSummary($result['newIssues']) . "\n";
        echo "Existing accepted-state issues: " . count($result['existingIssues']) . "\n";
    }

    return [
        'shouldFail' => $result['shouldFail'],
        'newIssues' => $result['newIssues'],
        'existingIssues' => $result['existingIssues'],
    ];
}

/**
 * Collect accepted-state stability issues for a single package.
 *
 * Global analysis of non-current branches; results feed the existing
 * accepted-state issues list and do not by themselves assign current-branch CI failure.
 */
function analyzePackageAcceptedState($package, $history, $metadata, $stableVersions, $ignoreMap)
{
    $acceptedStateIssue = checkAcceptedStateStabilityIssues($package, $history, $stableVersions, $ignoreMap);
    if ($acceptedStateIssue) {
        return $acceptedStateIssue;
    }

    $acceptedStateRegression = checkAcceptedStateStabilityRegressions($package, $metadata, $stableVersions, $ignoreMap);
    if ($acceptedStateRegression) {
        return $acceptedStateRegression;
    }

    return null;
}

/**
 * Check for stability issues in accepted-state (non-current) branch versions.
 */
function checkAcceptedStateStabilityIssues($package, $history, $stableVersions, $ignoreMap)
{
    $hasAcceptedStateIssues = false;
    $acceptedStateStability = null;
    $acceptedStateVersion = null;

    foreach ($stableVersions as $stableVersion) {
        if (isset($history[$stableVersion])) {
            $constraint = $history[$stableVersion];
            $stability = extractStabilityFromVersion($constraint);

            if ($stability !== 'stable') {
                $hasAcceptedStateIssues = true;
                $acceptedStateStability = $stability;
                $acceptedStateVersion = $constraint;
            }
        }
    }

    if (! $hasAcceptedStateIssues) {
        return null;
    }

    $ignoreInfo = checkPackageIgnoreStatus($package, $stableVersions, $ignoreMap);

    return [
        'package' => $package,
        'type' => 'existing_issue',
        'stability' => $acceptedStateStability ?? 'unknown',
        'version' => $acceptedStateVersion ?? 'unknown',
        'tiki_version' => 'accepted_state',
        'is_ignored' => $ignoreInfo['is_ignored'],
        'ignore_reason' => $ignoreInfo['ignore_reason']
    ];
}

/**
 * Check for stability regressions across accepted-state (non-current) branch versions.
 */
function checkAcceptedStateStabilityRegressions($package, $metadata, $stableVersions, $ignoreMap)
{
    if (! $metadata['has_regressions']) {
        return null;
    }

    $stableVersionNames = array_values($stableVersions);
    $acceptedStateVersions = array_filter($metadata['versions'], function ($version) use ($stableVersionNames) {
        return in_array($version['tiki_version'], $stableVersionNames);
    });

    $acceptedStateVersions = sortVersionsByTikiOrder($acceptedStateVersions, $stableVersionNames);

    if (! hasStabilityRegressionInVersions($acceptedStateVersions)) {
        return null;
    }

    $ignoreInfo = checkPackageIgnoreStatus($package, $stableVersions, $ignoreMap);

    return [
        'package' => $package,
        'type' => 'existing_stability_regression',
        'from_stability' => 'accepted_state',
        'from_version' => 'accepted_state',
        'from_tiki' => 'accepted_state',
        'to_stability' => 'accepted_state',
        'to_version' => 'accepted_state',
        'to_tiki' => 'accepted_state',
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
 * Analyze whether the current branch introduces new stability issues
 * or worsens existing accepted-state issues.
 *
 * This function evaluates only the current branch state, while
 * cross-branch comparisons are computed from the complete package history.
 */
function analyzePackageCurrentBranch($package, $history, $metadata, $currentBranch, $acceptedStateAnalysis, $ignoreMap)
{
    if (! isset($history[$currentBranch])) {
        return null;
    }

    $currentConstraint = $history[$currentBranch];
    $currentStability = extractStabilityFromVersion($currentConstraint);

    if ($currentStability !== 'stable') {
        if ($acceptedStateAnalysis && $acceptedStateAnalysis['stability'] !== 'stable') {
            // Issue exists in accepted state and is unstable - check if current branch makes it worse
            $previousStability = $acceptedStateAnalysis['stability'] ?? 'unknown';
            $previousVersion = $acceptedStateAnalysis['version'] ?? 'unknown';
            $acceptedStateWasMitigated = $acceptedStateAnalysis['is_ignored'] ?? false;
            $currentIsMitigated = isPackageIgnored($package, $currentBranch, $ignoreMap);

            // Check if current branch loses the mitigation
            if ($acceptedStateWasMitigated && ! $currentIsMitigated) {
                return [
                    'package' => $package,
                    'type' => 'new_issue',
                    'stability' => $currentStability,
                    'version' => $currentConstraint,
                    'reason' => 'Mitigation lost - package was mitigated in accepted state but not in current branch'
                ];
            }

            if (getStabilityNumeric($currentStability) < getStabilityNumeric($previousStability)) {
                return [
                    'package' => $package,
                    'type' => 'worsened_existing',
                    'previous_stability' => $previousStability,
                    'previous_version' => $previousVersion,
                    'current_stability' => $currentStability,
                    'current_version' => $currentConstraint
                ];
            }
            // Note: existing issues are already counted above when scanning accepted state (if still mitigated or same)
        } else {
            // New issue introduced by current branch (either no accepted-state analysis or accepted state was stable)
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
            // Only consider branches before the current one to establish the accepted baseline
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
 * Detect stable version downgrades across branches.
 *
 * Downgrades are detected globally across the dependency history,
 * but only become CI failures if the downgrade is introduced
 * by the current branch.
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

            // Downgrades on the current branch are handled by analyzePackageCurrentBranch()
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
 * Count unmitigated warnings in existing accepted-state issues.
 * This includes both stability issues (unstable versions not mitigated) and
 * unmitigated version downgrades. Each package contributes at most one count:
 * an unmitigated stability warning takes precedence over a downgrade on the
 * same package.
 *
 * @param array $existingIssues Array of existing issues from accepted-state analysis
 * @param array $packageHistory Complete package history across all versions
 * @param array $ignoreMap Map of ignored packages per version
 * @return int Number of unmitigated accepted-state issues
 */
function countUnmitigatedAcceptedStateIssues($existingIssues, $packageHistory, $ignoreMap)
{
    if (empty($existingIssues)) {
        return 0;
    }

    $count = 0;

    foreach ($existingIssues as $issue) {
        $package = $issue['package'];
        $history = $packageHistory[$package];

        $packageStatus = determinePackageStatus($package, $history, $ignoreMap);
        if ($packageStatus['hasWarnings']) {
            $count++;
            continue;
        }

        if ($issue['has_downgrade']) {
            if (isset($issue['from_tiki'], $issue['to_tiki'])) {
                $fromVersion = $issue['from_tiki'];
                $toVersion = $issue['to_tiki'];
                if (! isVersionDowngradeMitigated($package, $fromVersion, $toVersion, $ignoreMap)) {
                    $count++;
                }
            }
        }
    }

    return $count;
}

/**
 * Derive CI warning count, warning flag, and exit code from accepted-state analysis.
 *
 * @param array{shouldFail: bool, newIssues: array, existingIssues: array} $analysisResult
 * @return array{unmitigatedCount: int, hasWarnings: bool, exitCode: int}
 */
function computeCiOutcome(array $analysisResult, array $packageHistory, array $ignoreMap)
{
    $unmitigatedCount = 0;
    if (! empty($analysisResult['existingIssues'])) {
        $unmitigatedCount = countUnmitigatedAcceptedStateIssues(
            $analysisResult['existingIssues'],
            $packageHistory,
            $ignoreMap
        );
    }
    // Exit with warning when accepted-state issues remain unmitigated (not covered by ignore rules)
    $hasWarnings = $unmitigatedCount > 0;

    // Fail on new issues in touched packages, or on unmitigated accepted-state warnings
    $exitCode = ($analysisResult['shouldFail'] || $hasWarnings) ? 1 : 0;

    return [
        'unmitigatedCount' => $unmitigatedCount,
        'hasWarnings' => $hasWarnings,
        'exitCode' => $exitCode,
    ];
}

// Main execution
if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    $currentVersion = getCurrentTikiVersion();
    if (! $currentVersion) {
        logError("Could not determine current Tiki version.");
        exit(1);
    }

    $versionsToCheck = getVersionsToCheck();
    $currentBranch = getCheckedOutBranchName();
    $targetBranch = getTargetBranch();

    echo "Current Tiki Branch: {$currentVersion}\n";
    if ($currentBranch && $currentBranch !== $targetBranch) {
        echo "Current Branch: {$currentBranch}\n";
        echo "Target Branch: {$targetBranch}\n";
    }
    echo "Comparing Tiki branches: " . implode(', ', $versionsToCheck) . "\n";

    $packageHistory = buildPackageHistory($versionsToCheck);

    $ignoreMap = buildStabilityIgnoreMap($packageHistory);

    if ($isCiMode) {
        $repositoryAnalysis = processPackageData($packageHistory, $versionsToCheck);
        $acceptedStateAnalysis = analyzeCurrentBranchAgainstAcceptedState($packageHistory, $versionsToCheck, $ignoreMap);

        $outcome = computeCiOutcome($acceptedStateAnalysis, $packageHistory, $ignoreMap);
        $reportModel = buildStabilityReportModel(
            $packageHistory,
            $versionsToCheck,
            $ignoreMap,
            $repositoryAnalysis,
            $acceptedStateAnalysis,
            $outcome
        );

        renderExecutiveSummary($reportModel);
        renderMrResponsibilitySection($reportModel);
        echo "\n\n## Detailed Package Report (Repository Context)\n";
        renderDetailedReport($reportModel);

        $junitReport = renderJUnitReport($reportModel['junit_report']);
        file_put_contents('composer-stability-report.xml', $junitReport);

        exit($outcome['exitCode']);
    } else {
        $repositoryAnalysis = processPackageData($packageHistory, $versionsToCheck);
        $reportModel = buildStabilityReportModel(
            $packageHistory,
            $versionsToCheck,
            $ignoreMap,
            $repositoryAnalysis,
            null,
            null
        );
        renderNonCiExecutiveSummary($reportModel);
        echo "\n## Detailed Package Report\n";
        renderDetailedReport($reportModel);
        exit(computeNonCiExitCode($repositoryAnalysis));
    }
}
