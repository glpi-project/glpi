<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
class Computer_Item extends CommonDBRelation{

   // From CommonDBRelation
   public $itemtype_1 = 'Computer';
   public $items_id_1 = 'computers_id';

   public $itemtype_2 = 'itemtype';
   public $items_id_2 = 'items_id';

   /**
    * Count connection for an item
    *
    * @param $itemtype integer type of the item
    * @param $items_id integer ID of the item
    *
    * @return integer: count
    */
   static function countForItem($itemtype, $items_id) {
      return countElementsInTable('glpi_computers_items',
                                  "`itemtype`='$itemtype' AND `items_id`='$items_id'");
   }


   /**
    * Check right on an item - overloaded to check is_global
    *
    * @param $ID ID of the item (-1 if new item)
    * @param $right Right to check : r / w / recursive
    * @param $input array of input data (used for adding item)
    *
    * @return boolean
   **/
   function can($ID, $right, &$input=NULL) {

      if ($ID<0) {
         // Ajout
         if (!class_exists($input['itemtype'])) {
            return false;
         }
         $item = new $input['itemtype']();

         if (!$item->getFromDB($input['items_id'])) {
            return false;
         }
         if ($item->getField('is_global')==0
             &&  $this->countForItem($input['itemtype'],$input['items_id'])> 0) {
               return false;
         }
      }
      return parent::can($ID,$right,$input);
   }


   /**
   * Prepare input datas for adding the relation
   *
   * Overloaded to check is Disconnect needed (during OCS sync)
   * and to manage autoupdate feature
   *
   *@param $input datas used to add the item
   *
   *@return the modified $input array
   *
   **/
   function prepareInputForAdd($input) {
      global $DB, $CFG_GLPI, $LANG;

      switch ($input['itemtype']) {
         case 'Monitor' :
            $item = new Monitor();
            $ocstab = 'import_monitor';
            break;

         case 'Phone' :
            // shoul really never occurs as OCS doesn't sync phone
            $item = new Phone();
            $ocstab = '';
            break;

         case 'Printer' :
            $item = new Printer();
            $ocstab = 'import_printer';
            break;

         case 'Peripheral' :
            $item = new Peripheral();
            $ocstab = 'import_peripheral';
            break;

         default :
            return false;
      }
      if (!$item->getFromDB($input['items_id'])) {
         return false;
      }
      if (!$item->getField('is_global') ) {
         // Handle case where already used, should never happen (except from OCS sync)
         $query = "SELECT `id`, `computers_id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$input['items_id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$input['itemtype']."'";
         $result = $DB->query($query);

         while ($data=$DB->fetch_assoc($result)) {
            $temp = clone $this;
            $temp->delete($data);
            if ($ocstab) {
               OcsServer::deleteInOcsArray($data["computers_id"], $data["id"],$ocstab);
            }
         }

         // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
         $comp = new Computer();
         if ($comp->getFromDB($input['computers_id'])) {
            $updates = array();

            if ($CFG_GLPI["is_location_autoupdate"]
               && $comp->fields['locations_id'] != $item->getField('locations_id')){

               $updates[] = "locations_id";
               $item->fields['locations_id'] = addslashes($comp->fields['locations_id']);
               addMessageAfterRedirect($LANG['computers'][48], true);
            }
            if (($CFG_GLPI["is_user_autoupdate"]
                 && $comp->fields['users_id'] != $item->getField('users_id'))
                || ($CFG_GLPI["is_group_autoupdate"]
                    && $comp->fields['groups_id'] != $item->getField('groups_id'))) {

               if ($CFG_GLPI["is_user_autoupdate"]) {
                  $updates[] = "users_id";
                  $item->fields['users_id'] = $comp->fields['users_id'];
               }
               if ($CFG_GLPI["is_group_autoupdate"]) {
                  $updates[] = "groups_id";
                  $item->fields['groups_id'] = $comp->fields['groups_id'];
               }
               addMessageAfterRedirect($LANG['computers'][50],true);
            }

            if ($CFG_GLPI["is_contact_autoupdate"]
                && ($comp->fields['contact'] != $item->getField('contact')
                    || $comp->fields['contact_num'] != $item->getField('contact_num'))) {

               $updates[] = "contact";
               $updates[] = "contact_num";
               $item->fields['contact']     = addslashes($comp->fields['contact']);
               $item->fields['contact_num'] = addslashes($comp->fields['contact_num']);
               addMessageAfterRedirect($LANG['computers'][49], true);
            }

            if ($CFG_GLPI["state_autoupdate_mode"]<0
                && $comp->fields['states_id'] != $item->getField('states_id')) {

               $updates[] = "states_id";
               $item->fields['states_id'] = $comp->fields['states_id'];
               addMessageAfterRedirect($LANG['computers'][56], true);
            }

            if ($CFG_GLPI["state_autoupdate_mode"]>0
                && $item->getField('states_id') != $CFG_GLPI["state_autoupdate_mode"]) {

               $updates[] = "states_id";
               $item->fields['states_id'] = $CFG_GLPI["state_autoupdate_mode"];
            }

            if (count($updates)) {
               $item->updateInDB($updates);
            }
         }
      }
      return $input;
   }


