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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

/**
 * Log  history
 *
 * @param $items_id
 * @param $itemtype
 * @param $changes
 * @param $itemtype_link
 * @param $linked_action
 **/
function historyLog ($items_id,$itemtype,$changes,$itemtype_link='',$linked_action='0') {
   global $DB;

   $date_mod=$_SESSION["glpi_currenttime"];
   if (!empty($changes)) {

      // create a query to insert history
      $id_search_option=$changes[0];
      $old_value=$changes[1];
      $new_value=$changes[2];

      if (isset($_SESSION["glpiID"])) {
         if (is_numeric($_SESSION["glpiID"])) {
            $username = getUserName($_SESSION["glpiID"],$link=0);
         } else { // For cron management
            $username=$_SESSION["glpiID"];
         }
      } else {
         $username="";
      }

      // Build query
      $query = "INSERT INTO
                `glpi_logs` (`items_id`, `itemtype`, `itemtype_link`, `linked_action`, `user_name`,
                             `date_mod`, `id_search_option`, `old_value`, `new_value`)
                VALUES ('$items_id', '$itemtype', '$itemtype_link', '$linked_action','".
                        addslashes($username)."', '$date_mod', '$id_search_option', '".
                        utf8_substr($old_value,0,250)."', '".utf8_substr($new_value,0,250)."')";
      $DB->query($query);
   }
}

/**
 * Construct  history for device
 *
 * @param $items_id ID of the device
 * @param $itemtype ID of the device type
 * @param $oldvalues old values updated
 * @param $values all values of the item
 **/
function constructHistory($items_id,$itemtype,&$oldvalues,&$values) {
   global $LANG ;

   if (count($oldvalues)) {
      // needed to have  $SEARCHOPTION
      foreach ($oldvalues as $key => $oldval) {
         $changes=array();
         // Parsing $SEARCHOPTION to find infocom
         if ($itemtype == 'Infocom') {
            $ic=new Infocom();
            if ($ic->getFromDB($values['id'])) {
               $real_type=$ic->fields['itemtype'];
               $items_id=$ic->fields['items_id'];

               $searchopt=Search::getOptions($real_type);
               if (is_array($searchopt)) {
                  foreach($searchopt as $key2 => $val2) {
                     if (($val2["field"]==$key && strpos($val2['table'],'infocoms'))
                         || ($key=='budgets_id' && $val2['table']=='glpi_budgets')
                         || ($key=='suppliers_id' && $val2['table']=='glpi_suppliers_infocoms')) {

                        $id_search_option=$key2; // Give ID of the $SEARCHOPTION
                        if ($val2["table"]=="glpi_infocoms") {
                           // 1st case : text field -> keep datas
                           $changes=array($id_search_option,
                                          addslashes($oldval),
                                          $values[$key]);
                        } else if ($val2["table"]=="glpi_suppliers_infocoms") {
                           // 2nd case ; link field -> get data from glpi_suppliers
                           $changes=array($id_search_option,
                                          addslashes(Dropdown::getDropdownName("glpi_suppliers",$oldval)),
                                          addslashes(Dropdown::getDropdownName("glpi_suppliers",$values[$key])));
                        } else  {
                           // 3rd case ; link field -> get data from dropdown (budget)
                           $changes=array($id_search_option,
                                          addslashes(Dropdown::getDropdownName( $val2["table"],$oldval)),
                                          addslashes(Dropdown::getDropdownName( $val2["table"],$values[$key])));
                        }
                     break; // foreach exit
                     }
                  }
               }
            }
         } else {
            $real_type=$itemtype;
            // Parsing $SEARCHOPTION, check if an entry exists matching $key
            $searchopt=Search::getOptions($real_type);
            if (is_array($searchopt)) {
               foreach($searchopt as $key2 => $val2) {
                  // Linkfield or standard field not massive action enable
                  if ($val2["linkfield"]==$key
                      || (empty($val2["linkfield"]) && $key == $val2["field"])){

                     $id_search_option=$key2; // Give ID of the $SEARCHOPTION
                     if ($val2["table"]==getTableForItemType($itemtype)) {
                        // 1st case : text field -> keep datas
                        $changes=array($id_search_option,
                                       addslashes($oldval),
                                       $values[$key]);
                     } else {
                        // 2nd case ; link field -> get data from dropdown
                        $changes=array($id_search_option,
                                       addslashes(Dropdown::getDropdownName( $val2["table"],$oldval)),
                                       addslashes(Dropdown::getDropdownName( $val2["table"],$values[$key])));
                     }
                     break;
                  }
               }
            }
         }
         if (count($changes)) {
            historyLog ($items_id,$real_type,$changes);
         }
      }
   }
} // function construct_history

/**
 * Show History
 **
 * Show history for a device
 *
 * @param $items_id
 * @param $itemtype
 **/
function showHistory($itemtype,$items_id) {
   global $DB,$LANG;

   $SEARCHOPTION=Search::getOptions($itemtype);
   if (isset($_REQUEST["start"])) {
      $start = $_REQUEST["start"];
   } else {
      $start = 0;
   }

   // Total Number of events
   $number = countElementsInTable("glpi_logs", "`items_id`='$items_id' AND `itemtype`='$itemtype'");

   // No Events in database
   if ($number < 1) {
      echo "<div class='center'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th>".$LANG['event'][20]."</th></tr>";
      echo "</table>";
      echo "</div><br>";
      return;
   }

   // Display the pager
   printAjaxPager($LANG['title'][38],$start,$number);

   $query="SELECT *
           FROM `glpi_logs`
           WHERE `items_id`='".$items_id."'
                 AND `itemtype`='".$itemtype."'
           ORDER BY `id` DESC
           LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

   // Get results
   $result = $DB->query($query);

   // Output events
   echo "<div class='center'><table class='tab_cadre_fixe'>";
   echo "<tr><th>".$LANG['common'][2]."</th><th>".$LANG['common'][27]."</th>";
   echo "<th>".$LANG['common'][34]."</th><th>".$LANG['event'][18]."</th>";
   echo "<th>".$LANG['event'][19]."</th></tr>";
   while ($data =$DB->fetch_array($result)) {
      $display_history = true;
      $ID = $data["id"];
      $date_mod=convDateTime($data["date_mod"]);
      $user_name = $data["user_name"];
      $field="";
      // This is an internal device ?
      if ($data["linked_action"]) {
         // Yes it is an internal device
         switch ($data["linked_action"]) {
            case HISTORY_DELETE_ITEM :
               $change = $LANG['log'][22];
               break;

            case HISTORY_RESTORE_ITEM :
               $change = $LANG['log'][23];
               break;

            case HISTORY_ADD_DEVICE :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['devices'][25]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["new_value"]."\"";
               break;

            case HISTORY_UPDATE_DEVICE :
               $field = NOT_AVAILABLE;
               $change = '';
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
                  $change = $item->getSpecifityLabel()."&nbsp;<strong>:</strong>&nbsp;";
               }
               $change .= $data[ "old_value"]."&nbsp;<strong>--></strong>&nbsp;"."\"".
                          $data[ "new_value"]."\"";
               break;

            case HISTORY_DELETE_DEVICE :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['devices'][26]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["old_value"]."\"";
               break;

            case HISTORY_INSTALL_SOFTWARE :
               $field=$LANG['help'][31];
               $change = $LANG['software'][44]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["new_value"]."\"";
               break;

            case HISTORY_UNINSTALL_SOFTWARE :
               $field=$LANG['help'][31];
               $change = $LANG['software'][45]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["old_value"]."\"";
               break;

            case HISTORY_DISCONNECT_DEVICE :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['log'][26]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["old_value"]."\"";
               break;

            case HISTORY_CONNECT_DEVICE :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['log'][27]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["new_value"]."\"";
               break;

            case HISTORY_OCS_IMPORT :
               if (haveRight("view_ocsng","r")) {
                  $field="";
                  $change = $LANG['ocsng'][7]." ".$LANG['ocsng'][45]."&nbsp;<strong>:</strong>";
                  $change.= "&nbsp;"."\"".$data["new_value"]."\"";
               } else {
                  $display_history = false;
               }
               break;

            case HISTORY_OCS_DELETE :
               if (haveRight("view_ocsng","r")) {
                  $field="";
                  $change = $LANG['ocsng'][46]." ".$LANG['ocsng'][45]."&nbsp;<strong>:</strong>";
                  $change.= "&nbsp;"."\"".$data["old_value"]."\"";
               } else {
                  $display_history = false;
               }
               break;

            case HISTORY_OCS_LINK :
               if (haveRight("view_ocsng","r")) {
                  $field=NOT_AVAILABLE;
                  if (class_exists($data["itemtype_link"])) {
                     $item = new $data["itemtype_link"]();
                     $field = $item->getTypeName();
                  }

                  $change = $LANG['ocsng'][47]." ".$LANG['ocsng'][45]."&nbsp;<strong>:</strong>";
                  $change.= "&nbsp;"."\"".$data["new_value"]."\"";
               } else {
                  $display_history = false;
               }
               break;

            case HISTORY_OCS_IDCHANGED :
               if (haveRight("view_ocsng","r")) {
                  $field="";
                  $change = $LANG['ocsng'][48]." "."&nbsp;<strong>:</strong>&nbsp;"."\"".
                            $data["old_value"]."\" --> &nbsp;<strong>:</strong>&nbsp;"."\"".
                            $data["new_value"]."\"";
               } else {
                  $display_history = false;
               }
               break;

            case HISTORY_LOG_SIMPLE_MESSAGE :
               $field="";
               $change = $data["new_value"];
               break;

            case HISTORY_ADD_RELATION :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['log'][32]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["new_value"]."\"";
               break;

            case HISTORY_DEL_RELATION :
               $field=NOT_AVAILABLE;
               if (class_exists($data["itemtype_link"])) {
                  $item = new $data["itemtype_link"]();
                  $field = $item->getTypeName();
               }
               $change = $LANG['log'][33]."&nbsp;<strong>:</strong>&nbsp;"."\"".
                         $data["old_value"]."\"";
               break;
         }

      } else {
         $fieldname="";
         // It's not an internal device
         foreach($SEARCHOPTION as $key2 => $val2) {
            if ($key2==$data["id_search_option"]) {
               $field= $val2["name"];
               $fieldname=$val2["field"];
            }
         }
         switch ($fieldname) {
            case "comment" :
               $change =$LANG['log'][64];
               break;

            case "notepad" :
               $change =$LANG['log'][67];
               break;

            default :
               $change = "\"".$data[ "old_value"]."\"&nbsp;<strong>--></strong>&nbsp;\"".
                         $data[ "new_value"]."\"";
         }
      }// fin du else

      if ($display_history) {
         // show line
         echo "<tr class='tab_bg_2'>";
         echo "<td>$ID</td><td>$date_mod</td><td>$user_name</td><td>$field</td>";
         echo "<td width='60%'>$change</td></tr>";
      }
   }
   echo "</table></div>";
}

?>
