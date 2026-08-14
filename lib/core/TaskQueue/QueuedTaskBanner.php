<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
namespace Tiki\TaskQueue;

class QueuedTaskBanner
{
    /**
     * Add a note banner.
     *
     * @param array $banner
     */
    public static function note(array $banner)
    {
        self::add($banner);
    }

    /**
     * Add a banner to the session.
     *
     * @param array $banner
     */
    public static function add(array $banner)
    {
        if (! empty($banner['id'])) {
            $_SESSION['tiki_queued_tasks_banner'][$banner['id']] = $banner;
        }
    }

    /**
     * Update an existing banner in the session.
     *
     * @param int|string $id
     * @param array $data
     */
    public static function update($id, array $data)
    {
        if (isset($_SESSION['tiki_queued_tasks_banner'][$id])) {
            $_SESSION['tiki_queued_tasks_banner'][$id] = array_merge(
                $_SESSION['tiki_queued_tasks_banner'][$id],
                $data
            );
        }
    }

    /**
     * Check if a banner exists by its ID.
     *
     * @param int|string $id
     * @return bool
     */
    public static function exists($id): bool
    {
        return isset($_SESSION['tiki_queued_tasks_banner'][$id]);
    }

    /**
     * Clear one or all banners.
     *
     * @param int|string|null $key
     */
    public static function clear($key = null)
    {
        if ($key) {
            unset($_SESSION['tiki_queued_tasks_banner'][$key]);
        } else {
            $_SESSION['tiki_queued_tasks_banner'] = [];
        }
    }

    /**
     * Get all or filtered banners.
     *
     * @param callable|null $filterCallback
     * @return array
     */
    public static function get(?callable $filterCallback = null): array
    {
        $queueManager = new QueueManager();
        $count = $queueManager->getCountOfQueuedTasks();

        if ($count <= 0) {
            $_SESSION['tiki_queued_tasks_banner'] = [];
        }

        $banners = $_SESSION['tiki_queued_tasks_banner'] ?? [];
        if ($filterCallback) {
            $banners = array_filter($banners, $filterCallback);
        }

        return $banners;
    }

    /**
     * Filter banners based on specific criteria.
     *
     * @param array $criteria
     * @return array
     */
    public static function filter(array $criteria): array
    {
        return self::get(function ($banner) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if (! isset($banner[$key]) || $banner[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }
}
