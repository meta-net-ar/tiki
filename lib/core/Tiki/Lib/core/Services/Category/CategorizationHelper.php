<?php

namespace Tiki\Lib\core\Services\Category;

final class CategorizationHelper
{
    public static function processedCount(array $result): int
    {
        return isset($result['objects'])
            ? count($result['objects'])
            : 0;
    }

    public static function successMessage(
        string $categoryName,
        int $count,
        string $action
    ): string {
        if ($action === 'categorize') {
            return $count === 1
                ? tr('%0: One object added to category', $categoryName)
                : tr('%0: %1 objects added to category', $categoryName, $count);
        }

        return $count === 1
            ? tr('%0: One object removed from category', $categoryName)
            : tr('%0: %1 objects removed from category', $categoryName, $count);
    }

    public static function emptyResultMessage(
        string $categoryName,
        string $action
    ): string {
        return $action === 'categorize'
            ? tr('%0: No objects added to category', $categoryName)
            : tr('%0: No objects removed from category', $categoryName);
    }

    public static function unchangedMessage(
        string $categoryName,
        int $count,
        string $action
    ): string {
        if ($action === 'categorize') {
            return $count === 1
                ? tr('%0: No change made for one object already in the category', $categoryName)
                : tr('%0: No change made for %1 objects already in the category', $categoryName, $count);
        }

        return $count === 1
            ? tr('%0: No change made for one object not in the category', $categoryName)
            : tr('%0: No change made for %1 objects not in the category', $categoryName, $count);
    }
}
