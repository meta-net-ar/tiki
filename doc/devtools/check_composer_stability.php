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
 * Format mitigation metadata lines for a mitigated package on a specific branch.
 */
function formatMitigationDetails($package, $tikiVersion, $ignoreMap)
{
    if (! isPackageIgnored($package, $tikiVersion, $ignoreMap)) {
        return [];
    }

    $ignoreInfo = getIgnoreReason($package, $tikiVersion, $ignoreMap);
    if (! $ignoreInfo) {
        return [];
    }

    $details = [];
    $fields = [
        'reason' => 'reason',
        'mitigation' => 'mitigation',
        'alternative' => 'alternative',
        'reviewed' => 'reviewed'
    ];

    foreach ($fields as $key => $label) {
        if (! empty($ignoreInfo[$key])) {
            $details[] = "     ↳ {$label}: {$ignoreInfo[$key]}";
        }
    }

    return $details;
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

    $isCiMode = false;
    $filteredArgs = [];

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
 * Check if a package is ignored in any accepted-state (non-current) branch version.
 */
function isPackageIgnoredInAcceptedState($package, $versionsToCheck, $ignoreMap)
{
    $currentBranch = getCheckedOutBranchName();
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
               //'url' => "https://gitlab.com/tikiwiki/tiki/-/raw/$version/" . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . "/composer.json",
               'url' => "C:/laragon/www/Tiki/temps/$version.json",
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
    if ($currentVersion === 'accepted_state') {
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
    echo "   Branches:\n";

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

        echo "   🟥 Tiki {$tikiVersion}: {$constraint} ({$stabilityText})";

        if ($versionMitigated) {
            echo " [MITIGATED]";
        } elseif ($stability !== 'stable') {
            echo " [WARNING]";
        }

        echo "\n";

        if ($versionMitigated) {
            foreach (formatMitigationDetails($package, $tikiVersion, $ignoreMap) as $detailLine) {
                echo "{$detailLine}\n";
            }
        }
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
            echo "\n✅ No stability issues detected across all checked branches!\n";
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
 * Transform global package analysis data into categorized issue reports.
 *
 * This step prepares the reporting structures used by:
 * - detailed reports
 * - CI summaries
 * - current branch responsibility analysis
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
        $isIgnored = isPackageIgnoredInAcceptedState($package, $versionsToCheck, $ignoreMap);

        printPackageHistory($package, $data['history'], '⚠️', $ignoreMap, $isIgnored ? 'accepted_state' : 'current');

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
        $isIgnored = isPackageIgnoredInAcceptedState($package, $versionsToCheck, $ignoreMap);

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
        echo "   Branches:\n";

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

        echo "   🟥 Tiki {$tikiVersion}: {$constraint} ({$stabilityText})";

        if ($versionMitigated) {
            echo " [MITIGATED]";
        } elseif ($stability !== 'stable' || $isPartOfUnmitigatedDowngrade) {
            echo " [WARNING]";
        }

        echo "\n";

        if ($versionMitigated) {
            foreach (formatMitigationDetails($package, $tikiVersion, $ignoreMap) as $detailLine) {
                echo "{$detailLine}\n";
            }
        }
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
 * Split existing accepted-state issues into stability issues and version downgrades.
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
 * Summary
 */
function renderExecutiveSummary($acceptedStateAnalysis, $hasWarnings, $packageHistory, $ignoreMap, $unmitigatedCount = 0)
{
    if ($acceptedStateAnalysis['shouldFail']) {
        echo "\n## 🚨 Current Branch Introduces New Stability Issues\n\n";

        if (! empty($acceptedStateAnalysis['newIssues'])) {
            echo "### Issues Introduced by Current Branch:\n";
            foreach ($acceptedStateAnalysis['newIssues'] as $issue) {
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

    if (! empty($acceptedStateAnalysis['existingIssues'])) {
        if ($hasWarnings) {
            echo "\n## ⚠️ Stability Issues Detected in Accepted State\n\n";
            echo "**Reason**: Accepted state contains packages with [WARNING] tags (not mitigated).\n\n";
        } else {
            echo "\n## ⚠️ Stability Issues Detected (All Mitigated)\n\n";
            echo "**Status**: All existing accepted-state issues are documented and mitigated.\n\n";
        }

        [$stabilityIssues, $versionDowngrades] = splitExistingIssuesByType($acceptedStateAnalysis['existingIssues']);

        if (! empty($stabilityIssues)) {
            echo "### Current Accepted-State Stability Warnings:\n";
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
            echo "\n";
            echo "\n### Current Accepted-State Historical Stable Version Downgrades:\n";
            foreach ($versionDowngrades as $issue) {
                echo "- **{$issue['package']}**: Version downgrade detected";

                if (
                    isset($issue['from_tiki'], $issue['to_tiki'], $issue['from_version'], $issue['to_version'])
                ) {
                    echo " in `{$issue['to_tiki']}` ";
                    echo "(from `{$issue['from_version']}` in `{$issue['from_tiki']}` to `{$issue['to_version']}` in `{$issue['to_tiki']}`)";
                }

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
            if (! $acceptedStateAnalysis['shouldFail']) {
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
        echo "\n✅ No stability issues detected across all checked branches!\n";
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
 * - cross-branch comparisons are still performed to detect regressions
 *   relative to other branches (including master)
 *
 * The complete dependency history is still analyzed globally.
 * Cross-branch analysis is global, but pipeline failure responsibility
 * is restricted to packages modified in the current branch.
 *
 * Determine whether the current branch introduces issues relative
 * to already accepted branch states.
 * To determine whether it introduces new issues or worsens existing accepted-state issues.
 */
function analyzeCurrentBranchAgainstAcceptedState($packageHistory, $versionsToCheck, $ignoreMap = [])
{
    global $isCiMode, $globalComposerData;
    $currentBranch = getCheckedOutBranchName();

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

    $touchedPackages = getTouchedPackages($globalComposerData, $currentBranch);

    // Only packages modified in the current branch can make the pipeline fail.
    // Cross-branch analysis is still performed globally, but CI responsibility
    // is assigned only to touched packages.
    $filteredIssues = [];

    foreach ($currentBranchIssues as $issue) {
        $pkg = $issue['package'];
        if (isset($touchedPackages[$pkg])) {
            $filteredIssues[] = $issue;
        }
    }

    $shouldFail = ! empty($filteredIssues);

    if ($isCiMode) {
        echo "Issues detected on current branch: "
            . count($currentBranchIssues)
            . formatPackageListForSummary($currentBranchIssues) . "\n";
        echo "Touched packages: " . count($touchedPackages) . "\n";
        echo "Issues on touched packages (CI responsibility): "
            . count($filteredIssues)
            . formatPackageListForSummary($filteredIssues) . "\n";
        echo "Existing accepted-state issues: " . count($existingIssues) . "\n";
    }

    return [
        'shouldFail' => $shouldFail,
        'newIssues' => $filteredIssues,
        'existingIssues' => $existingIssues
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

    $stableVersionNames = array_keys($stableVersions);
    $acceptedStateVersions = array_filter($metadata['versions'], function ($version) use ($stableVersionNames) {
        return in_array($version['tiki_version'], $stableVersionNames);
    });

    $acceptedStateVersions = sortVersionsByTikiOrder($acceptedStateVersions, $stableVersionNames);

    $hasAcceptedStateRegression = false;
    for ($i = 1; $i < count($acceptedStateVersions); $i++) {
        $prev = $acceptedStateVersions[$i - 1];
        $current = $acceptedStateVersions[$i];

        $prevStability = extractStabilityFromVersion($prev['constraint']);
        $currentStability = extractStabilityFromVersion($current['constraint']);

        if (getStabilityNumeric($currentStability) < getStabilityNumeric($prevStability)) {
            $hasAcceptedStateRegression = true;
            break; // Only need to know if there's a regression, not count each one
        }
    }

    if (! $hasAcceptedStateRegression) {
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

        if (isset($issue['has_downgrade']) && $issue['has_downgrade']) {
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
 * Check if there are any unmitigated warnings in existing issues
 * This includes both stability issues (unstable versions not mitigated) and
 * unmitigated version downgrades.
 *
 * @param array $existingIssues Array of existing issues from accepted-state analysis
 * @param array $packageHistory Complete package history across all versions
 * @param array $ignoreMap Map of ignored packages per version
 * @return bool True if there are any unmitigated warnings, false otherwise
 */
function hasUnmitigatedWarnings($existingIssues, $packageHistory, $ignoreMap)
{
    return countUnmitigatedAcceptedStateIssues($existingIssues, $packageHistory, $ignoreMap) > 0;
}

// Main execution
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
    $acceptedStateAnalysis = analyzeCurrentBranchAgainstAcceptedState($packageHistory, $versionsToCheck, $ignoreMap);

    $unmitigatedCount = 0;
    if (! empty($acceptedStateAnalysis['existingIssues'])) {
        $unmitigatedCount = countUnmitigatedAcceptedStateIssues(
            $acceptedStateAnalysis['existingIssues'],
            $packageHistory,
            $ignoreMap
        );
    }
    $hasWarnings = $unmitigatedCount > 0;

    renderExecutiveSummary($acceptedStateAnalysis, $hasWarnings, $packageHistory, $ignoreMap, $unmitigatedCount);
    echo "\n\n## Detailed Package Report\n";
    renderDetailedReport($packageHistory, $versionsToCheck, $ignoreMap, true);

    $junitIssues = buildJUnitReportData($acceptedStateAnalysis['existingIssues']);
    $junitReport = generateJUnitReport($junitIssues, []);
    file_put_contents('composer-stability-report.xml', $junitReport);

    $exitCode = ($acceptedStateAnalysis['shouldFail'] || $hasWarnings) ? 1 : 0;

    exit($exitCode);
} else {
    renderNonCiExecutiveSummary($packageHistory, $versionsToCheck);
    echo "\n## Detailed Package Report\n";
    renderDetailedReport($packageHistory, $versionsToCheck, $ignoreMap, true);
    exit(empty($globalIssuesByPackage) && empty($globalStableRegressions) ? 0 : 1);
}
