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
use State;

class StateFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return State::getTypeName(Session::getPluralNumber());
    }

    public static function getId(): string
    {
        return "state";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'states_id');
    }

    public static function getCriteria(string $table, $value): array
    {
        $criteria = [];
        $states_ids = self::normalizeIntValues($value);

        if (count($states_ids) === 0) {
            return $criteria;
        }

        $criteria["WHERE"] = [
            "$table.states_id" => count($states_ids) === 1 ? $states_ids[0] : $states_ids,
        ];

        return $criteria;
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        $criteria = [];
        $states_ids = self::normalizeIntValues($value);

        if (count($states_ids) === 0) {
            return $criteria;
        }

        $so_id = self::getSearchOptionID($table, 'states_id', 'glpi_states');

        if (count($states_ids) === 1) {
            $criteria[] = [
                'link'       => 'AND',
                'field'      => $so_id,
                'searchtype' => 'equals',
                'value'      => $states_ids[0],
            ];
        } else {
            $sub = [];
            foreach ($states_ids as $i => $states_id) {
                $sub[] = [
                    'link'       => $i === 0 ? 'AND' : 'OR',
                    'field'      => $so_id,
                    'searchtype' => 'equals',
                    'value'      => $states_id,
                ];
            }
            $criteria[] = [
                'link'     => 'AND',
                'criteria' => $sub,
            ];
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayMultipleList(
            self::getName(),
            self::normalizeIntValues($value),
            self::getId(),
            State::class
        );
    }
}
