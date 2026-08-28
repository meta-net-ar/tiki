<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

require_once __DIR__ . '/../../../../path_constants.php';

use Composer\Script\Event;
use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * Scans PSR-4 Smarty extension classes and generates a static mapping file
 * used by SmartyTikiExtension to discover handlers at runtime.
 */
class SmartyExtensionMapper
{
    public const SMARTY_MAP_FILE = __DIR__ . '/../../../../' . \SMARTY_EXTENSION_MAP_PATH;

    private const MAP_KEYS = [
        'function_handlers',
        'block_handlers',
        'modifiers',
        'tag_compilers',
        'modifier_compilers',
        'pre_filters',
        'output_filters',
    ];

    /**
     * Composer hook entry point.
     */
    public static function generate(Event $event): void
    {
        $io = $event->getIO();
        $tikiRoot = realpath(__DIR__ . '/../../../../');
        $outputPath = self::SMARTY_MAP_FILE;

        $map = self::generateMapping($tikiRoot, $outputPath);

        $total = 0;
        $parts = [];
        foreach ($map as $type => $entries) {
            $count = count($entries);
            $total += $count;
            if ($count > 0) {
                $parts[] = "$count $type";
            }
        }

        $io->write("<info>Smarty extension map generated: $total extensions (" . implode(', ', $parts) . ")</info>");
    }

    /**
     * Generate the mapping and write it to disk.
     *
     * @return array The generated map
     */
    public static function generateMapping(string $tikiRoot, string $outputPath): array
    {
        $map = array_fill_keys(self::MAP_KEYS, []);

        foreach (self::getScanDirectories($tikiRoot) as $dirInfo) {
            self::scanDirectory($dirInfo['path'], $dirInfo['namespace'], $map);
        }

        foreach (self::MAP_KEYS as $key) {
            ksort($map[$key]);
        }

        self::writeMapFile($outputPath, $map);

        return $map;
    }

    /**
     * Check if the current mapping file is up-to-date.
     *
     * @return array List of differences (empty = up-to-date)
     */
    public static function checkMapping(string $tikiRoot, string $mapPath): array
    {
        $currentMap = [];
        if (file_exists($mapPath)) {
            $currentMap = require $mapPath;
            if (! is_array($currentMap)) {
                $currentMap = [];
            }
        }

        $freshMap = array_fill_keys(self::MAP_KEYS, []);
        foreach (self::getScanDirectories($tikiRoot) as $dirInfo) {
            self::scanDirectory($dirInfo['path'], $dirInfo['namespace'], $freshMap);
        }

        $diffs = [];

        foreach ($freshMap as $type => $entries) {
            foreach ($entries as $name => $class) {
                if (! isset($currentMap[$type][$name])) {
                    $diffs[] = "Added: $type '$name' => $class";
                } elseif ($currentMap[$type][$name] !== $class) {
                    $diffs[] = "Changed: $type '$name' => $class (was {$currentMap[$type][$name]})";
                }
            }
        }

        foreach ($currentMap as $type => $entries) {
            if (! is_array($entries)) {
                continue;
            }
            foreach ($entries as $name => $class) {
                if (! isset($freshMap[$type][$name])) {
                    $diffs[] = "Removed: $type '$name' => $class";
                }
            }
        }

        return $diffs;
    }

