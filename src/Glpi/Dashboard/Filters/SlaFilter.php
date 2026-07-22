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
use SLA;

class SlaFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return SLA::getTypeName(Session::getPluralNumber());
    }

    public static function getId(): string
    {
        return "sla";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'slas_id_ttr')
            || $DB->fieldExists($table, 'slas_id_tto');
    }

    /**
     * @return array<string, mixed>
     */
    public static function getCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        if ((int) $value > 0) {
            $or = [];
            if ($DB->fieldExists($table, 'slas_id_ttr')) {
                $or["$table.slas_id_ttr"] = (int) $value;
            }
            if ($DB->fieldExists($table, 'slas_id_tto')) {
                $or["$table.slas_id_tto"] = (int) $value;
            }

            if ($or !== []) {
                $criteria["WHERE"] = ['OR' => $or];
            }
        }

        return $criteria;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getSearchCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        if ((int) $value > 0) {
            if ($DB->fieldExists($table, 'slas_id_ttr')) {
                $criteria[] = [
                    'link'       => count($criteria) ? 'OR' : 'AND',
                    'field'      => self::getSearchOptionID($table, 'slas_id_ttr', 'glpi_slas'),
                    'searchtype' => 'equals',
                    'value'      => (int) $value,
                ];
            }
            if ($DB->fieldExists($table, 'slas_id_tto')) {
                $criteria[] = [
                    'link'       => count($criteria) ? 'OR' : 'AND',
                    'field'      => self::getSearchOptionID($table, 'slas_id_tto', 'glpi_slas'),
                    'searchtype' => 'equals',
                    'value'      => (int) $value,
                ];
            }
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            self::getName(),
            is_string($value) ? $value : "",
            'sla',
            SLA::class
        );
    }
}
