<?php
/*
 * @version $Id: contract_item.class.php 9363 2009-11-26 21:02:42Z moyo $
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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

// Relation between Computer and Items (monitor, printer, phone, peripheral only)
class Computer_Item extends CommonDBRelation{

   // From CommonDBTM
   public $table = 'glpi_computers_items';
   public $type = 'Computer_Item';

   // From CommonDBRelation
   public $itemtype_1 = COMPUTER_TYPE;
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
                                  "`itemtype`='$itemtype'
                                       AND `items_id`='$items_id'");
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
   function can($ID,$right,&$input=NULL) {

      if ($ID<0) {
         // Ajout
         $item = new CommonItem();

         if (!$item->getFromDB($input['itemtype'],$input['items_id'])) {
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
         case MONITOR_TYPE :
            $item = new Monitor();
            $ocstab = 'import_monitor';
            break;

         case PHONE_TYPE :
            // shoul really never occurs as OCS doesn't sync phone
            $item = new Phone();
            $ocstab = '';
            break;

         case PRINTER_TYPE :
            $item = new Printer();
            $ocstab = 'import_printer';
            break;

         case PERIPHERAL_TYPE :
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
               deleteInOcsArray($data["computers_id"],$data["id"],$ocstab);
            }
         }

         // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
         $comp=new Computer();
         $comp->getFromDB($input['computers_id']);
         $updates = array();

         if ($CFG_GLPI["is_location_autoupdate"]
             && $comp->fields['locations_id'] != $item->getField('locations_id')){
            $updates[]="locations_id";
            $item->fields['locations_id']=addslashes($comp->fields['locations_id']);
            addMessageAfterRedirect($LANG['computers'][48],true);
         }
         if (($CFG_GLPI["is_user_autoupdate"]
              && $comp->fields['users_id'] != $item->getField('users_id'))
             || ($CFG_GLPI["is_group_autoupdate"]
                 && $comp->fields['groups_id'] != $item->getField('groups_id'))) {
            if ($CFG_GLPI["is_user_autoupdate"]) {
               $updates[]="users_id";
               $item->fields['users_id']=$comp->fields['users_id'];
            }
            if ($CFG_GLPI["is_group_autoupdate"]) {
               $updates[]="groups_id";
               $item->fields['groups_id']=$comp->fields['groups_id'];
            }
            addMessageAfterRedirect($LANG['computers'][50],true);
         }

         if ($CFG_GLPI["is_contact_autoupdate"]
             && ($comp->fields['contact'] != $item->getField('contact')
                 || $comp->fields['contact_num'] != $item->getField('contact_num'))) {
            $updates[]="contact";
            $updates[]="contact_num";
            $item->fields['contact']=addslashes($comp->fields['contact']);
            $item->fields['contact_num']=addslashes($comp->fields['contact_num']);
            addMessageAfterRedirect($LANG['computers'][49],true);
         }
         if ($CFG_GLPI["state_autoupdate_mode"]<0
             && $comp->fields['states_id'] != $item->getField('states_id')) {
            $updates[]="states_id";
            $item->fields['states_id']=$comp->fields['states_id'];
            addMessageAfterRedirect($LANG['computers'][56],true);
         }
         if ($CFG_GLPI["state_autoupdate_mode"]>0
             && $item->getField('states_id') != $CFG_GLPI["state_autoupdate_mode"]) {
            $updates[]="states_id";
            $item->fields['states_id']=$CFG_GLPI["state_autoupdate_mode"];
         }
         if (count($updates)) {
            $item->updateInDB($updates);
         }
      }
      return $input;
   }

   /**
    * Actions done when item is deleted from the database
    * Overloaded to manage autoupdate feature
    *
    *@param $ID ID of the item
    *
    *@return nothing
    **/
   function cleanDBonPurge($ID) {
      global $DB, $CFG_GLPI;

      if (!isset($this->input['_no_auto_action'])) {
         //Get the computer name
         $computer = new Computer;
         $computer->getFromDB($this->fields['computers_id']);

         //Get device fields
         $device=new CommonItem();
         $device->getFromDB($this->fields['itemtype'], $this->fields['items_id']);

         if (!$device->getField('is_global')) {
            $updates=array();
            if ($CFG_GLPI["is_location_autoclean"] && $device->getField('locations_id')) {
               $updates[]="locations_id";
               $device->obj->fields['locations_id']=0;
            }
            if ($CFG_GLPI["is_user_autoclean"] && $device->getField('users_id')) {
               $updates[]="users_id";
               $device->obj->fields['users_id']=0;
            }
            if ($CFG_GLPI["is_group_autoclean"] && $device->getField('groups_id')) {
               $updates[]="groups_id";
               $device->obj->fields['groups_id']=0;
            }
            if ($CFG_GLPI["is_contact_autoclean"] && $device->getField('contact')) {
               $updates[]="contact";
               $device->obj->fields['contact']="";
            }
            if ($CFG_GLPI["is_contact_autoclean"] && $device->getField('contact_num')) {
               $updates[]="contact_num";
               $device->obj->fields['contact_num']="";
            }
            if ($CFG_GLPI["state_autoclean_mode"]<0 && $device->getField('states_id')) {
               $updates[]="states_id";
               $device->obj->fields['states_id']=0;
            }
            if ($CFG_GLPI["state_autoclean_mode"]>0
                && $device->getField('states_id') != $CFG_GLPI["state_autoclean_mode"]) {
               $updates[]="states_id";
               $device->obj->fields['states_id']=$CFG_GLPI["state_autoclean_mode"];
            }
            if (count($updates)) {
               $device->obj->updateInDB($updates);
            }
         }
         if (isset($this->input['_ocsservers_id'])) {
            $ocsservers_id = $this->input['_ocsservers_id'];
         } else {
            $ocsservers_id = getOCSServerByMachineID($this->fields['computers_id']);
         }
         if ($ocsservers_id>0) {
            //Get OCS configuration
            $ocs_config = getOcsConf($ocsservers_id);

            //Get the management mode for this device
            $mode = getMaterialManagementMode($ocs_config, $this->fields['itemtype']);
            $decoConf= $ocs_config["deconnection_behavior"];

            //Change status if :
            // 1 : the management mode IS NOT global
            // 2 : a deconnection's status have been defined
            // 3 : unique with serial
            if($mode >= 2 && strlen($decoConf)>0) {
               //Delete periph from glpi
               if($decoConf == "delete") {
                  $tmp["id"]=$this->fields['items_id'];
                  $device->obj->delete($tmp);
               //Put periph in trash
               } else if ($decoConf == "trash") {
                  $tmp["id"]=$this->fields['items_id'];
                  $tmp["is_deleted"]=1;
                  $device->obj->update($tmp);
               }
            }
         } // $ocsservers_id>0
      }
   }

   /**
    * Print the computers or template local connections form.
    *
    * Print the form for computers or templates connections to printers, screens or peripherals
    *
    *@param $target
    *@param $ID integer: Computer or template ID
    *@param $withtemplate=''  boolean : Template or basic item.
    *
    *@return Nothing (call to classes members)
    *
    **/
   static function showForComputer($target, Computer $comp, $withtemplate='') {
      global $DB,$CFG_GLPI, $LANG,$INFOFORM_PAGES;

      $ci=new CommonItem;
      $used = array();
      $items=array(PRINTER_TYPE=>$LANG['computers'][39],
                   MONITOR_TYPE=>$LANG['computers'][40],
                   PERIPHERAL_TYPE=>$LANG['computers'][46],
                   PHONE_TYPE=>$LANG['computers'][55]);

      $ID = $comp->fields['id'];
      $canedit=$comp->can($ID,'w');

      foreach ($items as $itemtype => $title) {
         if (!haveTypeRight($itemtype,"r")) {
            unset($items[$itemtype]);
         }
      }
      if (count($items)){
         echo "<div class='center'><table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='".max(2,count($items))."'>".$LANG['connect'][0].":</th></tr>";

         echo "<tr>";
         $header_displayed=0;
         foreach ($items as $itemtype => $title) {
            if ($header_displayed==2) {
               break;
            }
            echo "<th>".$title.":</th>";
            $header_displayed++;
         }
         echo "</tr>";
         echo "<tr class='tab_bg_1'>";
         $items_displayed=0;
         foreach ($items as $itemtype=>$title) {
            if ($items_displayed==2) {
               echo "</tr><tr>";
               $header_displayed=0;
               foreach ($items as $tmp_title) {
                  if ($header_displayed>=2) {
                     echo "<th>".$tmp_title.":</th>";
                  }
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
               if ($resultnum>0) {
                  echo "<table width='100%'>";
                  for ($i=0; $i < $resultnum; $i++) {
                     $tID = $DB->result($result, $i, "items_id");
                     $connID = $DB->result($result, $i, "id");
                     $ci->getFromDB($itemtype,$tID);
                     $used[] = $tID;

                     echo "<tr ".($ci->getField('is_deleted')?"class='tab_bg_2_2'":"").">";
                     echo "<td class='center'><strong>";
                     echo $ci->getLink();
                     echo "</strong>";
                     echo " - ".getDropdownName("glpi_states",$ci->getField('state'));
                     echo "</td><td>".$ci->getField('serial');
                     echo "</td><td>".$ci->getField('otherserial');
                     echo "</td><td>";
                     if ($canedit && (empty($withtemplate) || $withtemplate != 2)) {
                        echo "<td class='center'>";
                        echo "<a href=\"".$CFG_GLPI["root_doc"].
                               "/front/computer.form.php?computers_id=$ID&amp;id=$connID&amp;" .
                               "disconnect=1&amp;withtemplate=".$withtemplate."\"><strong>";
                        echo $LANG['buttons'][10];
                        echo "</strong></a></td>";
                     }
                     echo "</tr>";
                  }
                  echo "</table>";
               } else {
                  switch ($itemtype) {
                     case PRINTER_TYPE :
                        echo $LANG['computers'][38];
                        break;

                     case MONITOR_TYPE:
                        echo $LANG['computers'][37];
                        break;

                     case PERIPHERAL_TYPE:
                        echo $LANG['computers'][47];
                        break;

                     case PHONE_TYPE:
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
                     dropdownConnect($itemtype,COMPUTER_TYPE,"items_id",$comp->fields["entities_id"],
                                     $withtemplate,$used);
                     echo "<input type='submit' name='connect' value=\"".$LANG['buttons'][9].
                          "\" class='submit'>";
                     echo "</form>";
                  }
               }
            }
            echo "</td>";
            $items_displayed++;
         }
         echo "</tr>";
         echo "</table></div><br>";
      }
   }

   /**
    * Prints a direct connection to a computer
    *
    * @param $target the page where we'll print out this.
    * @param $item the Monitor/Phone/Peripheral/Printer
    *
    * @return nothing (print out a table)
    *
    */
   static function showForItem(CommonDBTM $item) {
      // Prints a direct connection to a computer
      global $DB, $LANG, $CFG_GLPI;

      $comp = new Computer();
      $target = $comp->getFormURL();

      $ID = $item->getField('id');

      if (!$item->can($ID,"r")) {
         return false;
      }
      $canedit=$item->can($ID,"w");

      // Is global connection ?
      $global=$item->getField('is_global');

      $used = array();
      $compids = array();
      $crit = array('FIELDS'   => array('id', 'computers_id'),
                    'itemtype' => $item->type,
                    'items_id' => $ID);
      foreach ($DB->request('glpi_computers_items', $crit) as $data) {
         $compids[$data['id']] = $data['computers_id'];
      }

      echo "<br><div class='center'><table width='50%' class='tab_cadre'><tr><th colspan='2'>";
      echo $LANG['connect'][0]."&nbsp;: ".count($compids);
      echo "</th></tr>";

      if (count($compids)>0) {
         foreach ($compids as $key => $compid) {
            $comp->getFromDB($compid);
            echo "<tr><td class='b tab_bg_1".($comp->fields['is_deleted']?"_2":"")."'>";
            echo $LANG['help'][25]."&nbsp;: ".$comp->getLink()."</td>";
            echo "<td class='tab_bg_2".($comp->fields['is_deleted']?"_2":"")." center b'>";
            if ($canedit) {
               echo "<a href=\"$target?disconnect=1&amp;computers_id=$compid&amp;id=$key\">".
                    $LANG['buttons'][10]."</a>";
            } else {
               echo "&nbsp;";
            }
            $used[] = $compid;
         }
      } else {
         echo "<tr><td class='tab_bg_1'><strong>".$LANG['help'][25].": </strong>";
         echo "<i>".$LANG['connect'][1]."</i>";
         echo "</td>";
         echo "<td class='tab_bg_2' class='center'>";
         if ($canedit) {
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='items_id' value='$ID'>";
            echo "<input type='hidden' name='itemtype' value='".$item->type."'>";
            if ($item->getField('is_recursive')) {
               dropdownConnect(COMPUTER_TYPE, $item->type, "computers_id",
                               getSonsOf("glpi_entities",$item->getField('entities_id')),0,$used);
            } else {
               dropdownConnect(COMPUTER_TYPE, $item->type, "computers_id",
                               $item->getField('entities_id'),0,$used);
            }
            echo "<input type='submit' name='connect' value=\"".$LANG['buttons'][9]."\" class='submit'>";
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
            echo "<input type='hidden' name='itemtype' value='".$item->type."'>";
            if ($item->getField('is_recursive')) {
               dropdownConnect(COMPUTER_TYPE, $item->type, "computers_id",
                               getSonsOf("glpi_entities",$item->getField('entities_id')),0,$used);
            } else {
               dropdownConnect(COMPUTER_TYPE, $item->type, "computers_id",
                               $item->getField('entities_id'),0,$used);
            }
            echo "<input type='submit' name='connect' value=\"".$LANG['buttons'][9]."\" class='submit'>";
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
    *
    */
   static function unglobalizeItem(CommonDBTM $item) {
      global $DB;

      // Update item to unit management :
      if ($item->getField('is_global')) {
         $input=array('id'        => $item->fields['id'],
                      'is_global' => 0);
         $item->update($input);

         // Get connect_wire for this connection
         $query = "SELECT `glpi_computers_items`.`id`
                   FROM `glpi_computers_items`
                   WHERE `glpi_computers_items`.`items_id` = '".$item->fields['id']."'
                         AND `glpi_computers_items`.`itemtype` = '".$item->type."'";
         $result=$DB->query($query);

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
}

?>