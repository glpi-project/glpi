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

// CLASSES Printers

class Printer  extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_printers";
      $this->type=PRINTER_TYPE;
      $this->dohistory=true;
      $this->entity_assign=true;
      $this->may_be_recursive=true;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG,$CFG_GLPI;

      $ong=array();
      if ($ID > 0 ) {
         if (haveRight("cartridge","r")) {
            $ong[1]=$LANG['Menu'][21];
         }
         if (haveRight("networking","r") || haveRight("computer","r")) {
            $ong[3]=$LANG['title'][27];
         }
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

      // RELATION : printers -> _port -> _wire -> _port -> device

      // Evaluate connection in the 2 ways
      for ($tabend=array("networkports_id_1"=>"networkports_id_2",
                         "networkports_id_2"=>"networkports_id_1");
           list($enda,$endb)=each($tabend);) {

         $sql = "SELECT `itemtype`, GROUP_CONCAT(DISTINCT `items_id`) AS ids
                 FROM `glpi_networkports_networkports`, `glpi_networkports`
                 WHERE `glpi_networkports_networkports`.$endb = `glpi_networkports`.`id`
                       AND `glpi_networkports_networkports`.$enda IN (SELECT `id`
                                                                      FROM `glpi_networkports`
                                                                      WHERE `itemtype`=".$this->type."
                                                                            AND `items_id`='$ID')
                 GROUP BY `itemtype`";
         $res = $DB->query($sql);

         if ($res) {
            while ($data = $DB->fetch_assoc($res)) {
               // For each itemtype which are entity dependant
               if (isset($LINK_ID_TABLE[$data["itemtype"]])
                   && in_array($table=$LINK_ID_TABLE[$data["itemtype"]],
                                      $CFG_GLPI["specif_entities_tables"])) {

                  if (countElementsInTable("$table", "`id` IN (".$data["ids"].")
                                           AND `entities_id` NOT IN $entities")>0) {
                     return false;
                  }
               }
            }
         }
      }
      return true;
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
         // ADD Infocoms
         $ic= new Infocom();
         if ($ic->getFromDBforDevice($this->type,$input["_oldID"])) {
            $ic->fields["items_id"]=$newID;
            unset ($ic->fields["id"]);
            if (isset($ic->fields["immo_number"])) {
               $ic->fields["immo_number"] = autoName($ic->fields["immo_number"], "immo_number", 1,
                                                     INFOCOM_TYPE,$input['entities_id']);
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
                         WHERE `id` = '".$data["id"]."';";
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

      $query2 = "DELETE
                 FROM `glpi_networkports`
                 WHERE `items_id` = '$ID'
                       AND `itemtype` = '".$this->type."'";
      $result2 = $DB->query($query2);

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `itemtype` = '".$this->type."'
                      AND `items_id` = '$ID'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)>0) {
            while ($data = $DB->fetch_array($result)) {
               // Disconnect without auto actions
               Disconnect($data["id"],1,false);
            }
         }
      }

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

      $query = "DELETE
                FROM `glpi_contracts_items`
                WHERE `items_id` = '$ID'
                      AND `itemtype` = '".$this->type."'";
      $result = $DB->query($query);

      $query = "UPDATE
                `glpi_cartridges`
                SET `printers_id` = NULL
                WHERE `printers_id` = '$ID'";
      $result = $DB->query($query);

      // For infocom...
      parent::cleanDBonPurge($ID);
   }

   /**
    * Print the printer form
    *
    *@param $target string: where to go when done.
    *@param $ID integer: Id of the item to print
    *@param $withtemplate integer: template or basic item
    *
     *@return boolean item found
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      if (!haveRight("printer","r")) {
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
         $datestring = $LANG['computers'][14]."&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } elseif (!empty($withtemplate) && $withtemplate == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14]."&nbsp;: ";
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26]."&nbsp;: ";
         $date = convDateTime($this->fields["date_mod"]);
         $template = false;
      }

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16].($template?"*":"")."&nbsp;:</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["name"], "name", ($template === "newcomp"),
                             $this->type,$this->fields["entities_id"]);
      autocompletionTextField("name",$this->table,"name",$objectName,40,$this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['peripherals'][33]."&nbsp;:</td>";
      echo "<td>";
      if ($this->can($ID,'w')) {
         globalManagementDropdown($target,$withtemplate,$this->fields["id"],$this->fields["is_global"],
                                  $CFG_GLPI["printers_management_restrict"]);
      } else {
         // Use printers_management_restrict to disallow change this
         globalManagementDropdown($target,$withtemplate,$this->fields["id"],$this->fields["is_global"],
                                  $this->fields["is_global"]);
      }
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][15]."&nbsp;: </td>\n";
      echo "<td>";
      dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"],1,
                    $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_printerstypes", "printerstypes_id", $this->fields["printerstypes_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],"interface",1,
                      $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['devices'][6]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("memory_size",$this->table,"memory_size",$this->fields["memory_size"],
                              40,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['printers'][30]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("init_pages_counter",$this->table,"init_pages_counter",
                              $this->fields["init_pages_counter"],40,$this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][21]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("contact_num",$this->table,"contact_num",$this->fields["contact_num"],
                              40,$this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['setup'][88]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_networks", "networks_id", $this->fields["networks_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][18]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("contact",$this->table,"contact",$this->fields["contact"],40,
                              $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][22]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_printersmodels", "printersmodels_id", $this->fields["printersmodels_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][34]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownAllUsers("users_id", $this->fields["users_id"],1,$this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("serial",$this->table,"serial",$this->fields["serial"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][35]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"],1,
                    $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][20].($template?"*":"")."&nbsp;:</td>\n";
      echo "<td>";
      $objectName = autoName($this->fields["otherserial"], "otherserial", ($template === "newcomp"),
                             $this->type,$this->fields["entities_id"]);
      autocompletionTextField("otherserial",$this->table,"otherserial",$objectName,40,
                              $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_states", "states_id",$this->fields["states_id"]);
      echo "</td>\n";
      echo "<td rowspan='4'>";
      echo $LANG['common'][25]."&nbsp;:</td>\n";
      echo "<td rowspan='4'><textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['setup'][89]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_domains", "domains_id", $this->fields["domains_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['printers'][18]."&nbsp;: </td>";
      echo "<td>\n<table>";
      // serial interface
      echo "<tr><td>".$LANG['printers'][14]."</td><td>";
      dropdownYesNo("have_serial",$this->fields["have_serial"]);
      echo "</td></tr>";
      // parallel interface?
      echo "<tr><td>".$LANG['printers'][15]."</td><td>";
      dropdownYesNo("have_parallel",$this->fields["have_parallel"]);
      echo "</td></tr>";
      // USB ?
      echo "<tr><td>".$LANG['printers'][27]."</td><td>";
      dropdownYesNo("have_usb",$this->fields["have_usb"]);
      echo "</td></tr></table>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td colspan='2' class='center' height='30'>".$datestring."&nbsp;".$date;
      if (!$template && !empty($this->fields['template_name'])) {
         echo "&nbsp;&nbsp;&nbsp;(".$LANG['common'][13]."&nbsp;: ".$this->fields['template_name'].")";
      }
      echo "</td></tr>\n";

      $this->showFormButtons($ID,$withtemplate,2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }

   /*
    * Return the SQL command to retrieve linked object
    *
    * @return a SQL command which return a set of (itemtype, items_id)
    */
   function getSelectLinkedItem () {

      return "SELECT '".COMPUTER_TYPE."', `computers_id`
              FROM `glpi_computers_items`
              WHERE `itemtype` = '".$this->type."'
                    AND `items_id` = '" . $this->fields['id']."'";
   }
}

?>