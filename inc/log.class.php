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

// Log class
class Log extends CommonDBTM {

   static function getTypeName() {
      global $LANG;

      return $LANG['title'][38];
   }

   /**
    * Construct  history for an item
    *
    * @param $items CommonDBTM object
    * @param $oldvalues array of old values updated
    * @param $values array of all values of the item
    **/
   static function constructHistory(CommonDBTM $item, & $oldvalues, & $values) {
      global $LANG;

      if (count($oldvalues)) {
         // needed to have  $SEARCHOPTION
         foreach ($oldvalues as $key => $oldval) {
            $changes = array ();
            // Parsing $SEARCHOPTION to find infocom
            if ($item->getType() == 'Infocom') {
               $real_type = $item->fields['itemtype'];
               $real_id = $item->fields['items_id'];

               $searchopt = Search :: getOptions($real_type);
               if (is_array($searchopt)) {
                  foreach ($searchopt as $key2 => $val2) {
                     if (($val2["field"] == $key && strpos($val2['table'], 'infocoms'))
                         || ($key == 'budgets_id' && $val2['table'] == 'glpi_budgets')
                         || ($key == 'suppliers_id' && $val2['table'] == 'glpi_suppliers_infocoms')) {

                        $id_search_option = $key2; // Give ID of the $SEARCHOPTION
                        if ($val2["table"] == "glpi_infocoms") {
                           // 1st case : text field -> keep datas
                           $changes = array (
                              $id_search_option,
                              addslashes($oldval),
                              $values[$key]
                           );
                        } else
                           if ($val2["table"] == "glpi_suppliers_infocoms") {
                              // 2nd case ; link field -> get data from glpi_suppliers
                              $changes = array (
                                 $id_search_option,
                                 addslashes(Dropdown :: getDropdownName("glpi_suppliers", $oldval)),
                                 addslashes(Dropdown :: getDropdownName("glpi_suppliers", $values[$key]))
                              );
                           } else {
                              // 3rd case ; link field -> get data from dropdown (budget)
                              $changes = array (
                                 $id_search_option,
                                 addslashes(Dropdown :: getDropdownName($val2["table"], $oldval)),
                                 addslashes(Dropdown :: getDropdownName($val2["table"], $values[$key]))
                              );
                           }
                        break; // foreach exit
                     }
                  }
               }
            } else {
               $real_type = $item->getType();
               $real_id = $item->fields['id'];

               // Parsing $SEARCHOPTION, check if an entry exists matching $key
               $searchopt = Search :: getOptions($real_type);
               if (is_array($searchopt)) {
                  foreach ($searchopt as $key2 => $val2) {
                     // Linkfield or standard field not massive action enable
                     if ($val2["linkfield"] == $key || (empty ($val2["linkfield"]) && $key == $val2["field"])) {

                        $id_search_option = $key2; // Give ID of the $SEARCHOPTION
                        if ($val2["table"] == $item->getTable()) {
                           // 1st case : text field -> keep datas
                           $changes = array (
                              $id_search_option,
                              addslashes($oldval),
                              $values[$key]
                           );
                        } else {
                           // 2nd case ; link field -> get data from dropdown
                           $changes = array (
                              $id_search_option,
                              addslashes(Dropdown :: getDropdownName($val2["table"], $oldval)),
                              addslashes(Dropdown :: getDropdownName($val2["table"], $values[$key]))
                           );
                        }
                        break;
                     }
                  }
               }
            }
            if (count($changes)) {
               historyLog($real_id, $real_type, $changes);
            }
         }
      }
   } // function construct_history

}
?>
