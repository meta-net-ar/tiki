<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue;

class QueuedTaskSettings
{
    public const IN_PROGRESS = 'InProgress';
    public const COMPLETED = 'Completed';
    public const PENDING = 'Pending';
    public const FAILED = 'Failed';

    private static $statuses = null;

    private static $jobTypePageMapping = [
        'CreateInstance' => 'manager_create',
        'RebuildIndex' => 'index_rebuild',
        'PdfGeneration' => 'pdf_generation',
    ];

    /**
     * Get the statuses with messages.
     *
     * @return array
     */
    public static function getStatuses(): array
    {
        if (self::$statuses === null) {
            self::$statuses = [
                self::IN_PROGRESS,
                self::COMPLETED,
                self::PENDING,
                self::FAILED
            ];
        }

        return self::$statuses;
    }

    /**
     * Get the job type-to-page mappings.
     *
     * @return array The job type-to-page mapping array.
     */
    public static function getJobTypePageMapping(): array
    {
        return self::$jobTypePageMapping;
    }

    /**
     * Get the page mapped to the Job type.
     *
     * @param string $queuedJobType The job type identifier.
     * @return string The corresponding page name or 'UnknownJobType' if not found.
     */
    public static function getPageByJobType(string $queuedJobType): string
    {
        return self::$jobTypePageMapping[$queuedJobType] ?? '';
    }
}
