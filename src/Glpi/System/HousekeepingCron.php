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

use CommonGLPI;
use CronTask;
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
        return [
            __('History entries related to processes') => self::cleanProcessLogs(...),
        ];
    }

    /**
     * Check whether some chores still have work to do.
     */
    public static function hasPendingWork(): bool
    {
        return self::hasProcessLogs();
    }

    /**
     * Processes are not historized anymore since GLPI 11.0.13, remove existing entries.
     */
    private static function cleanProcessLogs(int $max): int
    {
        global $DB;

        $total = 0;
        foreach (self::getProcessLogsCriteria() as $criteria) {
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
            $total += $DB->affectedRows();
        }

        return $total;
    }

    private static function hasProcessLogs(): bool
    {
        global $DB;

        foreach (self::getProcessLogsCriteria() as $criteria) {
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
     * Distinct criteria are used instead of an `OR`, so each one can use its own index.
     *
     * @return array<int, array<string, string>>
     */
    private static function getProcessLogsCriteria(): array
    {
        return [
            ['itemtype_link' => Item_Process::class],
            ['itemtype'      => Item_Process::class],
        ];
    }
}
