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

/**
 * Relation between item and devices
 **/
class Item_DeviceBattery extends Item_Devices
{
    public static $itemtype_2 = 'DeviceBattery';
    public static $items_id_2 = 'devicebatteries_id';

    protected static $notable = false;


    public static function getSpecificities($specif = '')
    {
        return [
            'serial'             => parent::getSpecificities('serial'),
            'otherserial'        => parent::getSpecificities('otherserial'),
            'locations_id'       => parent::getSpecificities('locations_id'),
            'states_id'          => parent::getSpecificities('states_id'),
            'manufacturing_date' => [
                'long name' => __('Manufacturing date'),
                'short name' => _n('Date', 'Dates', 1),
                'size'       => 10,
                'datatype'   => 'date',
                'id'         => 20,
            ],
            'real_capacity' => [
                'long name' => __('Real capacity (mWh)'),
                'short name' => __('Real capacity'),
                'size'       => 10,
                'id'         => 21,
                'datatype'   => 'progressbar',
                'max'       => 'capacity',    // Field used to represent 100% capacity value
            ]
        ];
    }

    public function getImportCriteria(): array
    {
        return [
            'serial' => 'equal',
            //'real_capacity' => 'delta:10'
        ];
    }
}
