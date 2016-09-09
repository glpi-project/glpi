<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
* @since version 0.84
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Relation between item and devices
**/
class Item_DeviceNetworkCard extends Item_Devices {

   static public $itemtype_2 = 'DeviceNetworkCard';
   static public $items_id_2 = 'devicenetworkcards_id';

   static protected $notable = false;


   /**
    * @since version 0.85
   **/
   static function getSpecificities($specif='') {

      return array('mac'    => array('long name'  => __('MAC address'),
                                     'short name' => __('MAC address'),
                                     'size'       => 18,
                                     'id'         => 20,
                                     'datatype'   => 'mac'),
                   'serial' => parent::getSpecificities('serial'),
                   'busID'  => parent::getSpecificities('busID'));
   }


   /**
    * @since version 0.85
   **/
   static function itemAffinity() {
      global $CFG_GLPI;
      return $CFG_GLPI["itemdevicenetworkcard_types"];
   }

}
?>
