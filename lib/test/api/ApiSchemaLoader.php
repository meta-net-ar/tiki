<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api;

use Symfony\Component\Yaml\Yaml;

/**
 * Converts OpenAPI YAML schema files into the associative-array format
 * understood by ApiTestCase::assertMatchesSchema().
 */
class ApiSchemaLoader
{
    private static string $schemasDir = __DIR__ . '/../../../templates/api/docs/schemas/';

    /** @var array<string, bool> Tracks files currently being resolved (circular ref guard) */
    private static array $loading = [];

    /** @var array<string, array> Memoised results keyed by filename */
    private static array $cache = [];

    /**
     * Load a schema YAML file and return a schema array for assertMatchesSchema().
     *
     * @param string $filename e.g. 'Comment.yaml'
     */
    public static function fromSchemaFile(string $filename): array
    {
        if (isset(self::$cache[$filename])) {
            return self::$cache[$filename];
        }

        $yaml   = Yaml::parseFile(self::$schemasDir . $filename);
        $result = self::convertDefinition($yaml);
        $schema = is_array($result) ? $result : [];

        self::$cache[$filename] = $schema;
        return $schema;
    }

    /**
     * Convert a single OpenAPI definition node to assertMatchesSchema format.
     * Returns either a type string ('string|null', 'int|string', …)
     * or an associative schema array (nested object)
     * or a single-element numeric array (array of objects, triggers per-item validation).
     *
     * @return string|array<string, mixed>
     */
    private static function convertDefinition(array $def): mixed
    {
        if (isset($def['$ref'])) {
            $resolved = self::resolveRef($def['$ref']);
            if (! empty($def['nullable']) && is_string($resolved) && ! str_contains($resolved, 'null')) {
                $resolved .= '|null';
            }
            return $resolved;
        }

        // allOf: merge all object schemas into one flat associative schema.
        if (isset($def['allOf'])) {
            $merged = [];
            foreach ($def['allOf'] as $subDef) {
                $converted = self::convertDefinition($subDef);
                if (is_array($converted) && ! isset($converted[0]) && ! isset($converted['*'])) {
                    $merged = array_merge($merged, $converted);
                }
            }
            return $merged ?: [];
        }

        // oneOf: join all converted type strings, deduplicating, then append nullable.
        if (isset($def['oneOf'])) {
            $parts    = [];
            $nullable = ! empty($def['nullable']);
            foreach ($def['oneOf'] as $option) {
                $converted = self::convertDefinition($option);
                if (is_string($converted)) {
                    foreach (explode('|', $converted) as $t) {
                        if (! in_array($t, $parts, true)) {
                            $parts[] = $t;
                        }
                    }
                }
            }
            if ($nullable && ! in_array('null', $parts, true)) {
                $parts[] = 'null';
            }
            return implode('|', $parts) ?: 'string';
        }

        $type     = $def['type'] ?? 'object';
        $nullable = ! empty($def['nullable']);

        if ($type === 'object') {
            if (isset($def['additionalProperties'])) {
                return ['*' => self::convertDefinition($def['additionalProperties'])];
            }
            if (isset($def['properties'])) {
                $required = $def['required'] ?? null;
                return self::convertProperties($def['properties'], $required);
            }
            return $nullable ? 'array|null' : 'array';
        }

        if ($type === 'array') {
            if (isset($def['items'])) {
                $itemSchema = self::convertDefinition($def['items']);
                if (is_array($itemSchema)) {
                    return [$itemSchema];
                }
            }
            return 'array';
        }

        $typeStr = self::mapScalarType($type);
        if ($nullable) {
            $typeStr .= '|null';
        }
        return $typeStr;
    }

    /**
     * Convert an OpenAPI properties map to an assertMatchesSchema associative array
     * When $required is provided, only properties listed there are included.
     */
    private static function convertProperties(array $properties, ?array $required = null): array
    {
        $schema = [];
        foreach ($properties as $key => $def) {
            if ($required !== null && ! in_array($key, $required, true)) {
                continue;
            }
            $schema[$key] = self::convertDefinition($def);
        }
        return $schema;
    }

    /**
     * Resolve a $ref value (e.g. 'schemas-Comment.yaml') by loading and
     * converting that schema file.  Returns 'array' on circular or missing refs.
     */
    private static function resolveRef(string $ref): mixed
    {
        $filename = str_replace('schemas-', '', $ref);

        if (isset(self::$loading[$filename])) {
            return 'array';
        }

        $path = self::$schemasDir . $filename;
        if (! file_exists($path)) {
            return 'array';
        }

        self::$loading[$filename] = true;
        $yaml   = Yaml::parseFile($path);
        $result = self::convertDefinition($yaml);
        unset(self::$loading[$filename]);

        return $result;
    }

    /** Map an OpenAPI scalar type name to the assertMatchesSchema type string. */
    private static function mapScalarType(string $type): string
    {
        return match ($type) {
            'integer' => 'int',
            'number'  => 'numeric',
            'boolean' => 'bool',
            default   => 'string',
        };
    }
}
