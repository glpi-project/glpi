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

use User;
use DBmysql;
use Ticket;
use Change;
use Problem;
use Session;

class UserTechFilter extends AbstractFilter
{
    /**
     * Get the filter name
     *
     * @return string
     */
    public static function getName(): string
    {
        return __("Technician");
    }

    /**
     * Get the filter id
     *
     * @return string
     */
    public static function getId(): string
    {
        return "user_tech";
    }

    public static function getCriteria(DBmysql $DB, string $table, $value): array
    {
        $criteria = [];

        $users_id = null;
        if ((int) $value > 0) {
            $users_id = (int) $value;
        } else if ($value === 'myself') {
            $users_id = $_SESSION['glpiID'];
        }

        if ($users_id !== null) {
            if ($DB->fieldExists($table, 'users_id_tech')) {
                $criteria["WHERE"] = [
                    "$table.users_id_tech" => $users_id,
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
                $userlink  = $main_item->userlinkclass;
                $ul_table  = $userlink::getTable();
                $fk        = $main_item->getForeignKeyField();

                $criteria["JOIN"] = [
                    "$ul_table as ul" => [
                        'ON' => [
                            'ul'   => $fk,
                            $table => 'id',
                        ]
                    ]
                ];
                $criteria["WHERE"] = [
                    "ul.type"     => \CommonITILActor::ASSIGN,
                    "ul.users_id" => $users_id,
                ];
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

        if (
            isset($apply_filters[self::getId()])
            && (
                (int) $apply_filters[self::getId()] > 0
                || $apply_filters[self::getId()] === 'myself'
            )
        ) {
            if ($DB->fieldExists($table, 'users_id_tech')) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => self::getSearchOptionID($table, 'users_id_tech', 'glpi_users'),// tech
                    'searchtype' => 'equals',
                    'value'      =>  $apply_filters[self::getId()] === 'myself' ? (int) Session::getLoginUserID() : (int) $apply_filters[self::getId()]
                ];
            } elseif (
                in_array($table, [
                    Ticket::getTable(),
                    Change::getTable(),
                    Problem::getTable(),
                ])
            ) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => 5,// tech
                    'searchtype' => 'equals',
                    'value'      =>  is_numeric($apply_filters[self::getId()]) ? (int) $apply_filters[self::getId()] : $apply_filters[self::getId()]
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
            'user_tech',
            User::class,
            [
                'right' => 'own_ticket',
                'toadd' => [
                    [
                        'id'    => 'myself',
                        'text'  => __('Myself'),
                    ]
                ]
            ]
        );
    }
}
