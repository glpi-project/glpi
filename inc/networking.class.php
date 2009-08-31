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

// CLASSES Networking

class Netdevice extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {

      $this->table="glpi_networkequipments";
      $this->type=NETWORKING_TYPE;
      $this->dohistory=true;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID > 0) {
         $ong[1]=$LANG['title'][27];
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4]=$LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if (empty($withtemplate)) {
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
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   function prepareInputForAdd($input) {

      if (isset($input["id"])&&$input["id"]>0) {
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
         // ADD Infocoms
         $ic= new Infocom();
         if ($ic->getFromDBforDevice($this->type,$input["_oldID"])) {
            $ic->fields["items_id"]=$newID;
            unset ($ic->fields["id"]);
            if (isset($ic->fields["immo_number"])) {
               $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                     INFOCOM_TYPE ,$input['entities_id']);
            }
            if (empty($ic->fields['use_date'])) {
               unset($ic->fields['use_date']);
            }
            if (empty($ic->fields['buy_date'])) {
               unset($ic->fields['buy_date']);
            }
            $ic->addToDB();
         }
         // ADD Ports
         $query = "SELECT `id`
                   FROM `glpi_networkports`
                   WHERE `items_id` = '".$input["_oldID"]."'
                         AND `itemtype` = '".$this->type."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               $np= new Netport();
               $np->getFromDB($data["id"]);
               unset($np->fields["id"]);
               unset($np->fields["ip"]);
               unset($np->fields["mac"]);
               unset($np->fields["netpoints_id"]);
               $np->fields["items_id"]=$newID;
               $np->addToDB();
            }
         }
         // ADD Contract
         $query = "SELECT `contracts_id`
                   FROM `glpi_contracts_items`
                   WHERE `items_id` = '".$input["_oldID"]."'
                         AND `itemtype` = '".$this->type."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               addDeviceContract($data["contracts_id"],$this->type,$newID);
            }
         }
         // ADD Documents
         $query = "SELECT `documents_id`
                   FROM `glpi_documents_items`
                   WHERE `items_id` = '".$input["_oldID"]."'
                         AND `itemtype` = '".$this->type."'";
         $result=$DB->query($query);
         if ($DB->numrows($result)>0) {
            while ($data=$DB->fetch_array($result)) {
               addDeviceDocument($data["documents_id"],$this->type,$newID);
            }
         }
      }
   }

   function pre_deleteItem($ID) {
      removeConnector($ID);
      return true;
   }

   function cleanDBonPurge($ID) {
      global $DB,$CFG_GLPI;

      $job =new Job();
      $query = "SELECT *
                FROM `glpi_tickets`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);

      if ($DB->numrows($result)) {
         while ($data=$DB->fetch_array($result)) {
            if ($CFG_GLPI["keep_tickets_on_delete"]==1) {
               $query = "UPDATE
                         `glpi_tickets`
                         SET `items_id` = '0', `itemtype` = '0'
                         WHERE `id` = '".$data["id"]."'";
               $DB->query($query);
            } else {
               $job->delete(array("id"=>$data["id"]));
            }
         }
      }
      $query = "SELECT `id`
                FROM `glpi_networkports`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);
      while ($data = $DB->fetch_array($result)) {
         $q = "DELETE
               FROM `glpi_networkports_networkports`
               WHERE `networkports_id_1` = '".$data["id"]."'
                     OR `networkports_id_2` = '".$data["id"]."'";
         $result2 = $DB->query($q);
      }
      $query = "DELETE
                FROM `glpi_networkports`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);

      $query = "DELETE
                FROM `glpi_infocoms`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);

      $query = "DELETE
                FROM `glpi_contracts_items`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);

      $query = "SELECT *
                FROM `glpi_reservationsitems`
                WHERE `itemtype` = '".$this->type."'
                AND `items_id` = '$ID'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            $rr=new ReservationItem();
            $rr->delete(array("id"=>$DB->result($result,0,"id")));
         }
      }
   }

   /**
    * Can I change recusvive flag to false
    * check if there is "linked" object in another entity
    *
    * Overloaded from CommonDBTM
    *
    * @return booleen
    **/
   function canUnrecurs () {
      global $DB, $CFG_GLPI, $LINK_ID_TABLE;

      $ID = $this->fields['id'];
      if ($ID<0 || !$this->fields['is_recursive']) {
         return true;
      }
      if (!parent::canUnrecurs()) {
         return false;
      }
      $entities = "(".$this->fields['entities_id'];
      foreach (getAncestorsOf("glpi_entities",$this->fields['entities_id']) as $papa) {
         $entities .= ",$papa";
      }
      $entities .= ")";

      // RELATION : networking -> _port -> _wire -> _port -> device

      // Evaluate connection in the 2 ways
      for ($tabend=array("networkports_id_1"=>"networkports_id_2",
                         "networkports_id_2"=>"networkports_id_1");list($enda,$endb)=each($tabend);) {

         $sql = "SELECT `itemtype`, GROUP_CONCAT(DISTINCT `items_id`) AS ids
                 FROM `glpi_networkports_networkports`, `glpi_networkports`
                 WHERE `glpi_networkports_networkports`.`$endb` = `glpi_networkports`.`id`
                       AND `glpi_networkports_networkports`.`$enda`
                                 IN (SELECT `id`
                                     FROM `glpi_networkports`
                                     WHERE `itemtype` = ".$this->type."
                                           AND `items_id` = '$ID')
                 GROUP BY `itemtype`";

         $res = $DB->query($sql);
         if ($res) {
            while ($data = $DB->fetch_assoc($res)) {
               // For each itemtype which are entity dependant
               if (isset($LINK_ID_TABLE[$data["itemtype"]])
                   && in_array($table=$LINK_ID_TABLE[$data["itemtype"]],
                               $CFG_GLPI["specif_entities_tables"])) {

                  if (countElementsInTable("$table", "id IN (".$data["ids"].")
                                           AND entities_id NOT IN $entities")>0) {
                     return false;
                  }
               }
            }
         }
      }
      return true;
   }

   /**
    * Print the networking form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *@param $withtemplate integer template or basic item
    *
    *@return boolean item found
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      // Show device or blank form

      if (!haveRight("networking","r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      if (!empty($withtemplate) && $withtemplate == 2) {
         $template = "newcomp";
         $datestring = $LANG['computers'][14].": ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } elseif (!empty($withtemplate) && $withtemplate == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14].": ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26].": ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID, $withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->type,$this->fields["entities_id"]);
      autocompletionTextField("name",$this->table,"name",$objectName,40,
                              $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_networkequipmentstypes", "networkequipmentstypes_id",
                    $this->fields["networkequipmentstypes_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td>";
      echo "<td>".$LANG['setup'][71]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_networkequipmentsfirmwares", "networkequipmentsfirmwares_id",
                    $this->fields["networkequipmentsfirmwares_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][15]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"],1,
                    $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['networking'][5]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("ram",$this->table,"ram",$this->fields["ram"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>";
      echo "<td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],"interface",1,
                      $this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['setup'][88]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_networks", "networks_id", $this->fields["networks_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][21]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("contact_num",$this->table,"contact_num",
                              $this->fields["contact_num"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][22]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_networkequipmentsmodels", "networkequipmentsmodels_id",
                    $this->fields["networkequipmentsmodels_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][18]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("contact",$this->table,"contact",
                              $this->fields["contact"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("serial",$this->table,"serial",$this->fields["serial"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][34]."&nbsp;:</td>";
      echo "<td>";
      dropdownAllUsers("users_id", $this->fields["users_id"],1,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td>".$LANG['common'][20].($template?"*":"")."&nbsp;:</td>";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"),
                             $this->type,$this->fields["entities_id"]);
      autocompletionTextField("otherserial",$this->table,"otherserial",$objectName,40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][35]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"],1,
                    $this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='6'>";
      echo $LANG['common'][25]."&nbsp;:</td>";
      echo "<td rowspan='6'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_states", "states_id",$this->fields["states_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][89]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_domains", "domains_id", $this->fields["domains_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['networking'][14]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("ip",$this->table,"ip",$this->fields["ip"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['networking'][15]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("mac",$this->table,"mac",$this->fields["mac"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$datestring."&nbsp;".$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

}

/// Netport class
class Netport extends CommonDBTM {

   /// ID of the port connected to the current one
   var $contact_id		= 0;
   /// hardare data : name
   var $device_name	= "";
   /// hardare data : ID
   var $device_ID		= 0;
   /// hardare data : type
   var $itemtype		= 0;
   /// hardare data : entity
   var $entities_id		= -1;
   /// hardare data : locations_id
   var $locations_id		= -1;
   /// hardare data : is_recursive
   var $is_recursive = 0;
   /// hardare data : is_deleted
   var $is_deleted = 0;

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_networkports";
      $this->type = NETWORKING_PORT_TYPE;
   }

   function post_updateItem($input,$updates,$history=1) {

      // Only netpoint updates : ip and mac may be different.
      $tomatch=array("netpoints_id");
      $updates=array_intersect($updates,$tomatch);
      if (count($updates)) {
         $save_ID=$this->fields["id"];
         $n=new Netwire;
         if ($this->fields["id"]=$n->getOppositeContact($save_ID)) {
            $this->updateInDB($updates);
         }
         $this->fields["id"]=$save_ID;
      }
   }

   function prepareInputForUpdate($input) {

      // Is a preselected mac adress selected ?
      if (isset($input['pre_mac']) && !empty($input['pre_mac'])) {
         $input['mac']=$input['pre_mac'];
         unset($input['pre_mac']);
      }
      return $input;
   }

   function prepareInputForAdd($input) {

      if (isset($input["logical_number"]) && strlen($input["logical_number"])==0) {
         unset($input["logical_number"]);
      }
      return $input;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '$ID'
                      OR `networkports_id_2` = '$ID'";
      $result = $DB->query($query);
   }

   // SPECIFIC FUNCTIONS
   /**
    * Retrieve data in the port of the item which belongs to
    *
    *@param $ID Integer : Id of the item to print
    *@param $itemtype item type
    *
    *@return boolean item found
    **/
   function getDeviceData($ID, $itemtype) {
      global $DB,$LINK_ID_TABLE;

      $table = $LINK_ID_TABLE[$itemtype];

      $query = "SELECT *
                FROM `$table`
                WHERE `id` = '$ID'";
      if ($result=$DB->query($query)) {
         $data = $DB->fetch_array($result);
         $this->device_name = $data["name"];
         $this->is_deleted = $data["is_deleted"];
         $this->entities_id = $data["entities_id"];
         $this->locations_id = $data["locations_id"];
         $this->device_ID = $ID;
         $this->itemtype = $itemtype;
         $this->is_recursive = (isset($data["is_recursive"])?$data["is_recursive"]:0);
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get port opposite port ID if linked item
    * ID store in contact_id
    *@param $ID networking port ID
    *
    *@return boolean item found
    **/
   function getContact($ID) {

      $wire = new Netwire;
      if ($this->contact_id = $wire->getOppositeContact($ID)) {
         return true;
      } else {
         return false;
      }
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG, $CFG_GLPI;

      $ong[1] = $LANG['title'][26];
      return $ong;
   }

}

/// Netwire class
class Netwire {

   /// ID of the netwire
   var $ID = 0;
   /// first connected port ID
   var $networkports_id_1 = 0;
   /// second connected port ID
   var $networkports_id_2 = 0;

   /**
    * Get port opposite port ID
    *
    *@param $ID networking port ID
    *
    *@return integer ID of opposite port. false if not found
    **/
   function getOppositeContact ($ID) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_networkports_networkports`
                WHERE `networkports_id_1` = '$ID'
                      OR `networkports_id_2` = '$ID'";
      if ($result=$DB->query($query)) {
         $data = $DB->fetch_array($result);
         if (is_array($data)) {
            $this->networkports_id_1 = $data["networkports_id_1"];
            $this->networkports_id_2 = $data["networkports_id_2"];
         }
         if ($this->networkports_id_1 == $ID) {
            return $this->networkports_id_2;
         } else if ($this->networkports_id_2 == $ID) {
            return $this->networkports_id_1;
         } else {
            return false;
         }
      }
   }

}

?>
