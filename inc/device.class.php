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

if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

///Class Devices
class Device extends CommonDBTM {
   /// Current device type
   var $devtype=0;

   /**
    * Constructor
    * @param $dev_type device type
   **/
   function __construct($dev_type) {
      $this->devtype=$dev_type;
      $this->table=getDeviceTable($dev_type);
      $this->type=DEVICE_TYPE;
      $this->auto_message_on_action=false;
   }

   function prepareInputForAdd($input) {

      if (isset($input['devicetype'])) {
         switch ($input['devicetype']) {
            case PROCESSOR_DEVICE :
               if (isset($input['frequence'])) {
                  if (!is_numeric($input['frequence'])) {
                     $input['frequence']=0;
                  }
               }
               break;
         }
      }
      return $input;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query2 = "DELETE 
                 FROM `glpi_computers_devices` 
                 WHERE `devices_id` = '$ID' 
                       AND `devicetype`='".$this->devtype."'";
      $DB->query($query2);
   }

   function canView() {
      return haveRight("device","r");
   }

   function canCreate() {
      return haveRight("device","w");
   }

   // SPECIFIC FUNCTIONS
   /**
    * Connect the current device to a computer
    *
    *@param $compID computer ID
    *@param $devicetype device type
    *@param $specificity value of the specificity
    *@return boolean : success ?
   **/
   function computer_link($compID,$devicetype,$specificity='') {
      global $DB;

      $query = "INSERT INTO 
                `glpi_computers_devices` (`devicetype`, `devices_id`, `computers_id`, `specificity`)
                VALUES ('".$devicetype."','".$this->fields["id"]."','".$compID."','".$specificity."')";
      if($DB->query($query)) {
         return $DB->insert_id();
      } else { 
         return false;
      }
   }

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $ong=array();
      $ong[1]=getDictDeviceLabel($this->devtype);
      return $ong;
   }

   /**
    * Show Device Form
    * 
    * @param $target where to go on action
    * @param $ID device ID
    * 
    **/
   function showForm ($target,$ID) {
      global $CFG_GLPI,$LANG,$REFERER;
   
      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         // Create item 
         $this->check(-1,'w');
         $this->getEmpty();
      }
   
      echo "<a href='$REFERER'>".$LANG['buttons'][13]."</a>";
      $this->showTabs($ID, "",$_SESSION['glpi_tab'],array("devicetype"=>$this->devtype,
                                                          "referer"=>$REFERER));
      $this->showFormHeader($target,$ID,'',2);
   
      echo "<tr class='tab_bg_1'>";
      // table commune
      
      echo "<td>".$LANG['common'][16]."&nbsp;: </td>";
      echo "<td>";
      echo "<input type='hidden' name='referer' value='$REFERER'>"; 
      echo "<input type='hidden' name='devicetype' value='".$this->devtype."'>";
      autocompletionTextField("designation",$this->table,"designation",
                              $this->fields["designation"],40);
      echo "<td rowspan='6' class='middle right'>".$LANG['common'][25]."&nbsp;: </td>";
      echo "<td class='center middle' rowspan='6'>.<textarea cols='45' rows='6' name='comment' >".
             $this->fields["comment"]."</textarea></td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".$LANG['common'][5]."&nbsp;:</td>";
      echo "<td colspan='2'>";
      dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
      echo "</td></tr>";

      if (getDeviceSpecifityLabel($this->devtype)!="") {
         echo "<tr class='tab_bg_1'>";
         echo "<td>".getDeviceSpecifityLabel($this->devtype)." ".$LANG['devices'][24]."</td>";
         echo "<td><input type='text' name='specif_default' value=\"".
                    $this->fields["specif_default"]."\" size='20'>";
         echo "</td></tr>";
      }
      // fin table Commune
   
      // table particuliere
      switch($this->table) {
         case "glpi_devicesmotherboards" : 
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_moboard'][0]."&nbsp;:</td>";
            echo "<td>";
            autocompletionTextField("chipset",$this->table,"chipset",$this->fields["chipset"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesprocessors" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_ram'][1]."&nbsp;:</td><td>";
            autocompletionTextField("frequence",$this->table,"frequence",
                                    $this->fields["frequence"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesmemories" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_devicesmemoriestypes","devicesmemoriestypes_id",
                          $this->fields["devicesmemoriestypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_ram'][1]."&nbsp;:</td><td>";
            autocompletionTextField("frequence",$this->table,"frequence",
                                    $this->fields["frequence"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesharddrives" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_hdd'][0]."&nbsp;:</td><td>";
            autocompletionTextField("rpm",$this->table,"rpm",$this->fields["rpm"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_hdd'][1]."&nbsp;:</td><td>";
            autocompletionTextField("cache",$this->table,"cache",$this->fields["cache"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_interfaces","interfaces_id",$this->fields["interfaces_id"]);
            echo "</td></tr>";
            break;

         case "glpi_devicesnetworkcards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_iface'][0]."&nbsp;:</td><td>";
            autocompletionTextField("bandwidth",$this->table,"bandwidth",
                                    $this->fields["bandwidth"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesdrives" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_drive'][0]."&nbsp;:</td>";
            echo "<td>";
            dropdownYesNo("is_writer",$this->fields["is_writer"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_interfaces","interfaces_id",$this->fields["interfaces_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_drive'][1]."&nbsp;:</td><td>";
            autocompletionTextField("speed",$this->table,"speed",$this->fields["speed"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case  "glpi_devicescontrols" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_control'][0]."&nbsp;:</td>";
            echo "<td>";
            dropdownYesNo("is_raid",$this->fields["is_raid"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_interfaces","interfaces_id",$this->fields["interfaces_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesgraphiccards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_gfxcard'][0]."&nbsp;:</td><td>";
            autocompletionTextField("specif_default",$this->table,"specif_default",$this->fields["specif_default"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_interfaces","interfaces_id",$this->fields["interfaces_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicessoundcards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][17]."&nbsp;:</td><td>";
            autocompletionTextField("type",$this->table,"type",$this->fields["type"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicespcis" :
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicescases" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_case'][0]."&nbsp;:</td>";
            echo "<td>";
            dropdownValue("glpi_devicescasestypes","devicescasestypes_id",
                          $this->fields["devicescasestypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicespowersupplies" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_power'][0]."&nbsp;:</td><td>";
            autocompletionTextField("power",$this->table,"power",$this->fields["power"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_power'][1]."&nbsp;:</td>";
            echo "<td>";
            dropdownYesNo("is_atx",$this->fields["is_atx"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;
      }

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }

}

?>