<?php

declare(strict_types=1);

namespace TikiDevTools\Spellchecker;

final class Config
{
    private array $config;

    public function __construct(string $file)
    {
        if (! is_file($file)) {
            throw new \RuntimeException("Cannot find $file");
        }

        $this->config = json_decode(
            file_get_contents($file),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function getIgnorePaths(): array
    {
        return $this->config['ignorePaths'] ?? [];
    }

    public function getIgnoreRegex(): array
    {
        return $this->config['ignoreRegExpList'] ?? [];
    }

    public function ignoredByRegex(string $word): bool
    {
        foreach ($this->getIgnoreRegex() as $regex) {
            if ($regex[0] !== '/') {
                continue;
            }

            $ok = @preg_match($regex, $word);

            if ($ok === 1) {
                return true;
            }
        }

        return false;
    }
}
