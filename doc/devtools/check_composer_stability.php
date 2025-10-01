<?php

/**
 * Enhanced Composer Package Stability Checker for Tiki
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
 *
 * Available versions: 24.x, 27.x, 28.x, master
 *
 * Output:
 * - Lists packages with unstable versions
 * - Shows actual version downgrades with specific version comparisons
 * - Provides a summary of all detected issues
 *
 * Exit codes:
 *   0 - No stability issues detected
 *   1 - Stability issues found
 */

require_once __DIR__ . '/../../vendor_bundled/vendor/autoload.php';

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
 * Get the current running Tiki version
 */
function getCurrentTikiVersion()
{
    $tikiPath = __DIR__ . '/../..';
    $versionFile = $tikiPath . '/lib/setup/twversion.class.php';
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
    return ['24.x', '27.x', '28.x', 'master'];
}

/**
 * Get versions to check based on command line arguments
 */
function getVersionsToCheck()
{
    global $argv;
    $args = array_slice($argv, 1);

    if (empty($args)) {
        return getAvailableTikiVersions();
    }

    $availableVersions = getAvailableTikiVersions();
    $versionsToCheck = [];

    foreach ($args as $arg) {
        if (in_array($arg, $availableVersions)) {
            $versionsToCheck[] = $arg;
        } else {
            echo "Warning: Ignoring invalid version '$arg'. Available versions are: " . implode(', ', $availableVersions) . "\n";
        }
    }

    if (empty($versionsToCheck)) {
        echo "Error: No valid versions specified. Using all available versions.\n";
        return getAvailableTikiVersions();
    }

    // Sort versions chronologically
    usort($versionsToCheck, function ($a, $b) {
        $order = array_flip(getAvailableTikiVersions());
        return $order[$a] <=> $order[$b];
    });

    return $versionsToCheck;
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
 */
function buildPackageHistory($versionsToCheck)
{
    $packageHistory = [];

    foreach ($versionsToCheck as $version) {
        $composer = getComposerJson([
            'url' => "https://gitlab.com/tikiwiki/tiki/-/raw/$version/vendor_bundled/composer.json",
            'type' => 'remote'
        ]);

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

    // Analyze each package's complete history
    foreach ($packageHistory as $package => &$history) {
        $versions = $history['_metadata']['versions'];

        // Sort versions chronologically
        usort($versions, function ($a, $b) {
            $order = array_flip(getAvailableTikiVersions());
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
 * Generate enhanced report with complete version history
 */
function generateReport($packageHistory)
{
    $currentVersion = getCurrentTikiVersion();
    echo "\nCurrent Tiki Version: {$currentVersion}\n";
    echo "\nPackage Stability Analysis:\n==================\n";

    $hasIssues = false;
    $issuesByPackage = [];
    $stableRegressions = [];

    foreach ($packageHistory as $package => $history) {
        $metadata = $history['_metadata'];
        unset($history['_metadata']);

        // Track packages with stable regressions separately
        if ($metadata['has_stable_regressions']) {
            $versions = $metadata['versions'];
            usort($versions, function ($a, $b) {
                $order = array_flip(getAvailableTikiVersions());
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
                usort($versions, function ($a, $b) {
                    $order = array_flip(getAvailableTikiVersions());
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
}

// Main execution
$currentVersion = getCurrentTikiVersion();
if (! $currentVersion) {
    echo "Error: Could not determine current Tiki version.\n";
    exit(1);
}

$versionsToCheck = getVersionsToCheck();
echo "Comparing Tiki versions: " . implode(', ', $versionsToCheck) . "\n";
$packageHistory = buildPackageHistory($versionsToCheck);
generateReport($packageHistory);

exit(empty($issuesByPackage) && empty($stableRegressions) ? 0 : 1);
