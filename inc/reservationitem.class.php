<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Julien Dombre
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Reservation item class
class ReservationItem extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_reservationitems';

   /**
    * Retrieve an item from the database for a specific item
    *
    *@param $ID ID of the item
    *@param $itemtype type of the item
    *@return true if succeed else false
   **/
   function getFromDBbyItem($itemtype,$ID) {
      global $DB;

      $query = "SELECT *
                FROM `".$this->table."`
                WHERE (`itemtype` = '$itemtype'
                       AND `items_id` = '$ID')";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)==1) {
            $this->fields = $DB->fetch_assoc($result);
            return true;
         }
      }
      return false;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query2 = "DELETE
                 FROM `glpi_reservations`
                 WHERE `reservationitems_id` = '$ID'";
      $result2 = $DB->query($query2);
   }

   function prepareInputForAdd($input) {

      if (!$this->getFromDBbyItem($input['itemtype'],$input['items_id'])) {
         if (!isset($input['is_active'])) {
            $input['is_active']=1;
         }
         return $input;
      }
      return false;
   }

}


?>
