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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// NetworkPort_NetworkPort class
class NetworkPort_NetworkPort extends CommonDBRelation {

   // From CommonDBRelation
   static public $itemtype_1           = 'NetworkPort';
   static public $items_id_1           = 'networkports_id_1';
   static public $itemtype_2           = 'NetworkPort';
   static public $items_id_2           = 'networkports_id_2';

   static public $log_history_1_add    = Log::HISTORY_CONNECT_DEVICE;
   static public $log_history_2_add    = Log::HISTORY_CONNECT_DEVICE;

   static public $log_history_1_delete = Log::HISTORY_DISCONNECT_DEVICE;
   static public $log_history_2_delete = Log::HISTORY_DISCONNECT_DEVICE;


   /**
    * Retrieve an item from the database
    *
    * @param $ID ID of the item to get
    *
    * @return true if succeed else false
   **/
   function getFromDBForNetworkPort($ID) {

      return $this->getFromDBByCrit([
         'OR'  => [
            $this->getTable() . '.networkports_id_1'  => $ID,
            $this->getTable() . '.networkports_id_2'  => $ID
         ]
      ]);
   }


   /**
    * Get port opposite port ID
    *
    * @param $ID networking port ID
    *
    * @return integer ID of opposite port. false if not found
   **/
   function getOppositeContact($ID) {
      global $DB;

      if ($this->getFromDBForNetworkPort($ID)) {
         if ($this->fields['networkports_id_1'] == $ID) {
            return $this->fields['networkports_id_2'];
         }
         if ($this->fields['networkports_id_2'] == $ID) {
            return $this->fields['networkports_id_1'];
         }
         return false;
      }
   }

}
