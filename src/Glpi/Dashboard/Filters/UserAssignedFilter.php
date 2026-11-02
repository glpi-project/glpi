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

namespace Glpi\Dashboard\Filters;

use Change;
use Problem;
use Session;
use Ticket;
use User;

class UserAssignedFilter extends AbstractFilter
{
    use AssignedITILUserFilterTrait;

    public static function getName(): string
    {
        return __("User / Assigned user");
    }

    public static function getId(): string
    {
        return "user_assigned";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'users_id')
            || in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        $users_id = null;
        if ((int) $value > 0) {
            $users_id = (int) $value;
        } elseif ($value === 'myself') {
            $users_id = $_SESSION['glpiID'];
        }

        if ($users_id !== null) {
            if ($DB->fieldExists($table, 'users_id')) {
                $criteria["WHERE"] = [
                    "$table.users_id" => $users_id,
                ];
            } elseif (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true)) {
                $criteria = self::getAssignedITILUserCriteria($table, $users_id);
            }
        }

        return $criteria;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getSearchCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        if ((int) $value > 0 || $value === 'myself') {
            if ($DB->fieldExists($table, 'users_id')) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => self::getSearchOptionID($table, 'users_id', 'glpi_users'),
                    'searchtype' => 'equals',
                    'value'      => $value === 'myself' ? (int) Session::getLoginUserID() : (int) $value,
                ];
            } elseif (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true)) {
                $criteria[] = self::getAssignedITILUserSearchCriteria($value);
            }
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            self::getId(),
            User::class,
            [
                'right' => 'own_ticket',
                'toadd' => [
                    [
                        'id'   => 'myself',
                        'text' => __('Myself'),
                    ],
                ],
            ]
        );
    }
}
