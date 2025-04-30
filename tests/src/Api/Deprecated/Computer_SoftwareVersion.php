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

namespace Glpi\Tests\Api\Deprecated;

class Computer_SoftwareVersion implements DeprecatedInterface
{
    public static function getDeprecatedType(): string
    {
        return "Computer_SoftwareVersion";
    }

    public static function getCurrentType(): string
    {
        return "Item_SoftwareVersion";
    }

    public static function getDeprecatedFields(): array
    {
        return [
            "id", "computers_id", "softwareversions_id", "is_deleted_computer",
            "is_template_computer", "entities_id", "is_deleted", "is_dynamic",
            "date_install", "links",
        ];
    }

    public static function getCurrentAddInput(): array
    {
        return [
            "users_id" => TU_USER,
            "itemtype" => "Computer",
            "items_id" => getItemByTypeName(
                'Computer',
                '_test_pc01',
                true
            ),
            "softwareversions_id" => getItemByTypeName(
                'SoftwareVersion',
                '_test_softver_1',
                true
            ),
            "entities_id" => getItemByTypeName(
                'Entity',
                '_test_root_entity',
                true
            ),
        ];
    }

    public static function getDeprecatedAddInput(): array
    {
        return [
            "users_id" => TU_USER,
            "computers_id" => getItemByTypeName(
                'Computer',
                '_test_pc01',
                true
            ),
            "softwareversions_id" => getItemByTypeName(
                'SoftwareVersion',
                '_test_softver_1',
                true
            ),
            "entities_id" => getItemByTypeName(
                'Entity',
                '_test_root_entity',
                true
            ),
        ];
    }

    public static function getDeprecatedUpdateInput(): array
    {
        return [
            'computers_id' => getItemByTypeName('Computer', '_test_pc02', true),
        ];
    }

    public static function getExpectedAfterInsert(): array
    {
        return [
            "itemtype" => "Computer",
            "items_id" => getItemByTypeName('Computer', '_test_pc01', true),
        ];
    }

    public static function getExpectedAfterUpdate(): array
    {
        return [
            "itemtype" => "Computer",
            "items_id" => getItemByTypeName('Computer', '_test_pc02', true),
        ];
    }

    public static function getDeprecatedSearchQuery(): string
    {
        return "forcedisplay[0]=2&rawdata=1";
    }

    public static function getCurrentSearchQuery(): string
    {
        return "forcedisplay[0]=2&criteria[0][field]=5&criteria[0][searchtype]=equals&criteria[0][value]=Computer&rawdata=1";
    }
}
