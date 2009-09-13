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

// CLASSES peripherals

class Peripheral  extends CommonDBTM {

   /**
    * Constructor
   **/
   function __construct () {
      $this->table="glpi_peripherals";
      $this->type=PERIPHERAL_TYPE;
      $this->dohistory=true;
      $this->entity_assign=true;
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID > 0) {
         if (haveRight("computer","r")) {
            $ong[1]=$LANG['title'][27];
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
                         AND `itemtype` = '".$this->type."';";
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
                         AND `itemtype` = '".$this->type."';";
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
                         AND `itemtype` = '".$this->type."';";
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
   }

   /**
    * Print the peripheral form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the item to print
    *@param $withtemplate integer template or basic item
    *
    *@return boolean item found
    **/
   function showForm ($target,$ID,$withtemplate='') {
      global $CFG_GLPI, $LANG;

      if (!haveRight("peripheral","r")) {
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
         $datestring = $LANG['computers'][14];
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } elseif(!empty($withtemplate) && $withtemplate == 1) {
         $template = "newtemplate";
         $datestring = $LANG['computers'][14];
         $date = convDateTime($_SESSION["glpi_currenttime"]);
      } else {
         $datestring = $LANG['common'][26];
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
      echo "<td>".$LANG['peripherals'][33]."&nbsp;:</td>\n";
      echo "<td>";
      globalManagementDropdown($target,$withtemplate,$this->fields["id"],$this->fields["is_global"],
                               $CFG_GLPI["peripherals_management_restrict"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][15]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_locations", "locations_id", $this->fields["locations_id"],1,
                    $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][17]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_peripheralstypes", "peripheralstypes_id",
                    $this->fields["peripheralstypes_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],"interface",1,
                      $this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['peripherals'][18]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("brand",$this->table,"brand",$this->fields["brand"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][22]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_peripheralsmodels", "peripheralsmodels_id",
                    $this->fields["peripheralsmodels_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][21]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("contact_num",$this->table,"contact_num",$this->fields["contact_num"],
                              40,$this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td>".$LANG['common'][19]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("serial",$this->table,"serial",$this->fields["serial"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][18]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("contact",$this->table,"contact",$this->fields["contact"],40,
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
      echo "<td>".$LANG['common'][34]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownAllUsers("users_id", $this->fields["users_id"],1,$this->fields["entities_id"]);
      echo "</td>\n";
      echo "<td rowspan='4'>";
      echo $LANG['common'][25]."&nbsp;:</td>\n";
      echo "<td rowspan='4'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][35]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_groups", "groups_id", $this->fields["groups_id"],1,
                    $this->fields["entities_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['state'][0]."&nbsp;:</td>\n";
      echo "<td>";
      dropdownValue("glpi_states", "states_id",$this->fields["states_id"]);
      echo "</td></tr>\n";

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