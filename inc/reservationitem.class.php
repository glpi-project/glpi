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
   public $type = RESERVATION_TYPE;
   
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

   function getSearchOptions() {
      global $LANG;

      $tab = array();

      $tab[4]['table']     = 'glpi_reservationitems';
      $tab[4]['field']     = 'comment';
      $tab[4]['linkfield'] = 'comment';
      $tab[4]['name']      = $LANG['common'][25];
      $tab[4]['datatype']  = 'text';

      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']     = 'reservation_types';
      $tab[1]['field']     = 'name';
      $tab[1]['linkfield'] = 'name';
      $tab[1]['name']      = $LANG['common'][16];
      $tab[1]['datatype']  = 'itemlink';

      $tab[2]['table']     = 'reservation_types';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = 'id';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_locations';
      $tab[3]['field']     = 'completename';
      $tab[3]['linkfield'] = 'locations_id';
      $tab[3]['name']      = $LANG['common'][15];

      $tab[16]['table']     = 'reservation_types';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][34];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[19]['table']     = 'reservation_types';
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[23]['table']     = 'glpi_manufacturers';
      $tab[23]['field']     = 'name';
      $tab[23]['linkfield'] = 'manufacturers_id';
      $tab[23]['name']      = $LANG['common'][5];

      $tab[24]['table']     = 'glpi_users';
      $tab[24]['field']     = 'name';
      $tab[24]['linkfield'] = 'users_id_tech';
      $tab[24]['name']      = $LANG['common'][10];

      $tab[80]['table']     = 'glpi_entities';
      $tab[80]['field']     = 'completename';
      $tab[80]['linkfield'] = 'entities_id';
      $tab[80]['name']      = $LANG['entity'][0];

      return $tab;
   }

}


?>
