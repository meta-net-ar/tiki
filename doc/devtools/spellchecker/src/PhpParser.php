<?php

declare(strict_types=1);

final class PhpParser
{
    /**
     * Parse a PHP file and return identifiers grouped by type.
     *
     * @return array<string, string[]>
     */
    public function parse(string $file): array
    {
        $code = file_get_contents($file);

        if ($code === false) {
            return [];
        }

        $tokens = token_get_all($code);

        $result = [
            'classes' => [],
            'interfaces' => [],
            'traits' => [],
            'enums' => [],
            'functions' => [],
            'constants' => [],
            'variables' => [],
            'namespaces' => [],
            'uses' => [],
        ];

        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {

            $token = $tokens[$i];

            if (!is_array($token)) {
                continue;
            }

            switch ($token[0]) {

                case T_CLASS:
                    $this->collectNextString($tokens, $i, $result['classes']);
                    break;

                case T_INTERFACE:
                    $this->collectNextString($tokens, $i, $result['interfaces']);
                    break;

                case T_TRAIT:
                    $this->collectNextString($tokens, $i, $result['traits']);
                    break;

                case T_ENUM:
                    $this->collectNextString($tokens, $i, $result['enums']);
                    break;

                case T_FUNCTION:
                    $this->collectFunction($tokens, $i, $result['functions']);
                    break;

                case T_CONST:
                    $this->collectConstant($tokens, $i, $result['constants']);
                    break;

                case T_NAMESPACE:
                    $this->collectNamespace($tokens, $i, $result['namespaces']);
                    break;

                case T_USE:
                    $this->collectNamespace($tokens, $i, $result['uses']);
                    break;

                case T_VARIABLE:
                    $result['variables'][] = substr($token[1], 1);
                    break;
            }
        }

        foreach ($result as &$list) {
            $list = array_values(array_unique($list));
            natcasesort($list);
            $list = array_values($list);
        }

        return $result;
    }

    private function collectNextString(array $tokens, int &$i, array &$output): void
    {
        $count = count($tokens);

        for ($j = $i + 1; $j < $count; $j++) {

            $token = $tokens[$j];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_STRING) {
                $output[] = $token[1];
                return;
            }
        }
    }

    private function collectFunction(array $tokens, int &$i, array &$output): void
    {
        $count = count($tokens);

        for ($j = $i + 1; $j < $count; $j++) {

            $token = $tokens[$j];

            if (!is_array($token)) {
                continue;
            }

            if (
                in_array(
                    $token[0],
                    [
                        T_WHITESPACE,
                        T_AMPERSAND_FOLLOWED_BY_VAR_OR_VARARG,
                        T_AMPERSAND_NOT_FOLLOWED_BY_VAR_OR_VARARG,
                    ],
                    true
                )
            ) {
                continue;
            }

            if ($token[0] === T_STRING) {
                $output[] = $token[1];
            }

            return;
        }
    }

    private function collectConstant(array $tokens, int &$i, array &$output): void
    {
        $count = count($tokens);

        for ($j = $i + 1; $j < $count; $j++) {

            $token = $tokens[$j];

            if (!is_array($token)) {
                continue;
            }

            if ($token[0] === T_STRING) {
                $output[] = $token[1];
                return;
            }

            if ($token[0] !== T_WHITESPACE) {
                return;
            }
        }
    }

    private function collectNamespace(array $tokens, int &$i, array &$output): void
    {
        $count = count($tokens);

        $name = '';

        for ($j = $i + 1; $j < $count; $j++) {

            $token = $tokens[$j];

            if (is_string($token)) {

                if ($token === ';' || $token === '{') {
                    break;
                }

                continue;
            }

            if (
                $token[0] === T_STRING
                || (
                    defined('T_NAME_QUALIFIED')
                    && $token[0] === T_NAME_QUALIFIED
                )
                || (
                    defined('T_NAME_FULLY_QUALIFIED')
                    && $token[0] === T_NAME_FULLY_QUALIFIED
                )
            ) {
                $name .= $token[1];
            } elseif ($token[0] === T_NS_SEPARATOR) {
                $name .= '\\';
            }
        }

        if ($name !== '') {
            $output[] = $name;

            foreach (explode('\\', $name) as $part) {
                if ($part !== '') {
                    $output[] = $part;
                }
            }
        }
    }
}
