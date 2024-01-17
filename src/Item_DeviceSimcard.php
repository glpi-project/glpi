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
 * @since 9.2
 */


/**
 * Relation between item and devices
 **/
class Item_DeviceSimcard extends Item_Devices
{
    public static $itemtype_2 = 'DeviceSimcard';
    public static $items_id_2 = 'devicesimcards_id';

    protected static $notable = false;

    public static $undisclosedFields      = ['pin', 'pin2', 'puk', 'puk2'];

    public static function getSpecificities($specif = '')
    {
        return [
            'serial'         => parent::getSpecificities('serial'),
            'otherserial'    => parent::getSpecificities('otherserial'),
            'locations_id'   => parent::getSpecificities('locations_id'),
            'states_id'      => parent::getSpecificities('states_id'),
            'pin'            => ['long name'  => __('PIN code'),
                'short name' => __('PIN code'),
                'size'       => 20,
                'id'         => 15,
                'datatype'   => 'text',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true
            ],
            'pin2'            => ['long name'  => __('PIN2 code'),
                'short name' => __('PIN2 code'),
                'size'       => 20,
                'id'         => 16,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true
            ],
            'puk'             => ['long name'  => __('PUK code'),
                'short name' => __('PUK code'),
                'size'       => 20,
                'id'         => 17,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true
            ],
            'puk2'            => ['long name'  => __('PUK2 code'),
                'short name' => __('PUK2 code'),
                'size'       => 20,
                'id'         => 18,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true
            ],
            'lines_id'        => ['long name'  => Line::getTypeName(1),
                'short name' => Line::getTypeName(1),
                'size'       => 20,
                'id'         => 19,
                'datatype'   => 'dropdown'
            ],
            'msin'           => ['long name'  => __('Mobile Subscriber Identification Number'),
                'short name' => __('MSIN'),
                'size'       => 20,
                'id'         => 20,
                'datatype'   => 'string',
                'tooltip'    => __('MSIN is the last 8 or 10 digits of IMSI')
            ],
            'users_id'        => ['long name'  => User::getTypeName(1),
                'short name' => User::getTypeName(1),
                'size'       => 20,
                'id'         => 21,
                'datatype'   => 'dropdown',
                'dropdown_options' => ['right' => 'all']
            ],
            'groups_id'        => ['long name'  => Group::getTypeName(1),
                'short name' => Group::getTypeName(1),
                'size'       => 20,
                'id'         => 22,
                'datatype'   => 'dropdown'
            ],
        ];
    }

    public static function getNameField()
    {
        return 'serial';
    }

    public function getImportCriteria(): array
    {
        return [
            'serial' => 'equal',
            'msin' => 'equal',
        ];
    }
}