   /**
    * Actions done when item is deleted from the database
    * Overloaded to manage autoupdate feature
    *
    *@return nothing
    **/
   function cleanDBonPurge() {
      global $CFG_GLPI;

      if (!isset($this->input['_no_auto_action'])) {
         //Get the computer name
         $computer = new Computer;
         $computer->getFromDB($this->fields['computers_id']);

         //Get device fields
         if (class_exists($this->fields['itemtype'])) {
            $device = new $this->fields['itemtype']();
            if ($device->getFromDB($this->fields['items_id'])) {

               if (!$device->getField('is_global')) {
                  $updates = array();
                  if ($CFG_GLPI["is_location_autoclean"] && $device->isField('locations_id')) {
                     $updates[] = "locations_id";
                     $device->fields['locations_id'] = 0;
                  }
                  if ($CFG_GLPI["is_user_autoclean"] && $device->isField('users_id')) {
                     $updates[] = "users_id";
                     $device->fields['users_id'] = 0;
                  }
                  if ($CFG_GLPI["is_group_autoclean"] && $device->isField('groups_id')) {
                     $updates[] = "groups_id";
                     $device->fields['groups_id'] = 0;
                  }
                  if ($CFG_GLPI["is_contact_autoclean"] && $device->isField('contact')) {
                     $updates[] = "contact";
                     $device->fields['contact'] = "";
                  }
                  if ($CFG_GLPI["is_contact_autoclean"] && $device->isField('contact_num')) {
                     $updates[] = "contact_num";
                     $device->fields['contact_num'] = "";
                  }
                  if ($CFG_GLPI["state_autoclean_mode"]<0 && $device->isField('states_id')) {
                     $updates[] = "states_id";
                     $device->fields['states_id'] = 0;
                  }

                  if ($CFG_GLPI["state_autoclean_mode"]>0
                      && $device->isField('states_id')
                      && $device->getField('states_id') != $CFG_GLPI["state_autoclean_mode"]) {

                     $updates[] = "states_id";
                     $device->fields['states_id'] = $CFG_GLPI["state_autoclean_mode"];
                  }

                  if (count($updates)) {
                     $device->updateInDB($updates);
                  }
               }

               if (isset($this->input['_ocsservers_id'])) {
                  $ocsservers_id = $this->input['_ocsservers_id'];
               } else {
                  $ocsservers_id = OcsServer::getByMachineID($this->fields['computers_id']);
               }

               if ($ocsservers_id>0) {
                  //Get OCS configuration
                  $ocs_config = OcsServer::getConfig($ocsservers_id);

                  //Get the management mode for this device
                  $mode = OcsServer::getDevicesManagementMode($ocs_config,
                                                              $this->fields['itemtype']);
                  $decoConf= $ocs_config["deconnection_behavior"];

                  //Change status if :
                  // 1 : the management mode IS NOT global
                  // 2 : a deconnection's status have been defined
                  // 3 : unique with serial
                  if ($mode >= 2 && strlen($decoConf)>0) {
                     //Delete periph from glpi
                     if ($decoConf == "delete") {
                        $tmp["id"] = $this->fields['items_id'];
                        $device->delete($tmp);

                     //Put periph in trash
                     } else if ($decoConf == "trash") {
                        $tmp["id"] = $this->fields['items_id'];
                        $tmp["is_deleted"] = 1;
                        $device->update($tmp);
                     }
                  }
               } // $ocsservers_id>0
            }
         }
      }
   }


