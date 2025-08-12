<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;

/**
 * Relation between item and devices
 * @since 9.2
 **/
class Item_DeviceSimcard extends Item_Devices implements AssignableItemInterface
{
    use AssignableItem;

    public static $itemtype_2 = 'DeviceSimcard';
    public static $items_id_2 = 'devicesimcards_id';

    protected static $notable = false;

    public static $undisclosedFields      = ['pin', 'pin2', 'puk', 'puk2'];

    public function getCloneRelations(): array
    {
        $relations = parent::getCloneRelations();

        $relations[] = Infocom::class;

        return $relations;
    }

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
                'protected'  => true,
            ],
            'pin2'            => ['long name'  => __('PIN2 code'),
                'short name' => __('PIN2 code'),
                'size'       => 20,
                'id'         => 16,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true,
            ],
            'puk'             => ['long name'  => __('PUK code'),
                'short name' => __('PUK code'),
                'size'       => 20,
                'id'         => 17,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true,
            ],
            'puk2'            => ['long name'  => __('PUK2 code'),
                'short name' => __('PUK2 code'),
                'size'       => 20,
                'id'         => 18,
                'datatype'   => 'string',
                'right'      => 'devicesimcard_pinpuk',
                'nosearch'   => true,
                'nodisplay'  => true,
                'protected'  => true,
            ],
            'lines_id'        => ['long name'  => Line::getTypeName(1),
                'short name' => Line::getTypeName(1),
                'size'       => 20,
                'id'         => 19,
                'datatype'   => 'dropdown',
            ],
            'msin'           => ['long name'  => __('Mobile Subscriber Identification Number'),
                'short name' => __('MSIN'),
                'size'       => 20,
                'id'         => 20,
                'datatype'   => 'string',
                'tooltip'    => __('MSIN is the last 8 or 10 digits of IMSI'),
            ],
            'users_id'        => ['long name'  => User::getTypeName(1),
                'short name' => User::getTypeName(1),
                'size'       => 20,
                'id'         => 21,
                'datatype'   => 'dropdown',
                'dropdown_options' => ['right' => 'all'],
            ],
            'groups_id'        => [
                'long name'  => Group::getTypeName(1),
                'short name' => Group::getTypeName(1),
                'size'       => 20,
                'id'         => 22,
                'joinparams'         => [
                    'beforejoin'         => [
                        'table'              => 'glpi_groups_items',
                        'joinparams'         => [
                            'jointype'           => 'itemtype_item',
                            'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_NORMAL],
                        ],
                    ],
                ],
                'forcegroupby'       => true,
                'massiveaction'      => false,
                'datatype'           => 'dropdown',
                'dropdown_options' => ['multiple' => true],
            ],
            'users_id_tech'  => [
                'long name'  => __('Technician in charge'),
                'short name' => __('Technician in charge'),
                'size'       => 20,
                'id'         => 23,
                'datatype'   => 'dropdown',
                'dropdown_options' => ['right' => 'own_ticket'],
            ],
            'groups_id_tech' => [
                'long name'  => __('Group in charge'),
                'short name' => __('Group in charge'),
                'size'       => 20,
                'id'         => 24,
                'joinparams' => [
                    'beforejoin'         => [
                        'table'              => 'glpi_groups_items',
                        'joinparams'         => [
                            'jointype'           => 'itemtype_item',
                            'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                        ],
                    ],
                ],
                'forcegroupby'     => true,
                'massiveaction'    => false,
                'datatype'         => 'dropdown',
                'dropdown_options' => [
                    'condition' => ['is_assign' => 1],
                    'multiple' => true,
                ],
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
