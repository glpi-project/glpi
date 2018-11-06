<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @since 0.84
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Relation between item and devices
**/
class Item_DeviceModem extends Item_Devices {

   static public $itemtype_2 = 'DeviceModem';
   static public $items_id_2 = 'devicemodems_id';

   static protected $notable = false;


   /**
    * @since 0.85
    */
   static function getSpecificities($specif = '') {
      return [
         'serial'       => parent::getSpecificities('serial'),
         'otherserial'  => parent::getSpecificities('otherserial'),
         'locations_id' => parent::getSpecificities('locations_id'),
         'states_id'    => parent::getSpecificities('states_id'),
         'imei_1'       => ['long name'  => __('IMEI 1'),
                            'short name' => __('IMEI 1'),
                            'size'       => 10,
                            'id'         => 15],
         'imei_2'       => ['long name'  => __('IMEI 2'),
                            'short name' => __('IMEI 2'),
                            'size'       => 10,
                            'id'         => 16],
         'imei_3'       => ['long name'  => __('IMEI 3'),
                             'short name' => __('IMEI 3'),
                             'size'       => 10,
                             'id'         => 17],
         'imei_4'       => ['long name'  => __('IMEI 4'),
                             'short name' => __('IMEI 4'),
                             'size'       => 10,
                             'id'         => 18],
         'devicesimcardtypes_id_1'   => ['long name'  => __('Sim type 1'),
                             'short name' => __('Sim type 1'),
                             'size'       => 10,
                             'id'         => 19,
                             'datatype'   => 'dropdown'],
         'devicesimcardtypes_id_2'   => ['long name'  => __('Sim type 2'),
                             'short name' => __('Sim type 2'),
                             'size'       => 10,
                             'id'         => 20,
                             'datatype'   => 'dropdown'],
         'devicesimcardtypes_id_3'   => ['long name'  => __('Sim type 3'),
                             'short name' => __('Sim type 3'),
                             'size'       => 10,
                             'id'         => 21,
                             'datatype'   => 'dropdown'],
         'devicesimcardtypes_id_4'   => ['long name'  => __('Sim type 4'),
                             'short name' => __('Sim type 4'),
                             'size'       => 10,
                             'id'         => 22,
                             'datatype'   => 'dropdown'],
         'firmware_version'         => ['long name'  => __('Firmware version'),
                             'short name' => __('Firmware version'),
                             'size'       => 10,
                             'id'         => 22,
                             'datatype'   => 'string'],
         'busID'        => parent::getSpecificities('busID'),
      ];
   }
}
