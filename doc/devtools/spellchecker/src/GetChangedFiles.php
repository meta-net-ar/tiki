<?php

declare(strict_types=1);

namespace TikiDevTools\Spellchecker;

use Symfony\Component\Finder\Finder;

final class ChangedFiles
{
    public static function all(
        string $root,
        callable $ignoreCallback
    ): array {
        $finder = new Finder();

        $finder
            ->files()
            ->in($root)
            ->name(['*.php', '*.js', '*.vue']);

        $ignoreCallback($finder);

        $files = [];

        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        sort($files);

        return $files;
    }

    public static function changed(string $root): array
    {
        return self::fromGit(
            $root,
            'git -C %s diff --name-only HEAD'
        );
    }

    public static function mergeRequest(
        string $root,
        string $base
    ): array {
        return self::fromGit(
            $root,
            'git -C %s diff --name-only %s...HEAD',
            $base
        );
    }

    private static function fromGit(
        string $root,
        string $command,
        ?string $base = null
    ): array {

        if ($base === null) {
            $command = sprintf(
                $command,
                escapeshellarg($root)
            );
        } else {
            $command = sprintf(
                $command,
                escapeshellarg($root),
                escapeshellarg($base)
            );
        }

        exec($command, $output);

        $files = [];

        foreach ($output as $file) {
            if (! preg_match('/\.(php|js|vue)$/', $file)) {
                continue;
            }

            $full = $root . DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, $file);

            if (is_file($full)) {
                $files[] = realpath($full);
            }
        }

        sort($files);

        return $files;
    }
}
