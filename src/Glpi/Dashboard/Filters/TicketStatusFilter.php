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

use Session;
use Ticket;

class TicketStatusFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return _n("Ticket status", "Ticket status", Session::getPluralNumber());
    }

    public static function getId(): string
    {
        return "ticketstatus";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $table === Ticket::getTable() && $DB->fieldExists($table, 'status');
    }

    /**
     * @return array<string, array<string, int>>
     */
    public static function getCriteria(string $table, $value): array
    {
        return [
            "WHERE" => [
                "$table.status" => self::getStatusValue($value),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function getSearchCriteria(string $table, $value): array
    {
        return [
            [
                'link'       => 'AND',
                'field'      => self::getSearchOptionID($table, 'status', $table),
                'searchtype' => 'equals',
                'value'      => self::getStatusValue($value),
            ],
        ];
    }

    /**
     * Resolve the effective status to filter on.
     *
     * dropdownStatus() always displays a status, falling back to Ticket::INCOMING
     * when no value is set, so the filter must apply that same default to keep the
     * displayed value and the applied filter consistent.
     */
    private static function getStatusValue(string|int $value): int
    {
        return (int) $value > 0 ? (int) $value : Ticket::INCOMING;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            'ticketstatus',
            Ticket::class,
            function: 'dropdownStatus',
        );
    }
}
