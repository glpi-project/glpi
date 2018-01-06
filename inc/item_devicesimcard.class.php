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
 * @since 9.2
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Relation between item and devices
 **/
class Item_DeviceSimcard extends Item_Devices {

   static public $itemtype_2 = 'DeviceSimcard';
   static public $items_id_2 = 'devicesimcards_id';

   static protected $notable = false;

   static $undisclosedFields      = ['pin', 'pin2', 'puk', 'puk2'];

   /**
    * @since 0.85
    **/
   static function getSpecificities($specif = '') {
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
                                  'protected'  => true],
             'pin2'            => ['long name'  => __('PIN2 code'),
                                  'short name' => __('PIN2 code'),
                                  'size'       => 20,
                                  'id'         => 16,
                                  'datatype'   => 'string',
                                  'right'      => 'devicesimcard_pinpuk',
                                  'nosearch'   => true,
                                  'nodisplay'  => true,
                                  'protected'  => true],
             'puk'             => ['long name'  => __('PUK code'),
                                  'short name' => __('PUK code'),
                                  'size'       => 20,
                                  'id'         => 17,
                                  'datatype'   => 'string',
                                  'right'      => 'devicesimcard_pinpuk',
                                  'nosearch'   => true,
                                  'nodisplay'  => true,
                                  'protected'  => true],
             'puk2'            => ['long name'  => __('PUK2 code'),
                                  'short name' => __('PUK2 code'),
                                  'size'       => 20,
                                  'id'         => 18,
                                  'datatype'   => 'string',
                                  'right'      => 'devicesimcard_pinpuk',
                                  'nosearch'   => true,
                                  'nodisplay'  => true,
                                  'protected'  => true],
             'lines_id'        => ['long name'  => __('Line'),
                                  'short name' => __('Line'),
                                  'size'       => 20,
                                  'id'         => 19,
                                  'datatype'   => 'dropdown'],
             'msin'           => ['long name'  => __('Mobile Subscriber Identification Number'),
                                  'short name' => __('MSIN'),
                                  'size'       => 20,
                                  'id'         => 20,
                                  'datatype'   => 'string',
                                  'tooltip'    => __('MSIN is the last 8 or 10 digits of IMSI')],
      ];
   }
}
