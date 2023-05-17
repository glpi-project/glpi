<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

namespace Glpi\Dashboard\Filters;

use Change;
use Group;
use Problem;
use Ticket;

class GroupTechFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return __("Technician group");
    }

    public static function getId(): string
    {
        return "group_tech";
    }

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return $DB->fieldExists($table, 'groups_id_tech')
            || in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()]);
    }

    public static function getCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        $groups_id = null;
        if ((int)$value > 0) {
            $groups_id = (int) $value;
        } else if ($value == 'mygroups') {
            $groups_id = $_SESSION['glpigroups'];
        }

        if ($groups_id != null) {
            if ($DB->fieldExists($table, 'groups_id_tech')) {
                $criteria["WHERE"] = [
                    "$table.groups_id_tech" => $groups_id
                ];
            } else if (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()])) {
                $itemtype  = getItemTypeForTable($table);
                $main_item = getItemForItemtype($itemtype);
                $grouplink = $main_item->grouplinkclass;
                $gl_table  = $grouplink::getTable();
                $fk        = $main_item->getForeignKeyField();

                $criteria["JOIN"] = [
                    "$gl_table as gl" => [
                        'ON' => [
                            'gl'   => $fk,
                            $table => 'id',
                        ]
                    ]
                ];
                $criteria["WHERE"] = [
                    "gl.type"      => \CommonITILActor::ASSIGN,
                    "gl.groups_id" => $groups_id
                ];
            }
        }

        return $criteria;
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        $groups_id = null;
        if ((int) $value > 0) {
            $groups_id =  (int) $value;
        } else if ($value == 'mygroups') {
            $groups_id =  'mygroups';
        }

        if ($groups_id != null) {
            if ($DB->fieldExists($table, 'groups_id_tech')) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => self::getSearchOptionID($table, 'groups_id_tech', 'glpi_groups'),
                    'searchtype' => 'equals',
                    'value'      => $groups_id
                ];
            } else if (in_array($table, [Ticket::getTable(), Change::getTable(),Problem::getTable()])) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => 8, // group tech
                    'searchtype' => 'equals',
                    'value'      => $groups_id
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
            'group_tech',
            Group::class,
            [
                'toadd' => ['mygroups' => __("My groups")],
            ]
        );
    }
}
