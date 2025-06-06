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

class ComputerAntivirus implements DeprecatedInterface
{
    public static function getDeprecatedType(): string
    {
        return 'ComputerAntivirus';
    }

    public static function getCurrentType(): string
    {
        return 'ItemAntivirus';
    }

    public static function getDeprecatedFields(): array
    {
        return [
            'id',
            'computers_id',
            'name',
            'manufacturers_id',
            'antivirus_version',
            'signature_version',
            'is_active',
            'is_deleted',
            'is_uptodate',
            'is_dynamic',
            'date_expiration',
            'date_mod',
            'date_creation',
            'links', // added by API
        ];
    }

    public static function getCurrentAddInput(): array
    {
        return [
            'name'     => __FUNCTION__,
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
        ];
    }

    public static function getDeprecatedAddInput(): array
    {
        return [
            'name'         => __FUNCTION__,
            'computers_id' => getItemByTypeName('Computer', '_test_pc01', true),
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
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc01', true),
        ];
    }

    public static function getExpectedAfterUpdate(): array
    {
        return [
            'itemtype' => 'Computer',
            'items_id' => getItemByTypeName('Computer', '_test_pc02', true),
        ];
    }

    public static function getDeprecatedSearchQuery(): string
    {
        return 'forcedisplay[0]=2&rawdata=1';
    }

    public static function getCurrentSearchQuery(): string
    {
        return 'forcedisplay[0]=2&criteria[0][field]=4&criteria[0][searchtype]=equals&criteria[0][value]=Computer&rawdata=1';
    }
}
