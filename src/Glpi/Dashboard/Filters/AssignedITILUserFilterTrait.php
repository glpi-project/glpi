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
use CommonITILActor;
use Problem;
use Ticket;
use UnexpectedValueException;

/**
 * Shared logic to filter ITIL items (Ticket, Change, Problem) by their
 * assigned technician (user actor).
 */
trait AssignedITILUserFilterTrait
{
    /**
     * Build the JOIN/WHERE criteria matching an ITIL item assigned to a given user.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function getAssignedITILUserCriteria(string $table, int $users_id, string $alias = 'ul_assigned'): array
    {
        $main_item = match ($table) {
            Ticket::getTable()  => new Ticket(),
            Change::getTable()  => new Change(),
            Problem::getTable() => new Problem(),
            default => throw new UnexpectedValueException("Table $table is not a supported ITIL table"),
        };
        $userlink = $main_item->userlinkclass;
        $ul_table = $userlink::getTable();
        $fk       = $main_item->getForeignKeyField();

        return [
            'JOIN' => [
                "$ul_table as $alias" => [
                    'ON' => [
                        $alias  => $fk,
                        $table  => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                "$alias.type"     => CommonITILActor::ASSIGN,
                "$alias.users_id" => $users_id,
            ],
        ];
    }

    /**
     * Build the search criterion matching an ITIL item assigned to a given user.
     *
     * @param mixed $value
     *
     * @return array<string, mixed>
     */
    protected static function getAssignedITILUserSearchCriteria($value): array
    {
        return [
            'link'       => 'AND',
            'field'      => 5, // assigned technician
            'searchtype' => 'equals',
            'value'      => is_numeric($value) ? (int) $value : $value,
        ];
    }
}
