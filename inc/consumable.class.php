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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

//!  ConsumableType Class
/**
 * This class is used to manage the various types of consumables.
 * @see Consumable
 * @author Julien Dombre
 */
class ConsumableType extends CommonDBTM {

   /**
    * Constructor
    **/
   function __construct () {
      $this->table="glpi_consumablesitems";
      $this->type=CONSUMABLE_TYPE;
      $this->entity_assign=true;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      // Delete cartridconsumablesges
      $query = "DELETE
                FROM `glpi_consumables`
                WHERE (`consumablesitems_id` = '$ID')";
      $DB->query($query);
   }

   function post_getEmpty () {
      global $CFG_GLPI;

      $this->fields["alarm_threshold"]=$CFG_GLPI["default_alarm_threshold"];
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      if ($ID>0) {
         $ong[1]=$LANG['Menu'][32];
         if (haveRight("contract","r") || haveRight("infocom","r")) {
            $ong[4]=$LANG['Menu'][26];
         }
         if (haveRight("document","r")) {
            $ong[5]=$LANG['Menu'][27];
         }
         if (haveRight("link","r")) {
            $ong[7]=$LANG['title'][34];
         }
         if (haveRight("notes","r")) {
            $ong[10]=$LANG['title'][37];
         }
      } else { // New item
         $ong[1]=$LANG['title'][26];
      }
      return $ong;
   }

   /**
    * Print the consumable type form
    *
    *
    * Print g��al consumable type form
    *
    *@param $target filename : where to go when done.
    *@param $ID Integer : Id of the consumable type
    *@param $withtemplate='' boolean : template or basic item
    *
    *
    *@return Nothing (display)
    *
    **/
   function showForm ($target,$ID,$withtemplate='') {
      // Show ConsumableType or blank form
      global $CFG_GLPI,$LANG;

      if (!haveRight("consumable","r")) {
         return false;
      }

      if ($ID > 0){
         $this->check($ID,'r');
      } else {
         // Create item
         $this->check(-1,'w');
         $this->getEmpty();
      }

      $this->showTabs($ID, $withtemplate,$_SESSION['glpi_tab']);
      $this->showFormHeader($target,$ID,$withtemplate,2);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][16]."&nbsp;:</td>";
      echo "<td>";
      autocompletionTextField("name","glpi_consumablesitems","name",
                              $this->fields["name"],40,$this->fields["entities_id"]);
      echo "</td>";
      echo "<td rowspan='7' class='middle right'>".$LANG['common'][25].
      "&nbsp;: </td>";
      echo "<td class='center middle' rowspan='7'>.<textarea cols='45' rows='9' name='comment' >"
         .$this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][2]."&nbsp;:</td>\n";
      echo "<td>";
      autocompletionTextField("ref","glpi_consumablesitems","ref",$this->fields["ref"],40,
                              $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][17]."&nbsp;: </td>";
      echo "<td>";
      dropdownValue("glpi_consumablesitemstypes","consumablesitemstypes_id",
                    $this->fields["consumablesitemstypes_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][10]."&nbsp;:</td>";
      echo "<td>";
      dropdownUsersID("users_id_tech", $this->fields["users_id_tech"],"interface",1,
                      $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][36]."&nbsp;:</td>";
      echo "<td>";
      dropdownValue("glpi_locations","locations_id",$this->fields["locations_id"],1,
                    $this->fields["entities_id"]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['consumables'][38]."&nbsp;:</td>";
      echo "<td>";
      dropdownInteger('alarm_threshold',$this->fields["alarm_threshold"],-1,100);
      echo "</td></tr>";

      $this->showFormButtons($ID,$withtemplate,2);
      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";

      return true;
   }
}

//!  Consumable Class
/**
  This class is used to manage the consumables.
  @see ConsumableType
  @author Julien Dombre
 */
class Consumable extends CommonDBTM {

   /**
    * Constructor
    **/
   function __construct () {
      $this->table="glpi_consumables";
      $this->type=CONSUMABLE_ITEM_TYPE;
      // by the Consumable type
      $this->entity_assign=true;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_infocoms`
                WHERE (`items_id` = '$ID'
                       AND `itemtype`='".CONSUMABLE_ITEM_TYPE."')";
      $result = $DB->query($query);
   }

   function prepareInputForAdd($input) {
      return array("consumablesitems_id"=>$input["tID"],
                   "date_in"=>date("Y-m-d"));
   }

   function post_addItem($newID,$input) {

      // Add infocoms if exists for the licence
      $ic=new Infocom();

      if ($ic->getFromDBforDevice(CONSUMABLE_TYPE,$this->fields["consumablesitems_id"])) {
         unset($ic->fields["id"]);
         $ic->fields["items_id"]=$newID;
         $ic->fields["itemtype"]=CONSUMABLE_ITEM_TYPE;
         if (empty($ic->fields['use_date'])) {
            unset($ic->fields['use_date']);
         }
         if (empty($ic->fields['buy_date'])) {
            unset($ic->fields['buy_date']);
         }
         $ic->addToDB();
      }
   }

   function restore($input,$history=1) {
      global $DB;

      $query = "UPDATE
                `glpi_consumables`
                SET `date_out` = NULL
                WHERE `id`='".$input["id"]."'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * UnLink a consumable linked to a printer
    *
    * UnLink the consumable identified by $ID
    *
    *@param $ID : consumable identifier
    *@param $users_id : ID of the user giving the consumable
    *
    *@return boolean
    *
    **/
   function out($ID,$users_id=0) {
      global $DB;

      $query = "UPDATE
                `glpi_consumables`
                SET `date_out` = '".date("Y-m-d")."',
                    `users_id` = '$users_id'
                WHERE `id` = '$ID'";

      if ($result = $DB->query($query)) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Get the ID of entity assigned to the Consumable
    *
    * @return ID of the entity
   **/
   function getEntityID () {
      $ci=new ConsumableType();
      $ci->getFromDB($this->fields["consumablesitems_id"]);

      return $ci->getEntityID();
   }

}

?>