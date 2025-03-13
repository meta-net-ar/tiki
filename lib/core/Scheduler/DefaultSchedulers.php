<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\core\Scheduler;

use TikiDb;
use TikiLib;

class DefaultSchedulers
{
    private int $currentVersion;
    private $scheduler;
    private $tikilib;

    public function __construct()
    {
        $this->tikilib = TikiLib::lib('tiki');
        $this->currentVersion = (int) $this->tikilib->get_preference('scheduler_default_task_version');
        $this->scheduler = TikiDb::get()->table('tiki_scheduler');
    }

    /**
     * Get default tasks.
     * @param int|null $version The version number to get tasks for.
     * @return array An array of default tasks.
     */
    public function getDefaultTasks($version = null): array
    {
        $list = [
            1 => [[
                'name' => tr('Send emails queued every minute'),
                'description' => tr('Send all queued emails every minute.'),
                'task' => 'ConsoleCommandTask',
                'params' => json_encode(['console_command' => 'mail-queue:send']),
                'run_time' => '* * * * *'
            ],[
                'name' => tr('Generate sitemap daily'),
                'description' => tr('Generate the sitemap daily at 02:00.'),
                'task' => 'ConsoleCommandTask',
                'params' => json_encode(['console_command' => 'sitemap:generate']),
                'run_time' => '0 2 * * *'
            ],[
                'name' => tr('Rebuild preferences index every Sunday'),
                'description' => tr('Rebuild the preferences index every Sunday at 02:30.'),
                'task' => 'ConsoleCommandTask',
                'params' => json_encode(['console_command' => 'preferences:rebuild-index']),
                'run_time' => '30 2 * * 0'
            ],[
                'name' => tr('Rebuild index every Sunday'),
                'description' => tr('Rebuild the site index every Sunday at 02:45.'),
                'task' => 'ConsoleCommandTask',
                'params' => json_encode(['console_command' => 'index:rebuild']),
                'run_time' => '45 2 * * 0'
            ],[
                'name' => tr('Clear tokens every Sunday'),
                'description' => tr('Clear expired tokens every Sunday at 03:00.'),
                'task' => 'ConsoleCommandTask',
                'params' => json_encode(['console_command' => 'tokens:clear']),
                'run_time' => '0 3 * * 0'
            ]],
        ];

        return $version !== null ? ($list[$version] ?? []) : $list;
    }

    /**
     * Get the latest version of the default tasks.
     * @return int The latest version number.
     */
    public function getLatestVersion(): int
    {
        $defaultTasks = $this->getDefaultTasks();
        return max(array_keys($defaultTasks));
    }

    /**
     * Get the current version of the scheduler.
     * @return int The current version number.
     */
    public function getCurrentVersion(): int
    {
        return $this->currentVersion;
    }

    /**
     * Update the version in the preferences.
     * @param int $version The version number to update.
     */
    private function updateVersion(int $version): void
    {
        $this->tikilib->set_preference('scheduler_default_task_version', $version);
    }

    /**
     * Check if any task exists.
     * @return bool True if any task exists, false otherwise.
     */
    private function anyTaskExists(): bool
    {
        $existingTaskCount = $this->scheduler->fetchCount([]);
        return $existingTaskCount ? true : false;
    }

    /**
     * Check and update the scheduler to the latest version.
     */
    public function checkAndUpdate(): void
    {
        $current = $this->getCurrentVersion();
        $target = $this->getLatestVersion();
        $enableTasks = ! $this->anyTaskExists();
        for ($v = $current + 1; $v <= $target; $v++) {
            $this->runMigration($v, $enableTasks);
        }
    }

    /**
     * Load and set up default tasks based on the current version.
     * If a task does not exist, it is created as active.
     * If a task exists, it is created as inactive.
     * The version is updated after processing the tasks.
     * @param int $version The version number to migrate.
     */
    public function runMigration(int $version, bool $enableTasks): void
    {
        $getDefaultTasks = $this->getDefaultTasks($version);
        if (empty($getDefaultTasks)) {
            return;
        }

        foreach ($getDefaultTasks as $task) {
            $this->scheduler->insert([
                'name' => $task['name'],
                'description' => $task['description'],
                'task' => $task['task'],
                'params' => $task['params'],
                'run_time' => $task['run_time'],
                'status' => $enableTasks ? 'active' : 'inactive',
                'creation_date' => time()
            ]);
        }

        $this->updateVersion($version);
    }
}
