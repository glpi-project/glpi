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
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Alert class
 */
class Alert extends CommonDBTM {

   function prepareInputForAdd($input) {
      if (!isset($input['date']) || empty($input['date'])) {
         $input['date']=date("Y-m-d H:i:s");;
      }
      return $input;
   }


   /**
    * Clear all alerts of an alert type for an item
    *
     *@param $alert_type ID of the alert type to clear
    *@return nothing
    *
   **/
   function clear($alert_type) {
      global $DB;

      $query="DELETE
              FROM `".$this->getTable()."`
              WHERE `itemtype` = '".$this->getType()."'
                    AND `items_id` = '".$this->fields['id']."'
                    AND `type` = '$alert_type'";
      $DB->query($query);
   }

   static function dropdown($options = array()) {
      global $LANG;

      if (!isset($options['value'])){
         $value = 0;
      } else {
         $value = $options['value'];
      }

      if (isset($options['inherit_global']) && $options['inherit_global']){
         $times[-1] = $LANG['setup'][731];
      }

      $times[0] = $LANG['setup'][307];
      $times[DAY_TIMESTAMP] = $LANG['setup'][305];
      $times[WEEK_TIMESTAMP] = $LANG['setup'][308];
      $times[MONTH_TIMESTAMP] = $LANG['setup'][309];

      Dropdown::showFromArray($options['name'],
                              $times,
                              array('value'=>$value));
   }

}

?>