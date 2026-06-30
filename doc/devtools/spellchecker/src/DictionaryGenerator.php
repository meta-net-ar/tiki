<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

final class DictionaryGenerator
{
    private string $root;

    private Config $config;

    private PhpParser $phpParser;
    private JsParser $jsParser;
    private VueParser $vueParser;

    private array $dictionaries = [];

    public function __construct(string $root, Config $config)
    {
        $this->root = rtrim($root, '/\\');
        $this->config = $config;

        $this->phpParser = new PhpParser();
        $this->jsParser  = new JsParser();
        $this->vueParser = new VueParser();

        $this->dictionaries = [
            'php-functions'  => [],
            'php-classes'    => [],
            'php-constants'  => [],
            'php-variables'  => [],
            'js-identifiers' => [],
            'vue-identifiers'=> [],
            'filenames'      => [],
            'project'        => [],
        ];
    }

    public function generate(): void
    {
        $finder = new Finder();

        $finder
            ->files()
            ->in($this->root)
            ->name(['*.php', '*.js', '*.vue']);

        $this->applyCSpellIgnores($finder);

        foreach ($finder as $file) {
            $this->processFile($file->getRealPath());
        }

        $this->writeDictionaries();
    }

    private function applyCSpellIgnores(Finder $finder): void
    {
        foreach ($this->config->getIgnorePaths() as $pattern) {

            // directory/**
            if (preg_match('#^([^*?]+)/\*\*$#', $pattern, $m)) {
                $finder->exclude($m[1]);
                continue;
            }

            // **/*.ext
            if (preg_match('#^\*\*/\*\.([^.]+)$#', $pattern, $m)) {
                $finder->notName('*.' . $m[1]);
                continue;
            }

            // **/*.min.js
            if (str_starts_with($pattern, '**/*.')) {
                $finder->notName(substr($pattern, 4));
                continue;
            }

            // exact filename
            if (!str_contains($pattern, '*')) {
                $finder->notName($pattern);
            }
        }
    }

    private function processFile(string $file): void
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $base = pathinfo($file, PATHINFO_FILENAME);

        $this->add('filenames', $base);

        switch ($ext) {

            case 'php':
                $this->processPhp($file);
                break;

            case 'js':
                $this->processJs($file);
                break;

            case 'vue':
                $this->processVue($file);
                break;
        }
    }

    private function processPhp(string $file): void
    {
        $result = $this->phpParser->parse($file);

        foreach ($result['classes'] ?? [] as $v) {
            $this->add('php-classes', $v);
            $this->add('project', $v);
        }

        foreach ($result['functions'] ?? [] as $v) {
            $this->add('php-functions', $v);
            $this->add('project', $v);
        }

        foreach ($result['constants'] ?? [] as $v) {
            $this->add('php-constants', $v);
            $this->add('project', $v);
        }

        foreach ($result['variables'] ?? [] as $v) {
            $this->add('php-variables', $v);
        }
    }

    private function processJs(string $file): void
    {
        foreach ($this->jsParser->parse($file) as $v) {
            $this->add('js-identifiers', $v);
            $this->add('project', $v);
        }
    }

    private function processVue(string $file): void
    {
        foreach ($this->vueParser->parse($file) as $v) {
            $this->add('vue-identifiers', $v);
            $this->add('project', $v);
        }
    }

    private function add(string $dict, string $word): void
    {
        if ($word === '') {
            return;
        }

        if ($this->config->ignoredByRegex($word)) {
            return;
        }

        $this->dictionaries[$dict][$word] = true;
    }

    private function writeDictionaries(): void
    {
        $outDir = $this->root . '/doc/devtools/spellchecker/generated_dictionaries';

        if (!is_dir($outDir)) {
            mkdir($outDir, 0777, true);
        }

        foreach ($this->dictionaries as $name => $words) {
            $list = array_keys($words);

            natcasesort($list);

            file_put_contents(
                $outDir . '/' . $name . '.txt',
                implode(PHP_EOL, $list) . PHP_EOL
            );
        }
    }
}
