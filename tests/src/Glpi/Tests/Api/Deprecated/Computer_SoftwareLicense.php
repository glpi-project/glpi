<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Tests\Api\Deprecated;

class Computer_SoftwareLicense implements DeprecatedInterface
{
    public static function getDeprecatedType(): string
    {
        return "Computer_SoftwareLicense";
    }

    public static function getCurrentType(): string
    {
        return "Item_SoftwareLicense";
    }

    public static function getDeprecatedFields(): array
    {
        return [
            "id", "computers_id", "softwarelicenses_id", "is_deleted", "is_dynamic",
            "links"
        ];
    }

    public static function getCurrentAddInput(): array
    {
        return [
            "itemtype" => "Computer",
            "items_id" => getItemByTypeName(
                'Computer',
                '_test_pc01',
                true
            ),
            "softwarelicenses_id" => getItemByTypeName(
                'SoftwareLicense',
                '_test_softlic_1',
                true
            ),
        ];
    }

    public static function getDeprecatedAddInput(): array
    {
        return [
            "computers_id" => getItemByTypeName(
                'Computer',
                '_test_pc01',
                true
            ),
            "softwarelicenses_id" => getItemByTypeName(
                'SoftwareLicense',
                '_test_softlic_1',
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
        return "forcedisplay[0]=2&criteria[0][field]=6&criteria[0][searchtype]=equals&criteria[0][value]=Computer&rawdata=1";
    }
}
