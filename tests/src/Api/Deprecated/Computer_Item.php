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

use Computer;
use Glpi\Asset\Asset_PeripheralAsset;
use Monitor;

class Computer_Item implements DeprecatedInterface
{
    public static function getDeprecatedType(): string
    {
        return 'Computer_Item';
    }

    public static function getCurrentType(): string
    {
        return Asset_PeripheralAsset::class;
    }

    public static function getDeprecatedFields(): array
    {
        return [
            'id',
            'items_id',
            'computers_id',
            'itemtype',
            'is_deleted',
            'is_dynamic',
            'links', // added by API
        ];
    }

    public static function getCurrentAddInput(): array
    {
        return [
            'name'                => __FUNCTION__,
            'itemtype_asset'      => Computer::class,
            'items_id_asset'      => getItemByTypeName(Computer::class, '_test_pc01', true),
            'itemtype_peripheral' => Monitor::class,
            'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
        ];
    }

    public static function getDeprecatedAddInput(): array
    {
        return [
            'name'         => __FUNCTION__,
            'computers_id' => getItemByTypeName(Computer::class, '_test_pc01', true),
            'itemtype'     => Monitor::class,
            'items_id'     => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
        ];
    }

    public static function getDeprecatedUpdateInput(): array
    {
        return [
            'computers_id' => getItemByTypeName(Computer::class, '_test_pc02', true),
            'itemtype'     => Monitor::class,
            'items_id'     => getItemByTypeName(Monitor::class, '_test_monitor_2', true),
        ];
    }

    public static function getExpectedAfterInsert(): array
    {
        return [
            'itemtype_asset'      => Computer::class,
            'items_id_asset'      => getItemByTypeName(Computer::class, '_test_pc01', true),
            'itemtype_peripheral' => Monitor::class,
            'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_1', true),
        ];
    }

    public static function getExpectedAfterUpdate(): array
    {
        return [
            'itemtype_asset'      => Computer::class,
            'items_id_asset'      => getItemByTypeName(Computer::class, '_test_pc02', true),
            'itemtype_peripheral' => Monitor::class,
            'items_id_peripheral' => getItemByTypeName(Monitor::class, '_test_monitor_2', true),
        ];
    }

    public static function getDeprecatedSearchQuery(): string
    {
        return 'forcedisplay[0]=2&rawdata=1';
    }

    public static function getCurrentSearchQuery(): string
    {
        return 'forcedisplay[0]=2&rawdata=1';
    }
}
