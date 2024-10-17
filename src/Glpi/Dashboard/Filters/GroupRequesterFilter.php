<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

class GroupRequesterFilter extends AbstractFilter
{
    public static function getName(): string
    {
        return __("Group / Requester group");
    }

    public static function getId(): string
    {
        return "group_requester";
    }

    public static function canBeApplied(string $table): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        return $DB->fieldExists($table, 'groups_id')
            || in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()]);
    }

    public static function getCriteria(string $table, $value): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [];

        $groups_id = null;
        if ((int)$value > 0) {
            $groups_id = (int) $value;
        } else if ($value == 'mygroups') {
            $groups_id = $_SESSION['glpigroups'];
        }

        if ($groups_id != null) {
            if ($DB->fieldExists($table, 'groups_id')) {
                $criteria["WHERE"] = [
                    "$table.groups_id" => $groups_id
                ];
            } elseif (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()])) {
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
                    "gl.type"      => \CommonITILActor::REQUESTER,
                    "gl.groups_id" => $groups_id
                ];
            }
        }

        return $criteria;
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        /** @var \DBmysql $DB */
        global $DB;

        $criteria = [];

        $groups_id = null;
        if ((int) $value > 0) {
            $groups_id =  (int) $value;
        } else if ($value == 'mygroups') {
            $groups_id =  'mygroups';
        }

        if ($groups_id != null) {
            if ($DB->fieldExists($table, 'groups_id')) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => self::getSearchOptionID($table, 'groups_id', 'glpi_groups'),
                    'searchtype' => 'equals',
                    'value'      => $groups_id
                ];
            } else if (in_array($table, [Ticket::getTable(), Change::getTable(),Problem::getTable()])) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => 71, // requester group
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
            'group_requester',
            Group::class,
            [
                'toadd' => ['mygroups' => __("My groups")],
            ]
        );
    }
}
