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
 *  Computer class
 */
class Computer extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_computers';
   public $type = 'Computer';
   public $dohistory=true;
   public $entity_assign=true;

   // Specific ones
   ///Device container - format $device = array(ID,"device type","ID in device table","specificity value")
   var $devices	= array();

   static function getTypeName() {
      global $LANG;

      return $LANG['help'][25];
   }

   static function canCreate() {
      return haveRight('computer', 'w');
   }

   static function canView() {
      return haveRight('computer', 'r');
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      if ($ID>0) {
         $ong[1]=$LANG['title'][30];
         $ong[20]=$LANG['computers'][8];
         if (haveRight("software","r")) {
            $ong[2]=$LANG['Menu'][4];
         }
         if (haveRight("networking","r") || haveRight("printer","r") || haveRight("monitor","r")
             || haveRight("peripheral","r") || haveRight("phone","r")) {
            $ong[3]=$LANG['title'][27];
         }
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4]=$LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }

         if (empty($withtemplate)) {
            if ($CFG_GLPI["use_ocs_mode"]) {
               $ong[14]=$LANG['title'][43];
            }
            if (haveRight("show_all_ticket","1")) {
               $ong[6]=$LANG['title'][28];
            }
            if (haveRight("link","r")) {
               $ong[7]=$LANG['title'][34];
            }
            if (haveRight("notes","r")) {
               $ong[10]=$LANG['title'][37];
            }
            if (haveRight("reservation_central","r")) {
               $ong[11]=$LANG['Menu'][17];
            }

            $ong[12]=$LANG['title'][38];

            if ($CFG_GLPI["use_ocs_mode"]
                && (haveRight("sync_ocsng","w") ||haveRight("computer","w"))) {
               $ong[13]=$LANG['Menu'][33];
            }
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   /**
   * Retrieve an item from the database with device associated
   *
   *@param $ID ID of the item to get
   *@return true if succeed else false
   **/
   function getFromDBwithDevices ($ID) {
      global $DB;

      if ($this->getFromDB($ID)) {
         $query = "SELECT count(*) AS NB, `id`, `devicetype`, `devices_id`, `specificity`
                   FROM `glpi_computers_devices`
                   WHERE `computers_id` = '$ID'
                   GROUP BY `devicetype`, `devices_id`, `specificity`";
         if ($result = $DB->query($query)) {
            if ($DB->numrows($result)>0) {
               $i = 0;
               while($data = $DB->fetch_array($result)) {
                  $this->devices[$i] = array("compDevID"=>$data["id"],
                                             "devType"=>$data["devicetype"],
                                             "devID"=>$data["devices_id"],
                                             "specificity"=>$data["specificity"],
                                             "quantity"=>$data["NB"]);
                  $i++;
               }
            }
            return true;
         }
      }
      return false;
   }

   function post_updateItem($input,$updates,$history=1) {
      global $DB,$LANG,$CFG_GLPI;

      // Manage changes for OCS if more than 1 element (date_mod)
      // Need dohistory==1 if dohistory==2 no locking fields
      if ($this->fields["is_ocs_import"] && $history==1 && count($updates)>1) {
         OcsServer::mergeOcsArray($this->fields["id"],$updates,"computer_update");
      }

      if (isset($input["_auto_update_ocs"])){
         $query="UPDATE
                 `glpi_ocslinks`
                 SET `use_auto_update`='".$input["_auto_update_ocs"]."'
                 WHERE `computers_id`='".$input["id"]."'";
         $DB->query($query);
      }

      for ($i=0; $i < count($updates); $i++) {
         // Update contact of attached items
         if (($updates[$i]=="contact"  || $updates[$i]=="contact_num")
              && $CFG_GLPI["is_contact_autoupdate"]) {
            $items = array('Printer', 'Monitor', 'Peripheral', 'Phone');

            $update_done=false;
            $updates3[0]="contact";
            $updates3[1]="contact_num";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id`='".$this->fields["id"]."'
                               AND `itemtype`='".$t."'";
               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();
                  if ($resultnum>0) {
                     for ($j=0; $j < $resultnum; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('contact')!=$this->fields['contact']
                               || $item->getField('contact_num')!=$this->fields['contact_num']){
                              $tmp["id"]=$item->getField('id');
                              $tmp['contact']=$this->fields['contact'];
                              $tmp['contact_num']=$this->fields['contact_num'];
                              $item->update($tmp);
                              $update_done=true;
                           }
                        }
                     }
                  }
               }
            }

            if ($update_done) {
               addMessageAfterRedirect($LANG['computers'][49],true);
            }
         }

         // Update users and groups of attached items
         if (($updates[$i]=="users_id"  && $this->fields["users_id"]!=0
                                        && $CFG_GLPI["is_user_autoupdate"])
              ||($updates[$i]=="groups_id" && $this->fields["groups_id"]!=0
                                           && $CFG_GLPI["is_group_autoupdate"])) {

            $items = array('Printer', 'Monitor', 'Peripheral', 'Phone');

            $update_done=false;
            $updates4[0]="users_id";
            $updates4[1]="groups_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id`='".$this->fields["id"]."'
                               AND `itemtype`='".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();
                  if ($resultnum>0) {
                     for ($j=0; $j < $resultnum; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('users_id')!=$this->fields["users_id"]
                               ||$item->getField('groups_id')!=$this->fields["groups_id"]) {
                              $tmp["id"]=$item->getField('id');
                              if ($CFG_GLPI["is_user_autoupdate"]) {
                                 $tmp["users_id"]=$this->fields["users_id"];
                              }
                              if ($CFG_GLPI["is_group_autoupdate"]) {
                                 $tmp["groups_id"]=$this->fields["groups_id"];
                              }
                              $item->update($tmp);
                              $update_done=true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               addMessageAfterRedirect($LANG['computers'][50],true);
            }
         }

         // Update state of attached items
         if ($updates[$i]=="states_id" && $CFG_GLPI["state_autoupdate_mode"]<0) {
            $items = array('Printer', 'Monitor', 'Peripheral', 'Phone');
            $update_done=false;

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id`='".$this->fields["id"]."'
                               AND `itemtype`='".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();

                  if ($resultnum>0) {
                     for ($j=0; $j < $resultnum; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')){
                           if ($item->getField('states_id')!=$this->fields["states_id"]){
                              $tmp["id"]=$item->getField('id');
                              $tmp["states_id"]=$this->fields["states_id"];
                              $item->update($tmp);
                              $update_done=true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               addMessageAfterRedirect($LANG['computers'][56],true);
            }
         }

         // Update loction of attached items
         if ($updates[$i]=="locations_id" && $this->fields["locations_id"]!=0
                                          && $CFG_GLPI["is_location_autoupdate"]) {

            $items = array('Printer', 'Monitor', 'Peripheral', 'Phone');
            $update_done=false;
            $updates2[0]="locations_id";

            foreach ($items as $t) {
               $query = "SELECT *
                         FROM `glpi_computers_items`
                         WHERE `computers_id`='".$this->fields["id"]."'
                               AND `itemtype`='".$t."'";

               if ($result=$DB->query($query)) {
                  $resultnum = $DB->numrows($result);
                  $item = new $t();

                  if ($resultnum>0) {
                     for ($j=0; $j < $resultnum; $j++) {
                        $tID = $DB->result($result, $j, "items_id");
                        $item->getFromDB($tID);
                        if (!$item->getField('is_global')) {
                           if ($item->getField('locations_id')!=$this->fields["locations_id"]) {
                              $tmp["id"]=$item->getField('id');
                              $tmp["locations_id"]=$this->fields["locations_id"];
                              $item->update($tmp);
                              $update_done=true;
                           }
                        }
                     }
                  }
               }
            }
            if ($update_done) {
               addMessageAfterRedirect($LANG['computers'][48],true);
            }
         }
      }
   }

   function prepareInputForAdd($input) {

      if (isset($input["id"]) && $input["id"]>0) {
         $input["_oldID"]=$input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }

   function post_addItem($newID,$input) {
      global $DB;

      // Manage add from template
      if (isset($input["_oldID"])) {
         // ADD Devices
         $this->getFromDBwithDevices($input["_oldID"]);
         foreach($this->devices as $key => $val) {
            for ($i=0;$i<$val["quantity"];$i++) {
               compdevice_add($newID,$val["devType"],$val["devID"],$val["specificity"],0);
            }
         }

         // ADD Infocoms
         $ic= new Infocom();
         if ($ic->getFromDBforDevice($this->type,$input["_oldID"])) {
            $ic->fields["items_id"]=$newID;
            unset ($ic->fields["id"]);
            if (isset($ic->fields["immo_number"])) {
               $ic->fields["immo_number"] = autoName($ic->fields["immo_number"],
                                            "immo_number", 1, 'Infocom', $input['entities_id']);
            }
            if (empty($ic->fields['use_date'])) {
               unset($ic->fields['use_date']);
            }
            if (empty($ic->fields['buy_date'])) {
               unset($ic->fields['buy_date']);
            }
            $ic->addToDB();
         }

         // ADD volumes
         $query="SELECT `id`
                 FROM `glpi_computerdisks`
                 WHERE `computers_id`='".$input["_oldID"]."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $disk=new ComputerDisk();
               $disk->getfromDB($data['id']);
               unset($disk->fields["id"]);
               $disk->fields["computers_id"]=$newID;
               $disk->addToDB();
            }
         }

         // ADD software
         $query="SELECT `softwareversions_id`
                 FROM `glpi_computers_softwareversions`
                 WHERE `computers_id`='".$input["_oldID"]."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               installSoftwareVersion($newID,$data['softwareversions_id']);
            }
         }

         // ADD Contract
         $query="SELECT `contracts_id`
                 FROM `glpi_contracts_items`
                 WHERE `items_id`='".$input["_oldID"]."'
                       AND `itemtype`='".$this->type."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $contractitem=new Contract_Item();
            while ($data=$DB->fetch_array($result)) {
               $contractitem->add(array('contracts_id' => $data["contracts_id"],
                                        'itemtype' => $this->type,
                                        'items_id' => $newID));
            }
         }

         // ADD Documents
         $query="SELECT `documents_id`
                 FROM `glpi_documents_items`
                 WHERE `items_id`='".$input["_oldID"]."'
                       AND `itemtype`='".$this->type."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $docitem=new Document_Item();
            while ($data=$DB->fetch_array($result)) {
               $docitem->add(array('documents_id' => $data["documents_id"],
                                   'itemtype' => $this->type,
                                   'items_id' => $newID));
            }
         }

         // ADD Ports
         $query="SELECT `id`
                 FROM `glpi_networkports`
                 WHERE `items_id`='".$input["_oldID"]."'
                       AND `itemtype`='".$this->type."';";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $np= new NetworkPort();
               $np->getFromDB($data["id"]);
               unset($np->fields["id"]);
               unset($np->fields["ip"]);
               unset($np->fields["mac"]);
               unset($np->fields["netpoints_id"]);
               $np->fields["items_id"]=$newID;
               $portid=$np->addToDB();
               foreach ($DB->request('glpi_networkports_vlans',
                                     array('networkports_id'=>$data["id"])) as $vlan) {
                  assignVlan($portid, $vlan['vlans_id']);
               }
            }
         }

         // Add connected devices
         $query="SELECT *
                 FROM `glpi_computers_items`
                 WHERE `computers_id`='".$input["_oldID"]."';";

         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            $conn = new Computer_Item();
            while ($data=$DB->fetch_array($result)) {
               $conn->add(array('computers_id' => $newID,
                                'itemtype'     => $data["itemtype"],
                                'items_id'     => $data["items_id"]));
            }
         }
      }
   }

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI;

      $query = "DELETE
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$ID'";
      $result = $DB->query($query);

      $query="SELECT `id`
              FROM `glpi_computers_items`
              WHERE `computers_id`='$ID'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $conn = new Computer_Item();
            while ($data = $DB->fetch_array($result)) {
               $data['_no_auto_action']=true;
               $conn->delete($data);
            }
         }
      }

      $query = "DELETE
                FROM `glpi_registrykeys`
                WHERE `computers_id` = '$ID'";
      $result = $DB->query($query);

      $query = "DELETE
                FROM `glpi_computers_devices`
                WHERE `computers_id` = '$ID'";
      $result = $DB->query($query);

      $query = "DELETE
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$ID'";
      $result = $DB->query($query);

      $disk = new ComputerDisk();
      $disk->cleanDBonItemDelete($this->type, $ID);
   }

   /**
   * Print the computer form
   *
   * Print general computer form
   *
   *@param $target form target
   *@param $ID Integer : Id of the computer or the template to print
   *@param $withtemplate template or basic computer
   *
   *@return Nothing (display)
   *
   **/
   function showForm($target,$ID,$withtemplate='') {
      global $LANG,$CFG_GLPI,$DB;

      if (!haveRight("computer","r")) {
        return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      if(!empty($withtemplate) && $withtemplate == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14]." : ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } elseif(!empty($withtemplate) && $withtemplate == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14]." : ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26].": ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($ID, $withtemplate,getActiveTab($this->type));
      $this->showFormHeader($target, $ID, $withtemplate, 2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),$this->type,
                    $this->fields["entities_id"]);
      autocompletionTextField("name",$this->table,"name",$objectName,40,
                               $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_states", "states_id",$this->fields["states_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][15]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"],1,
                     $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][17]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_computertypes", "computertypes_id", $this->fields["computertypes_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;: </td>";
      echo "<td>";
      User::dropdownUsersID("users_id_tech",$this->fields["users_id_tech"],"interface",1,
                       $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][5]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_manufacturers","manufacturers_id", $this->fields["manufacturers_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][21]."&nbsp;: </td>";
      echo "<td >";
      autocompletionTextField("contact_num",$this->table,"contact_num",
                              $this->fields["contact_num"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][22]."&nbsp;: </td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_computermodels", "computermodels_id",$this->fields["computermodels_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][18]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("contact",$this->table,"contact",
                              $this->fields["contact"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>";
      echo "<td >";
      autocompletionTextField("serial",$this->table,"serial",$this->fields["serial"],40,
                               $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][34]."&nbsp;: </td>";
      echo "<td>";
      User::dropdownAllUsers("users_id", $this->fields["users_id"],1, $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][20].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"),
                             $this->type,$this->fields["entities_id"]);
      autocompletionTextField("otherserial",$this->table,"otherserial",$objectName,40,
                               $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][35]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"],1,
                     $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['setup'][88]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_networks", "networks_id", $this->fields["networks_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][89]."&nbsp;:</td>";
      echo "<td >";
      Dropdown::dropdownValue("glpi_domains", "domains_id", $this->fields["domains_id"]);
      echo "</td>";
      echo "<td rowspan='7'>".$LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='7' class='middle'>";
      echo "<textarea cols='45' rows='11' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][9]."&nbsp;:</td>";
      echo "<td>";
      Dropdown::dropdownValue("glpi_operatingsystems", "operatingsystems_id",
                     $this->fields["operatingsystems_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][53]."&nbsp;:</td>";
      echo "<td >";
      Dropdown::dropdownValue("glpi_operatingsystemservicepacks", "operatingsystemservicepacks_id",
                     $this->fields["operatingsystemservicepacks_id"]);
      echo "</td></tr>\n";


      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][52]."&nbsp;:</td>";
      echo "<td >";
      Dropdown::dropdownValue("glpi_operatingsystemversions", "operatingsystemversions_id",
                     $this->fields["operatingsystemversions_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][11]."&nbsp;:</td>";
      echo "<td >";
      autocompletionTextField("os_licenseid",$this->table,"os_licenseid",
                              $this->fields["os_licenseid"],40, $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['computers'][10]."&nbsp;:</td>";
      echo "<td >";
      autocompletionTextField("os_license_number",$this->table,"os_license_number",
                              $this->fields["os_license_number"],40, $this->fields["entities_id"]);
      echo "</td></tr>\n";

      // Get OCS Datas :
      $dataocs=array();
      if (!empty($ID) && $this->fields["is_ocs_import"] && haveRight("view_ocsng","r")) {
         $query = "SELECT *
                   FROM `glpi_ocslinks`
                   WHERE `computers_id`='$ID'";

         $result=$DB->query($query);
         if ($DB->numrows($result)==1) {
            $dataocs=$DB->fetch_array($result);
         }
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center'>".$datestring.$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      if (!empty($ID) && $this->fields["is_ocs_import"] && haveRight("view_ocsng","r")
          && count($dataocs)) {
         echo "<br>";
         echo $LANG['ocsng'][14]."&nbsp;: ".convDateTime($dataocs["last_ocs_update"]);
         echo "<br>";
         echo $LANG['ocsng'][13]."&nbsp;: ".convDateTime($dataocs["last_update"]);
         echo "<br>";
         if (haveRight("ocsng","r")) {
            echo $LANG['common'][52]." <a href='".$CFG_GLPI["root_doc"]."/front/ocsng.form.php?id="
                 .OcsServer::getByMachineID($ID)."'>".OcsServer::getServerNameByID($ID)."</a>";
            $query = "SELECT `ocs_agent_version`, `ocsid`
                      FROM `glpi_ocslinks`
                      WHERE `computers_id` = '$ID'";
            $result_agent_version = $DB->query($query);
            $data_version = $DB->fetch_array($result_agent_version);

            $ocs_config = OcsServer::getConfig(OcsServer::getByMachineID($ID));

            //If have write right on OCS and ocsreports url is not empty in OCS config
            if (haveRight("ocsng","w") && $ocs_config["ocs_url"] != '') {
               echo ", ".OcsServer::getComputerLinkToOcsConsole (OcsServer::getByMachineID($ID),
               $data_version["ocsid"],$LANG['ocsng'][57]);
            }

            if ($data_version["ocs_agent_version"] != NULL) {
               echo " , ".$LANG['ocsng'][49]."&nbsp;: ".$data_version["ocs_agent_version"];
            }
         } else {
            echo $LANG['common'][52]." ".OcsServer::getServerNameByID($ID);
         }
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      if (!empty($ID) && $this->fields["is_ocs_import"] && haveRight("view_ocsng","r")
          && haveRight("sync_ocsng","w") && count($dataocs)) {
         echo "<td >".$LANG['ocsng'][6]." ".$LANG['Menu'][33]."&nbsp;:</td>";
         echo "<td >";
         Dropdown::showYesNo("_auto_update_ocs",$dataocs["use_auto_update"]);
         echo "</td>";
      } else {
         echo "<td colspan=2></td>";
      }
      echo "<td>".$LANG['computers'][51]."&nbsp;:</td>";
      echo "<td >";
      Dropdown::dropdownValue("glpi_autoupdatesystems", "autoupdatesystems_id",
                     $this->fields["autoupdatesystems_id"]);
      echo "</td></tr>";


      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /**
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   function getSelectLinkedItem () {
      return "SELECT `itemtype`, `items_id`
              FROM `glpi_computers_items`
              WHERE `computers_id`='" . $this->fields['id']."'";
   }

   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['common'][32];

      $tab[1]['table']         = 'glpi_computers';
      $tab[1]['field']         =  'name';
      $tab[1]['linkfield']     ='name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = 'Computer';

      $tab[2]['table']     = 'glpi_computers';
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = 'glpi_locations';
      $tab[3]['field']     = 'completename';
      $tab[3]['linkfield'] = 'locations_id';
      $tab[3]['name']      = $LANG['common'][15];

      $tab[4]['table']     = 'glpi_computertypes';
      $tab[4]['field']     = 'name';
      $tab[4]['linkfield'] = 'computertypes_id';
      $tab[4]['name']      = $LANG['common'][17];

      $tab[40]['table']     = 'glpi_computermodels';
      $tab[40]['field']     = 'name';
      $tab[40]['linkfield'] = 'computermodels_id';
      $tab[40]['name']      = $LANG['common'][22];

      $tab[31]['table']     = 'glpi_states';
      $tab[31]['field']     = 'name';
      $tab[31]['linkfield'] = 'states_id';
      $tab[31]['name']      = $LANG['state'][0];

      $tab[45]['table']     = 'glpi_operatingsystems';
      $tab[45]['field']     = 'name';
      $tab[45]['linkfield'] = 'operatingsystems_id';
      $tab[45]['name']      = $LANG['computers'][9];

      $tab[46]['table']     = 'glpi_operatingsystemversions';
      $tab[46]['field']     = 'name';
      $tab[46]['linkfield'] = 'operatingsystemversions_id';
      $tab[46]['name']      = $LANG['computers'][52];

      $tab[41]['table']     = 'glpi_operatingsystemservicepacks';
      $tab[41]['field']     = 'name';
      $tab[41]['linkfield'] = 'operatingsystemservicepacks_id';
      $tab[41]['name']      = $LANG['computers'][53];

      $tab[42]['table']     = 'glpi_autoupdatesystems';
      $tab[42]['field']     = 'name';
      $tab[42]['linkfield'] = 'autoupdatesystems_id';
      $tab[42]['name']      = $LANG['computers'][51];

      $tab[43]['table']     = 'glpi_computers';
      $tab[43]['field']     = 'os_license_number';
      $tab[43]['linkfield'] = 'os_license_number';
      $tab[43]['name']      = $LANG['computers'][10];

      $tab[44]['table']     = 'glpi_computers';
      $tab[44]['field']     = 'os_licenseid';
      $tab[44]['linkfield'] = 'os_licenseid';
      $tab[44]['name']      = $LANG['computers'][11];

      $tab[5]['table']     = 'glpi_computers';
      $tab[5]['field']     = 'serial';
      $tab[5]['linkfield'] = 'serial';
      $tab[5]['name']      = $LANG['common'][19];

      $tab[6]['table']     = 'glpi_computers';
      $tab[6]['field']     = 'otherserial';
      $tab[6]['linkfield'] = 'otherserial';
      $tab[6]['name']      = $LANG['common'][20];

      $tab[16]['table']     = 'glpi_computers';
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      $tab[90]['table']     = 'glpi_computers';
      $tab[90]['field']     = 'notepad';
      $tab[90]['linkfield'] = '';
      $tab[90]['name']      = $LANG['title'][37];

      $tab[17]['table']     = 'glpi_computers';
      $tab[17]['field']     = 'contact';
      $tab[17]['linkfield'] = 'contact';
      $tab[17]['name']      = $LANG['common'][18];

      $tab[18]['table']     = 'glpi_computers';
      $tab[18]['field']     = 'contact_num';
      $tab[18]['linkfield'] = 'contact_num';
      $tab[18]['name']      = $LANG['common'][21];

      $tab[70]['table']     = 'glpi_users';
      $tab[70]['field']     = 'name';
      $tab[70]['linkfield'] = 'users_id';
      $tab[70]['name']      = $LANG['common'][34];

      $tab[71]['table']     = 'glpi_groups';
      $tab[71]['field']     = 'name';
      $tab[71]['linkfield'] = 'groups_id';
      $tab[71]['name']      = $LANG['common'][35];

      $tab[19]['table']     = 'glpi_computers';
      $tab[19]['field']     = 'date_mod';
      $tab[19]['linkfield'] = '';
      $tab[19]['name']      = $LANG['common'][26];
      $tab[19]['datatype']  = 'datetime';

      $tab[32]['table']     = 'glpi_networks';
      $tab[32]['field']     = 'name';
      $tab[32]['linkfield'] = 'networks_id';
      $tab[32]['name']      = $LANG['setup'][88];

      $tab[33]['table']     = 'glpi_domains';
      $tab[33]['field']     = 'name';
      $tab[33]['linkfield'] = 'domains_id';
      $tab[33]['name']      = $LANG['setup'][89];

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


      $tab['periph'] = $LANG['title'][30];

      $tab[7]['table']        = 'glpi_deviceprocessors';
      $tab[7]['field']        = 'designation';
      $tab[7]['linkfield']    = '';
      $tab[7]['name']         = $LANG['computers'][21];
      $tab[7]['forcegroupby'] = true;
      $tab[7]['usehaving']    = true;

      $tab[36]['table']        = 'glpi_deviceprocessors';
      $tab[36]['field']        = 'specif_default';
      $tab[36]['linkfield']    = '';
      $tab[36]['name']         = $LANG['computers'][21]." ".$LANG['setup'][35];
      $tab[36]['forcegroupby'] = true;
      $tab[36]['usehaving']    = true;
      $tab[36]['datatype']     = 'number';
      $tab[36]['width']        = 100;

      $tab[10]['table']        = 'glpi_devicememories';
      $tab[10]['field']        = 'designation';
      $tab[10]['linkfield']    = '';
      $tab[10]['name']         = $LANG['computers'][23];
      $tab[10]['forcegroupby'] = true;
      $tab[10]['usehaving']    = true;

      $tab[35]['table']        = 'glpi_devicememories';
      $tab[35]['field']        = 'specif_default';
      $tab[35]['linkfield']    = '';
      $tab[35]['name']         = $LANG['computers'][24];
      $tab[35]['forcegroupby'] = true;
      $tab[35]['usehaving']    = true;
      $tab[35]['datatype']     = 'number';
      $tab[35]['width']        = 100;

      $tab[11]['table']        = 'glpi_devicenetworkcards';
      $tab[11]['field']        = 'designation';
      $tab[11]['linkfield']    = '';
      $tab[11]['name']         = $LANG['setup'][9];
      $tab[11]['forcegroupby'] = true;

      $tab[12]['table']        = 'glpi_devicesoundcards';
      $tab[12]['field']        = 'designation';
      $tab[12]['linkfield']    = '';
      $tab[12]['name']         = $LANG['devices'][7];
      $tab[12]['forcegroupby'] = true;

      $tab[13]['table']        = 'glpi_devicegraphiccards';
      $tab[13]['field']        = 'designation';
      $tab[13]['linkfield']    = '';
      $tab[13]['name']         = $LANG['devices'][2];
      $tab[13]['forcegroupby'] = true;

      $tab[14]['table']        = 'glpi_devicemotherboards';
      $tab[14]['field']        = 'designation';
      $tab[14]['linkfield']    ='';
      $tab[14]['name']         = $LANG['devices'][5];
      $tab[14]['forcegroupby'] = true;

      $tab[15]['table']        = 'glpi_deviceharddrives';
      $tab[15]['field']        = 'designation';
      $tab[15]['linkfield']    = '';
      $tab[15]['name']         = $LANG['computers'][36];
      $tab[15]['forcegroupby'] = true;
      $tab[15]['usehaving']    = true;

      $tab[34]['table']        = 'glpi_deviceharddrives';
      $tab[34]['field']        = 'specif_default';
      $tab[34]['linkfield']    = '';
      $tab[34]['name']         = $LANG['computers'][25];
      $tab[34]['forcegroupby'] = true;
      $tab[34]['usehaving']    = true;
      $tab[34]['datatype']     = 'number';
      $tab[34]['width']        = 1000;

      $tab[39]['table']        = 'glpi_devicepowersupplies';
      $tab[39]['field']        = 'designation';
      $tab[39]['linkfield']    = '';
      $tab[39]['name']         = $LANG['devices'][23];
      $tab[39]['forcegroupby'] = true;
      $tab[39]['usehaving']    = true;


      $tab['disk'] = $LANG['computers'][8];

      $tab[156]['table']        = 'glpi_computerdisks';
      $tab[156]['field']        = 'name';
      $tab[156]['linkfield']    = '';
      $tab[156]['name']         = $LANG['common'][16]." ".$LANG['computers'][0];
      $tab[156]['forcegroupby'] = true;

      $tab[150]['table']        = 'glpi_computerdisks';
      $tab[150]['field']        = 'totalsize';
      $tab[150]['linkfield']    = '';
      $tab[150]['name']         = $LANG['computers'][3];
      $tab[150]['forcegroupby'] = true;
      $tab[150]['usehaving']    = true;
      $tab[150]['datatype']     = 'number';
      $tab[150]['width']        = 1000;

      $tab[151]['table']        = 'glpi_computerdisks';
      $tab[151]['field']        = 'freesize';
      $tab[151]['linkfield']    = '';
      $tab[151]['name']         = $LANG['computers'][2];
      $tab[151]['forcegroupby'] = true;
      $tab[151]['usehaving']    = true;
      $tab[151]['datatype']     = 'number';
      $tab[151]['width']        = 1000;

      $tab[152]['table']        = 'glpi_computerdisks';
      $tab[152]['field']        = 'freepercent';
      $tab[152]['linkfield']    = '';
      $tab[152]['name']         = $LANG['computers'][1];
      $tab[152]['forcegroupby'] = true;
      $tab[152]['usehaving']    = true;
      $tab[152]['datatype']     = 'decimal';
      $tab[152]['width']        = 2;
      $tab[152]['computation']  = "ROUND(100*TABLE.freesize/TABLE.totalsize)";
      $tab[152]['unit']         = '%';

      $tab[153]['table']        = 'glpi_computerdisks';
      $tab[153]['field']        = 'mountpoint';
      $tab[153]['linkfield']    = '';
      $tab[153]['name']         = $LANG['computers'][5];
      $tab[153]['forcegroupby'] = true;

      $tab[154]['table']        = 'glpi_computerdisks';
      $tab[154]['field']        = 'device';
      $tab[154]['linkfield']    = '';
      $tab[154]['name']         = $LANG['computers'][6];
      $tab[154]['forcegroupby'] = true;

      $tab[155]['table']        = 'glpi_filesystems';
      $tab[155]['field']        = 'name';
      $tab[155]['linkfield']    = '';
      $tab[155]['name']         = $LANG['computers'][4];
      $tab[155]['forcegroupby'] = true;


      $tab['network'] = $LANG['setup'][88];

      $tab[20]['table']        = 'glpi_networkports';
      $tab[20]['field']        = 'ip';
      $tab[20]['linkfield']    = '';
      $tab[20]['name']         = $LANG['networking'][14];
      $tab[20]['forcegroupby'] = true;

      $tab[21]['table']        = 'glpi_networkports';
      $tab[21]['field']        = 'mac';
      $tab[21]['linkfield']    = '';
      $tab[21]['name']         = $LANG['networking'][15];
      $tab[21]['forcegroupby'] = true;

      $tab[83]['table']        = 'glpi_networkports';
      $tab[83]['field']        = 'netmask';
      $tab[83]['linkfield']    = '';
      $tab[83]['name']         = $LANG['networking'][60];
      $tab[83]['forcegroupby'] = true;

      $tab[84]['table']        = 'glpi_networkports';
      $tab[84]['field']        = 'subnet';
      $tab[84]['linkfield']    = '';
      $tab[84]['name']         = $LANG['networking'][61];
      $tab[84]['forcegroupby'] = true;

      $tab[85]['table']        = 'glpi_networkports';
      $tab[85]['field']        = 'gateway';
      $tab[85]['linkfield']    = '';
      $tab[85]['name']         = $LANG['networking'][59];
      $tab[85]['forcegroupby'] = true;

      $tab[22]['table']        = 'glpi_netpoints';
      $tab[22]['field']        = 'name';
      $tab[22]['linkfield']    = '';
      $tab[22]['name']         = $LANG['networking'][51];
      $tab[22]['forcegroupby'] = true;


      $tab['tracking'] = $LANG['title'][24];

      $tab[60]['table']        = 'glpi_tickets';
      $tab[60]['field']        = 'count';
      $tab[60]['linkfield']    = '';
      $tab[60]['name']         = $LANG['stats'][13];
      $tab[60]['forcegroupby'] = true;
      $tab[60]['usehaving']    = true;
      $tab[60]['datatype']     = 'number';



      $tab['ocsng'] = $LANG['Menu'][33];

      $tab[102]['table']     = 'glpi_ocslinks';
      $tab[102]['field']     = 'last_update';
      $tab[102]['linkfield'] = '';
      $tab[102]['name']      = $LANG['ocsng'][13];
      $tab[102]['datatype']  = 'datetime';

      $tab[103]['table']     = 'glpi_ocslinks';
      $tab[103]['field']     = 'last_ocs_update';
      $tab[103]['linkfield'] = '';
      $tab[103]['name']      = $LANG['ocsng'][14];
      $tab[103]['datatype']  = 'datetime';

      $tab[100]['table']     = 'glpi_computers';
      $tab[100]['field']     = 'is_ocs_import';
      $tab[100]['linkfield'] = '';
      $tab[100]['name']      = $LANG['ocsng'][7];

      $tab[101]['table']     = 'glpi_ocslinks';
      $tab[101]['field']     = 'use_auto_update';
      $tab[101]['linkfield'] = '';
      $tab[101]['name']      = $LANG['ocsng'][6]." ".$LANG['Menu'][33];


      $tab['registry'] = $LANG['title'][43];

      $tab[110]['table']        = 'glpi_registrykeys';
      $tab[110]['field']        = 'value';
      $tab[110]['linkfield']    = '';
      $tab[110]['name']         = $LANG['title'][43]." : ".$LANG['registry'][3];
      $tab[110]['forcegroupby'] = true;

      $tab[111]['table']        = 'glpi_registrykeys';
      $tab[111]['field']        = 'ocs_name';
      $tab[111]['linkfield']    = '';
      $tab[111]['name']         = $LANG['title'][43]." : ".$LANG['registry'][6];
      $tab[111]['forcegroupby'] = true;

      return $tab;
   }
}

?>