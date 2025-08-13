<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Features\AssignableItem;
use Group;
use Group_Item;
use Problem;
use Ticket;
use Toolbox;
use UnexpectedValueException;

abstract class AbstractGroupFilter extends AbstractFilter
{
    /**
     * Get the group type
     * @return int
     * @phpstan-return Group_Item::GROUP_TYPE_*
     */
    abstract protected static function getGroupType(): int;

    /**
     * Get the field name for the group when stored directly in the item's table.
     * @return string
     */
    abstract protected static function getGroupFieldName(): string;

    /**
     * Get the search option ID for the group field for Tickets, Changes and Problems.
     * @return int
     */
    abstract protected static function getITILSearchOptionID(): int;

    public static function canBeApplied(string $table): bool
    {
        global $DB;

        return Toolbox::hasTrait(getItemtypeForTable($table), AssignableItem::class)
            || $DB->fieldExists($table, 'groups_id')
            || in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true);
    }

    public static function getCriteria(string $table, $value): array
    {
        global $DB;

        $criteria = [];

        $groups_id = null;
        if ((int) $value > 0) {
            $groups_id = (int) $value;
        } elseif ($value == 'mygroups') {
            $groups_id = $_SESSION['glpigroups'];
        }

        if ($groups_id != null) {
            if ($DB->fieldExists($table, static::getGroupFieldName())) {
                $criteria["WHERE"] = [
                    $table . '.' . static::getGroupFieldName() => $groups_id,
                ];
            } elseif (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true)) {
                $main_item = match ($table) {
                    Ticket::getTable() => new Ticket(),
                    Change::getTable() => new Change(),
                    Problem::getTable() => new Problem(),
                    default => throw new UnexpectedValueException(),
                };
                $grouplink = $main_item->grouplinkclass;
                $gl_table  = $grouplink::getTable();
                $fk        = $main_item::getForeignKeyField();

                $criteria["JOIN"] = [
                    "$gl_table as gl" => [
                        'ON' => [
                            'gl'   => $fk,
                            $table => 'id',
                        ],
                    ],
                ];
                $criteria["WHERE"] = [
                    "gl.type"      => static::getGroupType(),
                    "gl.groups_id" => $groups_id,
                ];
            } else {
                $group_item_table = Group_Item::getTable();
                $criteria['JOIN'] = [
                    $group_item_table => [
                        'ON' => [
                            $group_item_table => 'items_id',
                            $table => 'id', [
                                'AND' => [
                                    $group_item_table . '.itemtype' => getItemtypeForTable($table),
                                ],
                            ],
                        ],
                    ],
                ];
                $criteria["WHERE"] = [
                    $group_item_table . ".type" => static::getGroupType(),
                    $group_item_table . '.groups_id' => $groups_id,
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
        } elseif ($value == 'mygroups') {
            $groups_id =  'mygroups';
        }

        if ($groups_id != null) {
            if (in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true)) {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => static::getITILSearchOptionID(), // requester group
                    'searchtype' => 'equals',
                    'value'      => $groups_id,
                ];
            } else {
                $criteria[] = [
                    'link'       => 'AND',
                    'field'      => self::getSearchOptionID($table, static::getGroupFieldName(), 'glpi_groups'),
                    'searchtype' => 'equals',
                    'value'      => $groups_id,
                ];
            }
        }

        return $criteria;
    }

    public static function getHtml($value): string
    {
        return self::displayList(
            static::getName(),
            is_string($value) ? $value : "",
            static::getId(),
            Group::class,
            [
                'toadd' => ['mygroups' => __("My groups")],
            ]
        );
    }
}
