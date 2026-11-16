<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\System;

use CommonDBTM;
use CommonGLPI;
use CronTask;
use Item_Environment;
use Item_Process;
use Log;

/**
 * Catch-all automatic action dedicated to remove obsolete data.
 *
 * Each chore handles a bounded amount of rows, so the task can be run frequently on large databases
 * without locking tables for too long. Chores are run in order, until the global limit is reached.
 */
final class HousekeepingCron extends CommonGLPI
{
    public const TASK_NAME = 'housekeeping';

    public const DEFAULT_MAX_ROWS = 10000;

    public static function getTypeName($nb = 0)
    {
        return __('Housekeeping');
    }

    /**
     * @param string $name
     *
     * @return array{description: string, parameter: string}
     */
    public static function cronInfo($name)
    {
        return [
            'description' => __('Remove obsolete data'),
            'parameter'   => __('Max rows to handle in one execution'),
        ];
    }

    /**
     * Run from cron task.
     *
     * @return int 0 if nothing to do, 1 if done, -1 if there is remaining work
     */
    public static function cronHousekeeping(CronTask $task): int
    {
        $max = (int) ($task->fields['param'] ?: self::DEFAULT_MAX_ROWS);

        $total = 0;
        foreach (self::getChores() as $label => $chore) {
            if ($total >= $max) {
                break;
            }
            $done = $chore($max - $total);
            if ($done > 0) {
                $task->log(sprintf('%s: %d', $label, $done));
            }
            $total += $done;
        }
        $task->addVolume($total);

        if ($total === 0) {
            return 0;
        }
        // limit reached, check whether remaining work exists
        return $total >= $max && self::hasPendingWork() ? -1 : 1;
    }

    /**
     * Get chores to run, indexed by their label.
     * Each chore receives the max number of rows it may handle, and returns the number of handled rows.
     *
     * @return array<string, callable(int): int>
     */
    public static function getChores(): array
    {
        $chores = [];
        foreach (self::getNotHistorizedItemtypes() as $label => $itemtype) {
            $chores[$label] = static fn(int $max): int => self::cleanLogs($itemtype, $max);
        }
        return $chores;
    }

    /**
     * Check whether some chores still have work to do.
     */
    public static function hasPendingWork(): bool
    {
        foreach (self::getNotHistorizedItemtypes() as $itemtype) {
            if (self::hasLogs($itemtype)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Itemtypes that are not historized anymore, but may still have history entries.
     *
     * @return array<string, class-string<CommonDBTM>>
     */
    private static function getNotHistorizedItemtypes(): array
    {
        return [
            __('History entries related to processes')             => Item_Process::class,
            __('History entries related to environment variables') => Item_Environment::class,
        ];
    }

    /**
     * Remove history entries related to given itemtype.
     *
     * @param class-string<CommonDBTM> $itemtype
     */
    private static function cleanLogs(string $itemtype, int $max): int
    {
        global $DB;

        $total = 0;
        foreach (self::getLogsCriteria($itemtype) as $criteria) {
            if ($total >= $max) {
                break;
            }
            $ids = array_column(
                iterator_to_array($DB->request([
                    'SELECT' => ['id'],
                    'FROM'   => Log::getTable(),
                    'WHERE'  => $criteria,
                    'LIMIT'  => $max - $total,
                ])),
                'id'
            );
            if ($ids === []) {
                continue;
            }
            $DB->delete(Log::getTable(), ['id' => $ids]);
            $total += $DB->getAffectedRows();
        }

        return $total;
    }

    /**
     * @param class-string<CommonDBTM> $itemtype
     */
    private static function hasLogs(string $itemtype): bool
    {
        global $DB;

        foreach (self::getLogsCriteria($itemtype) as $criteria) {
            if (
                count($DB->request([
                    'SELECT' => ['id'],
                    'FROM'   => Log::getTable(),
                    'WHERE'  => $criteria,
                    'LIMIT'  => 1,
                ])) > 0
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Entries are logged either on the parent item (`itemtype_link`) or on the item itself (`itemtype`).
     * Distinct criteria are used instead of an `OR`, so each one can use its own index.
     *
     * @param class-string<CommonDBTM> $itemtype
     *
     * @return array<int, array<string, string>>
     */
    private static function getLogsCriteria(string $itemtype): array
    {
        return [
            ['itemtype_link' => $itemtype],
            ['itemtype'      => $itemtype],
        ];
    }
}
