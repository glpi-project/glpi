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
use Group;
use Group_Item;
use Problem;
use Ticket;
use UnexpectedValueException;

abstract class AbstractITILGroupFilter extends AbstractFilter
{
    /** @phpstan-return Group_Item::GROUP_TYPE_* */
    abstract protected static function getGroupType(): int;

    abstract protected static function getITILSearchOptionID(): int;

    public static function canBeApplied(string $table): bool
    {
        return in_array($table, [Ticket::getTable(), Change::getTable(), Problem::getTable()], true);
    }

    /** @return int[] */
    private static function resolveGroupIds(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $groups_ids = [];
        foreach ($values as $v) {
            if ($v === 'mygroups') {
                foreach ($_SESSION['glpigroups'] as $g) {
                    $groups_ids[] = (int)$g;
                }
            } elseif ((int)$v > 0) {
                $groups_ids[] = (int)$v;
            }
        }
        return array_values(array_unique($groups_ids));
    }

    public static function getCriteria(string $table, $value): array
    {
        $groups_ids = self::resolveGroupIds($value);
        if (count($groups_ids) === 0) {
            return [];
        }

        $groups_ids_value = count($groups_ids) === 1 ? $groups_ids[0] : $groups_ids;

        $main_item = match ($table) {
            Ticket::getTable() => new Ticket(),
            Change::getTable() => new Change(),
            Problem::getTable() => new Problem(),
            default => throw new UnexpectedValueException(),
        };
        $gl_table = $main_item->grouplinkclass::getTable();
        $fk = $main_item::getForeignKeyField();

        return [
            'JOIN' => [
                "$gl_table as gl" => ['ON' => ['gl' => $fk, $table => 'id']],
            ],
            'WHERE' => [
                'gl.type' => static::getGroupType(),
                'gl.groups_id' => $groups_ids_value,
            ],
        ];
    }

    public static function getSearchCriteria(string $table, $value): array
    {
        $values = is_array($value) ? $value : [$value];
        $resolved = [];
        foreach ($values as $v) {
            if ($v === 'mygroups') {
                $resolved[] = 'mygroups';
            } elseif ((int)$v > 0) {
                $resolved[] = (int)$v;
            }
        }
        $resolved = array_values(array_unique($resolved));
        if (count($resolved) === 0) {
            return [];
        }

        $build = fn(int|string $id): array => [
            'field' => static::getITILSearchOptionID(),
            'searchtype' => 'equals',
            'value' => $id,
        ];

        if (count($resolved) === 1) {
            return [['link' => 'AND'] + $build($resolved[0])];
        }

        $sub = [];
        foreach ($resolved as $i => $id) {
            $sub[] = ['link' => $i === 0 ? 'AND' : 'OR'] + $build($id);
        }
        return [['link' => 'AND', 'criteria' => $sub]];
    }

    public static function getHtml($value): string
    {
        return self::displayMultipleList(
            static::getName(),
            is_array($value) ? array_values($value) : [],
            static::getId(),
            Group::class,
            ['toadd' => ['mygroups' => __("My groups")]]
        );
    }
}