   /**
   * Disconnect an item to its computer
   *
   * @param $item the Monitor/Phone/Peripheral/Printer
   *
   * @return boolean : action succeeded
   */
   function disconnectForItem(CommonDBTM $item) {
      global $DB;

      if ($item->getField('id')) {
         $query = "SELECT `id`
                   FROM `glpi_computers_items`
                   WHERE `itemtype` = '".$item->getType()."'
                         AND `items_id` = '".$item->getField('id')."'";
         $result = $DB->query($query);

         if ($DB->numrows($result) > 0) {
            $ok = true;
            while ($data = $DB->fetch_assoc($result)) {
               if ($this->can($data["id"],'w')) {
                  $ok &= $this->delete($data);
               }
            }
            return $ok;
         }
      }
      return false;
   }


   /**
   * Print the computers or template local connections form.
   *
   * Print the form for computers or templates connections to printers, screens or peripherals
   *
   *@param $target
   *@param $comp Computer object
   *@param $withtemplate=''  boolean : Template or basic item.
   *
   *@return Nothing (call to classes members)
   **/
   static function showForComputer($target, Computer $comp, $withtemplate='') {
      global $DB, $CFG_GLPI, $LANG;

      $items = array('Printer'    => $LANG['computers'][39],
                     'Monitor'    => $LANG['computers'][40],
                     'Peripheral' => $LANG['computers'][46],
                     'Phone'      => $LANG['computers'][55]);

      $ID = $comp->fields['id'];
      $canedit = $comp->can($ID,'w');

      foreach ($items as $itemtype => $title) {
         if (!class_exists($itemtype)) {
            unset($items[$itemtype]);
         }
         $item = new $itemtype();
         if (!$item->canView()) {
            unset($items[$itemtype]);
         }
      }
      if (count($items)) {
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='".max(2,count($items))."'>".$LANG['connect'][0]."&nbsp;:</th></tr>";

         echo "<tr class='tab_bg_1'>";
         $items_displayed = 0;
         $nbperline=2;
         foreach ($items as $itemtype=>$title) {
            $used = array();

            // Line change
            if ($items_displayed%$nbperline==0) {
               // Begin case 
               if ($items_displayed!=0) {
                     echo "</tr>";
               }
               echo "<tr>";
               $count = 0;
               $header_displayed=0;
               foreach ($items as $tmp_title) {
                  if ($count>=$items_displayed 
                        && $header_displayed <$nbperline) {
                     echo "<th>".$tmp_title."&nbsp;:</th>";
                     $header_displayed++;
                  }
                  $count++;
               }
               // Add header if line not complete
               while ($header_displayed%$nbperline!=0) {
                  echo "<th>&nbsp;</th>";
                  $header_displayed++;
               }
               echo "</tr><tr class='tab_bg_1'>";
            }

            echo "<td class='center'>";
            $query = "SELECT *
                      FROM `glpi_computers_items`
                      WHERE `computers_id` = '$ID'
                            AND `itemtype` = '".$itemtype."'";

            if ($result=$DB->query($query)) {
               $resultnum = $DB->numrows($result);
               $item = new $itemtype();
               if ($resultnum>0) {
                  echo "<table width='100%'>";
                  for ($i=0; $i < $resultnum; $i++) {
                     $tID = $DB->result($result, $i, "items_id");
                     $connID = $DB->result($result, $i, "id");

                     $item->getFromDB($tID);
                     $used[] = $tID;

                     echo "<tr ".($item->isDeleted()?"class='tab_bg_2_2'":"").">";
                     echo "<td class='center'><strong>".$item->getLink()."</strong>";
                     echo " - ".Dropdown::getDropdownName("glpi_states", $item->getField('state'));
                     echo "</td><td>".$item->getField('serial');
                     echo "</td><td>".$item->getField('otherserial');
                     echo "</td><td>";
                     if ($canedit && (empty($withtemplate) || $withtemplate != 2)) {
                        echo "<td class='center'>";
                        echo "<a href=\"".$CFG_GLPI["root_doc"].
                               "/front/computer.form.php?computers_id=$ID&amp;id=$connID&amp;" .
                               "disconnect=1&amp;withtemplate=".$withtemplate."\">";
                        echo "<strong>".$LANG['buttons'][10]."</strong></a></td>";
                     }
                     echo "</tr>";
                  }
                  echo "</table>";

               } else {
                  switch ($itemtype) {
                     case 'Printer' :
                        echo $LANG['computers'][38];
                        break;

                     case 'Monitor' :
                        echo $LANG['computers'][37];
                        break;

                     case 'Peripheral' :
                        echo $LANG['computers'][47];
                        break;

                     case 'Phone' :
                        echo $LANG['computers'][54];
                        break;
                  }
                  echo "<br>";
               }
               if ($canedit) {
                  if(empty($withtemplate) || $withtemplate != 2) {
                     echo "<form method='post' action=\"$target\">";
                     echo "<input type='hidden' name='computers_id' value='$ID'>";
                     echo "<input type='hidden' name='itemtype' value='".$itemtype."'>";
                     if (!empty($withtemplate)) {
                        echo "<input type='hidden' name='_no_history' value='1'>";
                     }
                     Computer_Item::dropdownConnect($itemtype, 'Computer', "items_id",
                                                    $comp->fields["entities_id"], $withtemplate,
                                                    $used);
                     echo "<input type='submit' name='connect' value='".$LANG['buttons'][9]."'
                            class='submit'>";
                     echo "</form>";
                  }
               }
            }
            echo "</td>";
            $items_displayed++;
         }
         while ($items_displayed%$nbperline!=0) {
            echo "<td>&nbsp;</td>";
            $items_displayed++;
         }
         echo "</tr>";
         echo "</table></div><br>";
      }
   }


