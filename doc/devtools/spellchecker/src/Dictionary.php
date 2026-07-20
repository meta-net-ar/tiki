<?php

declare(strict_types=1);

namespace TikiDevTools\Spellchecker;

final class Dictionary
{
    /**
     * @var array<string, true>
     */
    private array $words = [];

    public function add(string $word): void
    {
        $word = trim($word);

        if ($word === '') {
            return;
        }

        // Remove surrounding punctuation
        $word = trim($word, "\"'` \t\r\n(){}[]<>.,;:");

        if ($word === '') {
            return;
        }

        // Ignore very short identifiers
        if (strlen($word) < 3) {
            return;
        }

        $this->words[$word] = true;
    }

    public function addMany(array $words): void
    {
        foreach ($words as $word) {
            $this->add($word);
        }
    }

    public function has(string $word): bool
    {
        return isset($this->words[$word]);
    }

    public function count(): int
    {
        return count($this->words);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        $words = array_keys($this->words);

        natcasesort($words);

        return array_values($words);
    }

    public function save(string $filename): void
    {
        $dir = dirname($filename);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents(
            $filename,
            implode(PHP_EOL, $this->all()) . PHP_EOL
        );
    }

    public function merge(Dictionary $dictionary): void
    {
        foreach ($dictionary->all() as $word) {
            $this->words[$word] = true;
        }
    }
}
