<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

/**
 * Materialized counters used to sort users by ITIL actor relationships.
 */
final class UserITILObjectCount extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return _n('User ITIL object count', 'User ITIL object counts', $nb);
    }

    public static function refreshForActor(string $actor_class, int $users_id, int $actor_type): void
    {
        global $DB;

        if ($users_id <= 0 || !is_subclass_of($actor_class, CommonITILActor::class)) {
            return;
        }

        $itemtype = $actor_class::$itemtype_1;
        if (!is_a($itemtype, CommonITILObject::class, true)) {
            return;
        }

        $relation_table = $actor_class::getTable();
        $relation_fk = $actor_class::getItilObjectForeignKey();
        $itil_table = $itemtype::getTable();

        $iterator = $DB->request([
            'SELECT' => new QueryExpression('COUNT(DISTINCT ' . DBmysql::quoteName($relation_table . '.' . $relation_fk) . ') AS cnt'),
            'FROM'   => $relation_table,
            'INNER JOIN' => [
                $itil_table => [
                    'FKEY' => [
                        $itil_table      => 'id',
                        $relation_table  => $relation_fk,
                    ],
                ],
            ],
            'WHERE' => [
                $relation_table . '.users_id'  => $users_id,
                $relation_table . '.type'      => $actor_type,
                $itil_table . '.is_deleted'    => 0,
            ],
        ]);

        $count = (int) ($iterator->current()['cnt'] ?? 0);

        if ($count > 0) {
            $DB->updateOrInsert(
                self::getTable(),
                [
                    'users_id'    => $users_id,
                    'itemtype'    => $itemtype,
                    'actor_type'  => $actor_type,
                    'count'       => $count,
                    'date_mod'    => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
                ],
                [
                    'users_id'    => $users_id,
                    'itemtype'    => $itemtype,
                    'actor_type'  => $actor_type,
                ]
            );
            return;
        }

        $DB->delete(
            self::getTable(),
            [
                'users_id'    => $users_id,
                'itemtype'    => $itemtype,
                'actor_type'  => $actor_type,
            ]
        );
    }
}
