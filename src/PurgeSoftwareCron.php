<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\DBAL\QuerySubQuery;

/**
 * @since 11.0.0
 */
class PurgeSoftwareCron extends CommonDBTM
{
    const TASK_NAME = 'purgesoftware';

    const MAX_BATCH_SIZE = 2000;

    protected static $notable = true;

    /**
     * Get task description
     *
     * @return string
     */
    public static function getTaskDescription(): string
    {
        return __("Purge software with no version that are deleted.");
    }

    /**
     * Get task's parameter description
     *
     * @return string
     */
    public static function getParameterDescription(): string
    {
        return __('Max items to handle in one execution');
    }

    public static function cronInfo($name)
    {
        return [
            'description' => self::getTaskDescription(),
            'parameter' => self::getParameterDescription(),
        ];
    }

    /**
     * Purge deleted & unused software
     *
     * @param int $max Max items to handle
     * @return int Number of purged items
     */
    public static function run(?int $max): int
    {
        $total = 0;

        // Purge deleted software
        $total += self::purgeItems(
            self::getDeletedSoftwareWithNoVersionsCriteria(),
            new Software(),
            $max - $total
        );

        return $total;
    }

    /**
     * Run from cronTask
     *
     * @param CronTask $task
     */
    public static function cronPurgeSoftware(CronTask $task)
    {
        $max = $task->fields['param'];
        $total = self::run($max);
        $task->addVolume($total);

        return 1;
    }

    /**
     * Get all deleted software
     *
     * @return array
     */
    protected static function getDeletedSoftwareWithNoVersionsCriteria(): array
    {
        return [
            'SELECT' => 'id',
            'FROM'   => Software::getTable(),
            'WHERE'  => [
                'is_deleted' => 1,
                'NOT' => [
                    'id' => new QuerySubQuery(
                        [
                            'SELECT' => 'softwares_id',
                            'FROM'   => SoftwareVersion::getTable(),
                        ]
                    ),
                ]
            ]
        ];
    }

    /**
     * Purge given items
     *
     * @param array         $scope   Items to purge
     * @param CommonDBTM    $em      EM for this itemtype
     * @param int           $max     Max number of items to handle
     *
     * @return int Number of items purged
     */
    protected static function purgeItems(
        array $scope,
        CommonDBTM $em,
        int $max
    ): int {
        /** @var \DBmysql $DB */
        global $DB;

        $total = 0;

        do {
            $scope['LIMIT'] = min($max - $total, self::MAX_BATCH_SIZE);
            $items = $DB->request($scope);
            $count = count($items);
            $total += $count;

            foreach ($items as $item) {
                $em->delete($item, true);
            }

           // Stop if no items found
        } while ($count > 0 && $total < $max);

        return $total;
    }
}
