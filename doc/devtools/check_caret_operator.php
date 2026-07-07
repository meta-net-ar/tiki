#!/usr/bin/env php
<?php

require __DIR__ . '/../../path_constants.php';

echo "Checking for ^ operators in composer.json require and require-dev..." . PHP_EOL;
echo "--------------------------------------------------------------------" . PHP_EOL;

$data = json_decode(file_get_contents(PRIMARY_COMPOSERJSON_FILE_PATH), true);

$matches = [];

foreach (['require', 'require-dev'] as $section) {
    foreach ($data[$section] as $package => $constraint) {
        if (is_string($constraint) && str_starts_with(trim($constraint), '^')) {
            $matches[$package] = $constraint;
        }
    }
}

if (! empty($matches)) {
    echo "❌ Error: '^' operator found in composer.json. Use tilde (~) instead on these dependencies:" . PHP_EOL;
    foreach ($matches as $package => $constraint) {
        echo "  $package: $constraint" . PHP_EOL;
    }
    exit(1);
}

echo "✅ Check passed: No ^ operator found in composer.json." . PHP_EOL;
exit(0);