    /**
     * Get the list of directories to scan for Smarty extensions.
     */
    private static function getScanDirectories(string $tikiRoot): array
    {
        $dirs = [
            ['path' => $tikiRoot . '/lib/smarty_tiki/FunctionHandler', 'namespace' => 'SmartyTiki\\FunctionHandler'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/BlockHandler', 'namespace' => 'SmartyTiki\\BlockHandler'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/Modifier', 'namespace' => 'SmartyTiki\\Modifier'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/Compile/Tag', 'namespace' => 'SmartyTiki\\Compile\\Tag'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/Compile/Modifier', 'namespace' => 'SmartyTiki\\Compile\\Modifier'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/Filter/Pre', 'namespace' => 'SmartyTiki\\Filter\\Pre'],
            ['path' => $tikiRoot . '/lib/smarty_tiki/Filter/Output', 'namespace' => 'SmartyTiki\\Filter\\Output'],
        ];

        // Support for custom Smarty extensions in _custom/shared/smarty, matching the
        // existing _custom/shared/{templates,wiki-plugins,js} conventions. PSR-4 autoloading
        // for the SmartyTikiCustom\ namespace is registered in lib/init/initlib.php.
        // See _custom_dist/README.md and _custom_dist/shared/smarty/ for the expected layout.
        $customSmartDir = $tikiRoot . '/_custom/shared/smarty';
        if (is_dir($customSmartDir)) {
            $subDirs = [
                'FunctionHandler' => 'SmartyTikiCustom\\FunctionHandler',
                'BlockHandler' => 'SmartyTikiCustom\\BlockHandler',
                'Modifier' => 'SmartyTikiCustom\\Modifier',
                'Compile/Tag' => 'SmartyTikiCustom\\Compile\\Tag',
                'Compile/Modifier' => 'SmartyTikiCustom\\Compile\\Modifier',
                'Filter/Pre' => 'SmartyTikiCustom\\Filter\\Pre',
                'Filter/Output' => 'SmartyTikiCustom\\Filter\\Output',
            ];
            foreach ($subDirs as $subDir => $namespace) {
                $path = $customSmartDir . '/' . $subDir;
                if (is_dir($path)) {
                    $dirs[] = ['path' => $path, 'namespace' => $namespace];
                }
            }
        }

        return array_filter($dirs, fn($d) => is_dir($d['path']));
    }

    /**
     * Scan a directory for classes implementing TikiSmartyExtensionInterface.
     */
    private static function scanDirectory(string $dirPath, string $namespace, array &$map): void
    {
        foreach (new \DirectoryIterator($dirPath) as $file) {
            if ($file->isDot() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $file->getBasename('.php');

            // Skip non-class files (e.g. index.php
            if ($className === 'index' || ! preg_match('/^[A-Z]/', $className)) {
                continue;
            }
            $fqcn = $namespace . '\\' . $className;

            if (! class_exists($fqcn)) {
                continue;
            }

            $reflection = new \ReflectionClass($fqcn);

            if (! $reflection->implementsInterface(TikiSmartyExtensionInterface::class)) {
                continue;
            }
            if ($reflection->isAbstract() || $reflection->isInterface()) {
                continue;
            }

            $smartyName = $fqcn::getSmartyName();
            $type = self::determineType($reflection, $namespace);

            if ($type !== null && ! isset($map[$type][$smartyName])) {
                $map[$type][$smartyName] = $fqcn;
            }
        }
    }

    /**
     * Determine the mapping type based on the class's interfaces and namespace.
     */
    private static function determineType(\ReflectionClass $ref, string $namespace): ?string
    {
        // FunctionHandler
        if ($ref->implementsInterface(\Smarty\FunctionHandler\FunctionHandlerInterface::class)) {
            return 'function_handlers';
        }

        // BlockHandler
        if ($ref->implementsInterface(\Smarty\BlockHandler\BlockHandlerInterface::class)) {
            return 'block_handlers';
        }

        // Compiler (tag vs modifier, distinguished by namespace)
        if ($ref->implementsInterface(\Smarty\Compile\CompilerInterface::class)) {
            if (str_contains($namespace, 'Compile\\Modifier')) {
                return 'modifier_compilers';
            }
            return 'tag_compilers';
        }

        // ModifierCompiler
        if (
            interface_exists(\Smarty\Compile\Modifier\ModifierCompilerInterface::class)
            && $ref->implementsInterface(\Smarty\Compile\Modifier\ModifierCompilerInterface::class)
        ) {
            return 'modifier_compilers';
        }

        // Filter
        if ($ref->implementsInterface(\Smarty\Filter\FilterInterface::class)) {
            if (str_contains($namespace, 'Filter\\Pre')) {
                return 'pre_filters';
            }
            if (str_contains($namespace, 'Filter\\Output')) {
                return 'output_filters';
            }
            return null;
        }

        // Tiki modifiers — plain classes with a handle() method; Smarty itself has no
        // registration interface for simple modifiers (unlike the handler/compiler/filter
        // types above), so we key off the exact scan namespace instead of an interface.
        // Interface membership (TikiSmartyExtensionInterface) was already verified by
        // scanDirectory() before this method is called.
        if ($namespace === 'SmartyTiki\\Modifier' || $namespace === 'SmartyTikiCustom\\Modifier') {
            return 'modifiers';
        }

        return null;
    }

    /**
     * Write the mapping array to a PHP file.
     */
    private static function writeMapFile(string $outputPath, array $map): void
    {
        $content = "<?php\n\n";
        $content .= "// Auto-generated by Tiki\\Composer\\SmartyExtensionMapper — do not edit.\n";
        $content .= "// Regenerate with: php console.php smarty:generate-mapping\n\n";
        $content .= "return " . var_export($map, true) . ";\n";

        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($outputPath, $content);
    }
}
