<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\TestHelpers\Tracker;

use Perms;
use Perms_Builder;
use Perms_Context;
use TikiDb;
use TikiLib;

/**
 * Shared permission and unified-index fixture for tracker tests that need
 * group permissions granted and/or a search index built around their data.
 *
 * Extracted verbatim from the setup previously duplicated in
 * TrackerDatesTimezoneTest and TrackerItemPermissionTest. Methods are
 * granular so each test can keep its exact teardown ordering.
 */
final class TrackerPermissionsFixture
{
    /** @var array<string, string[]> map of groupName => permNames granted by this fixture */
    private $granted = [];

    /**
     * Grant permissions to groups (REPLACE INTO users_grouppermissions) and
     * rebuild the global Perms resolver so the grants take effect.
     *
     * @param array<string, string[]> $permsByGroup e.g. ['Registered' => ['tiki_p_view_trackers']]
     */
    public function grantGroupPermissions(array $permsByGroup): void
    {
        foreach ($permsByGroup as $group => $perms) {
            foreach ($perms as $perm) {
                TikiDb::get()->query("REPLACE INTO `users_grouppermissions` VALUES(?, ?, '')", [$group, $perm]);
                $this->granted[$group][] = $perm;
            }
        }
        $this->rebuildPerms();
    }

    /**
     * Delete the permission rows recorded by grantGroupPermissions() and
     * rebuild the global Perms resolver.
     */
    public function revokeGroupPermissions(): void
    {
        foreach ($this->granted as $group => $perms) {
            foreach ($perms as $perm) {
                TikiDb::get()->query("DELETE FROM `users_grouppermissions` WHERE `groupName` = ? AND `permName` = ?", [$group, $perm]);
            }
        }
        $this->granted = [];
        $this->rebuildPerms();
    }

    /**
     * Make permission checks resolve as $user belonging to $groups.
     */
    public function impersonate(string $user, array $groups): void
    {
        new Perms_Context($user);
        Perms::getInstance()->setGroups($groups);
    }

    public function rebuildUnifiedIndex(): void
    {
        TikiLib::lib('unifiedsearch')->rebuild();
    }

    /**
     * Drop the MySQL unified-search index table created by
     * rebuildUnifiedIndex() and invalidate the index cache.
     */
    public function dropUnifiedIndex(): void
    {
        global $prefs;

        if (! empty($prefs['unified_mysql_index_current'])) {
            TikiDb::get()->query("DROP TABLE `{$prefs['unified_mysql_index_current']}`");
            TikiLib::lib('tiki')->delete_preference('unified_mysql_index_current');
        }

        TikiLib::lib('unifiedsearch')->invalidateIndicesCache();
    }

    private function rebuildPerms(): void
    {
        $builder = new Perms_Builder();
        Perms::set($builder->build());
    }
}
