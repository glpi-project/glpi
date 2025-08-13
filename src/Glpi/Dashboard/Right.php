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

namespace Glpi\Dashboard;

use CommonDBChild;
use Glpi\DBAL\QueryParam;

class Right extends CommonDBChild
{
    public static $itemtype = Dashboard::class;
    public static $items_id = 'dashboards_dashboards_id';

    // prevent bad getFromDB when bootstraping tests suite
    // FIXME Should be true
    public static $mustBeAttached = false;

    /**
     * Return rights for the provided dashboard
     *
     * @param int $dashboards_id
     *
     * @return array the rights
     */
    public static function getForDashboard(int $dashboards_id = 0): array
    {
        global $DB;

        $dr_iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'dashboards_dashboards_id' => $dashboards_id,
            ],
        ]);

        $rights = [];
        foreach ($dr_iterator as $right) {
            unset($right['id']);
            $rights[] = $right;
        }

        return $rights;
    }


    /**
     * Save rights in DB for the provided dashboard
     *
     * @param int $dashboards_id id (not key) of the dashboard
     * @param array $rights contains these data:
     * - 'users_id'    => [items_id]
     * - 'groups_id'   => [items_id]
     * - 'entities_id' => [items_id]
     * - 'profiles_id' => [items_id]
     *
     * @return void
     */
    public static function addForDashboard(int $dashboards_id = 0, array $rights = [])
    {
        global $DB;

        $query_rights = $DB->buildInsert(
            self::getTable(),
            [
                'dashboards_dashboards_id' => new QueryParam(),
                'itemtype' => new QueryParam(),
                'items_id' => new QueryParam(),
            ]
        );
        $stmt = $DB->prepare($query_rights);
        foreach ($rights as $fk => $right_line) {
            $itemtype = getItemtypeForForeignKeyField($fk);
            foreach ($right_line as $items_id) {
                $stmt->bind_param(
                    'isi',
                    $dashboards_id,
                    $itemtype,
                    $items_id
                );
                $DB->executeStatement($stmt);
            }
        }
    }
}