   /**
   * Prints a direct connection to a computer
   *
   * @param $item the Monitor/Phone/Peripheral/Printer
   *
   * @return nothing (print out a table)
   */
   static function showForItem(CommonDBTM $item) {
      // Prints a direct connection to a computer
      global $DB, $LANG;

      $comp = new Computer();
      $target = $comp->getFormURL();

      $ID = $item->getField('id');

      if (!$item->can($ID,"r")) {
         return false;
      }
      $canedit = $item->can($ID,"w");

      // Is global connection ?
      $global=$item->getField('is_global');

      $used = array();
      $compids = array();
      $crit = array('FIELDS'   => array('id', 'computers_id'),
                    'itemtype' => $item->getType(),
                    'items_id' => $ID);
      foreach ($DB->request('glpi_computers_items', $crit) as $data) {
         $compids[$data['id']] = $data['computers_id'];
      }

      echo "<br><div class='center'><table width='50%' class='tab_cadre'>";
      echo "<tr><th colspan='2'>".$LANG['connect'][0]."&nbsp;: ".count($compids)."</th></tr>";

      if (count($compids)>0) {
         foreach ($compids as $key => $compid) {
            $comp->getFromDB($compid);
            echo "<tr><td class='b tab_bg_1".($comp->getField('is_deleted')?"_2":"")."'>";
            echo $LANG['help'][25]."&nbsp;: ".$comp->getLink()."</td>";
            echo "<td class='tab_bg_2".($comp->getField('is_deleted')?"_2":"")." center b'>";
            if ($canedit) {
               echo "<a href=\"$target?disconnect=1&amp;computers_id=$compid&amp;id=$key\">".
                      $LANG['buttons'][10]."</a>";
            } else {
               echo "&nbsp;";
            }
            $used[] = $compid;
         }

      } else {
         echo "<tr><td class='tab_bg_1 b'>".$LANG['help'][25]."&nbsp;:";
         echo "<i>".$LANG['connect'][1]."</i></td>";
         echo "<td class='tab_bg_2' class='center'>";
         if ($canedit) {
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
            if ($item->isRecursive()) {
               Computer_Item::dropdownConnect('Computer', $item->getType(), "computers_id",
                                              getSonsOf("glpi_entities", $item->getEntityID()), 0,
                                                        $used);
            } else {
               Computer_Item::dropdownConnect('Computer', $item->getType(), "computers_id",
                                              $item->getEntityID(), 0, $used);
            }
            echo "<input type='submit' name='connect' value='".$LANG['buttons'][9]."'
                   class='submit'>";
            echo "</form>";
         } else {
            echo "&nbsp;";
         }
      }

      if ($global && count($compids)>0) {
         echo "</td></tr>";
         echo "<tr><td class='tab_bg_1'>&nbsp;</td>";
         echo "<td class='tab_bg_2' class='center'>";
         if ($canedit) {
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='".$item->getType()."'>";
            if ($item->isRecursive()) {
               Computer_Item::dropdownConnect('Computer', $item->getType(), "computers_id",
                                              getSonsOf("glpi_entities", $item->getEntityID()), 0,
                                                        $used);
            } else {
               Computer_Item::dropdownConnect('Computer', $item->getType(), "computers_id",
                                              $item->getEntityID(), 0, $used);
            }
            echo "<input type='submit' name='connect' value='".$LANG['buttons'][9]."'
                   class='submit'>";
            echo "</form>";
         } else {
            echo "&nbsp;";
         }
      }
      echo "</td></tr>";
      echo "</table></div><br>";
   }


