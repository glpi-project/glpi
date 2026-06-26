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

use Entity;
use Session;

class EntityFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return Entity::getTypeName(Session::getPluralNumber());
    }

    public static function getId(): string
    {
        return "entity";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'entities_id');
    }

    /** @return array<string, mixed> */
    public static function getCriteria(string $table, $value): array
    {
        $criteria = [];

        if ((int) $value > 0) {
            $sons = array_intersect(
                getSonsOf(Entity::getTable(), (int) $value),
                Session::getActiveEntities()
            );

            if ($sons !== []) {
                $criteria["WHERE"] = [
                    "$table.entities_id" => $sons,
                ];
            }
        }

        return $criteria;
    }

    /** @return array<int, array<string, mixed>> */
    public static function getSearchCriteria(string $table, $value): array
    {
        $criteria = [];

        if ((int) $value > 0) {
            $criteria[] = [
                'link'       => 'AND',
                'field'      => self::getSearchOptionID($table, 'entities_id', 'glpi_entities'),
                'searchtype' => 'under',
                'value'      => (int) $value,
            ];
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            'entity',
            Entity::class,
            ['entity' => Session::getActiveEntities()]
        );
    }
}
