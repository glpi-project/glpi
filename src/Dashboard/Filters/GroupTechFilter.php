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

use Group;
use DBmysql;
use Ticket;
use Change;
use Problem;

class GroupTechFilter extends AbstractFilter
{
    /**
     * Get the filter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return __("Technician group");
    }

    /**
     * Get the filter id
     *
     * @return string
     */
    public static function getId(): string
    {
        return "group_tech";
    }

    /**
     * Get the filter criteria
     *
     * @return array
     */
    public static function getCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array
    {
        $criteria = [
            "WHERE" => [],
            "JOIN"  => [],
        ];

        if (isset($apply_filters[self::getId()])) {
            $groups_id = null;
            if ((int) $apply_filters[self::getId()] > 0) {
                $groups_id =  (int) $apply_filters[self::getId()];
            } else if ($apply_filters[self::getId()] == 'mygroups') {
                $groups_id =  $_SESSION['glpigroups'];
            }

            if ($groups_id != null) {
                if ($DB->fieldExists($table, 'groups_id_tech')) {
                    $criteria["WHERE"] += [
                        "$table.groups_id_tech" => $groups_id
                    ];
                } else if (
                    in_array($table, [
                        Ticket::getTable(),
                        Change::getTable(),
                        Problem::getTable(),
                    ])
                ) {
                    $itemtype  = getItemTypeForTable($table);
                    $main_item = getItemForItemtype($itemtype);
                    $grouplink = $main_item->grouplinkclass;
                    $gl_table  = $grouplink::getTable();
                    $fk        = $main_item->getForeignKeyField();

                    $criteria["JOIN"] += [
                        "$gl_table as gl" => [
                            'ON' => [
                                'gl'   => $fk,
                                $table => 'id',
                            ]
                        ]
                    ];
                    $criteria["WHERE"] += [
                        "gl.type"      => \CommonITILActor::ASSIGN,
                        "gl.groups_id" => $groups_id
                    ];
                }
            }
        }

        return $criteria;
    }

    /**
     * Get the search filter criteria
     *
     * @return array
     */
    public static function getSearchCriteria(DBmysql $DB, string $table = "", array $apply_filters = []): array
    {
        $criteria = [];

        if (isset($apply_filters[self::getId()])) {
            $groups_id = null;
            if ((int) $apply_filters[self::getId()] > 0) {
                $groups_id =  (int) $apply_filters[self::getId()];
            } else if ($apply_filters[self::getId()] == 'mygroups') {
                $groups_id =  'mygroups';
            }

            if ($groups_id != null) {
                if ($DB->fieldExists($table, 'groups_id_tech')) {
                    $criteria[] = [
                        'link'       => 'AND',
                        'field'      => self::getSearchOptionID($table, 'groups_id_tech', 'glpi_groups'), // group tech
                        'searchtype' => 'equals',
                        'value'      => $groups_id
                    ];
                } else if (
                    in_array($table, [
                        Ticket::getTable(),
                        Change::getTable(),
                        Problem::getTable(),
                    ])
                ) {
                    $criteria[] = [
                        'link'       => 'AND',
                        'field'      => 8, // group tech
                        'searchtype' => 'equals',
                        'value'      => $groups_id
                    ];
                }
            }
        }

        return $criteria;
    }

    /**
     * Get the html for the filter
     *
     * @return string
     */
    public static function getHtml(string $value = ""): string
    {
        return self::displayList(self::getName(), $value, 'group_tech', Group::class, ['toadd' => ['mygroups' => __("My groups")]]);
    }
}