   /**
    * Unglobalize an item : duplicate item and connections
    *
    * @param $item object to unglobalize
    */
   static function unglobalizeItem(CommonDBTM $item) {
      global $DB;

      // Update item to unit management :
      if ($item->getField('is_global')) {
         $input = array('id'        => $item->fields['id'],
                        'is_global' => 0);
         $item->update($input);

         // Get connect_wire for this connection
         $query = "SELECT `glpi_computers_items`.`id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->fields['id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->getType()."'";
         $result = $DB->query($query);

         if ($data=$DB->fetch_array($result)) {
            // First one, keep the existing one

            // The others = clone the existing object
            unset($input['id']);
            $conn = new self();
            while ($data=$DB->fetch_array($result)) {
               $temp = clone $item;
               unset($temp->fields['id']);
               if ($newID=$temp->add($temp->fields)) {
                  $conn->update(array('id'       => $data['id'],
                                      'items_id' => $newID));
               }
            }
         }
      }
   }


   /**
   * Make a select box for connections
   *
   * @param $itemtype type to connect
   * @param $fromtype from where the connection is
   * @param $myname select name
   * @param $entity_restrict Restrict to a defined entity
   * @param $onlyglobal display only global devices (used for templates)
   * @param $used Already used items ID: not to display in dropdown
   *
   * @return nothing (print out an HTML select box)
   */
   static function dropdownConnect($itemtype, $fromtype, $myname, $entity_restrict=-1,
                                   $onlyglobal=0, $used=array()) {
      global $CFG_GLPI;

      $rand = mt_rand();

      $use_ajax = false;
      if ($CFG_GLPI["use_ajax"]) {
         $nb = 0;
         if ($entity_restrict>=0) {
            $nb = countElementsInTableForEntity(getTableForItemType($itemtype), $entity_restrict);
         } else {
            $nb = countElementsInTableForMyEntities(getTableForItemType($itemtype));
         }
         if ($nb>$CFG_GLPI["ajax_limit_count"]) {
            $use_ajax = true;
         }
      }

      $params = array('searchText'     => '__VALUE__',
                     'fromtype'        => $fromtype,
                     'idtable'         => $itemtype,
                     'myname'          => $myname,
                     'onlyglobal'      => $onlyglobal,
                     'entity_restrict' => $entity_restrict,
                     'used'            => $used);

      $default = "<select name='$myname'><option value='0'>".DROPDOWN_EMPTY_VALUE."</option>
                  </select>\n";
      ajaxDropdown($use_ajax, "/ajax/dropdownConnect.php", $params, $default, $rand);

      return $rand;
   }
}

?>