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

/**
 * Class Devices
 */
class Device extends CommonDBTM {

   // From CommonDBTM
   public $type = 'Device';
   public $auto_message_on_action = false;

   // Specific ones
   /// Current device type
   var $devtype=0;

   /**
    * Constructor
    * @param $dev_type device type
   **/
   function __construct($dev_type) {
      $this->devtype=$dev_type;
      $this->table=getDeviceTable($dev_type);
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
      }

      echo "<a href='$REFERER'>".$LANG['buttons'][13]."</a>";
      $this->showTabs($ID, "",getActiveTab($this->type),array("devicetype"=>$this->devtype,
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
      Dropdown::dropdownValue("glpi_manufacturers","manufacturers_id",$this->fields["manufacturers_id"]);
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
         case "glpi_devicemotherboards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_moboard'][0]."&nbsp;:</td>";
            echo "<td>";
            autocompletionTextField("chipset",$this->table,"chipset",$this->fields["chipset"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_deviceprocessors" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_ram'][1]."&nbsp;:</td><td>";
            autocompletionTextField("frequence",$this->table,"frequence",
                                    $this->fields["frequence"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicememories" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][17]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::dropdownValue("glpi_devicememorytypes","devicememorytypes_id",
                          $this->fields["devicememorytypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_ram'][1]."&nbsp;:</td><td>";
            autocompletionTextField("frequence",$this->table,"frequence",
                                    $this->fields["frequence"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_deviceharddrives" :
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
            Dropdown::dropdownValue("glpi_interfacetypes","interfacetypes_id",$this->fields["interfacetypes_id"]);
            echo "</td></tr>";
            break;

         case "glpi_devicenetworkcards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_iface'][0]."&nbsp;:</td><td>";
            autocompletionTextField("bandwidth",$this->table,"bandwidth",
                                    $this->fields["bandwidth"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicedrives" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_drive'][0]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::showYesNo("is_writer",$this->fields["is_writer"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::dropdownValue("glpi_interfacetypes","interfacetypes_id",$this->fields["interfacetypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_drive'][1]."&nbsp;:</td><td>";
            autocompletionTextField("speed",$this->table,"speed",$this->fields["speed"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case  "glpi_devicecontrols" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_control'][0]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::showYesNo("is_raid",$this->fields["is_raid"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::dropdownValue("glpi_interfacetypes","interfacetypes_id",$this->fields["interfacetypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicegraphiccards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_gfxcard'][0]."&nbsp;:</td><td>";
            autocompletionTextField("specif_default",$this->table,"specif_default",$this->fields["specif_default"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][65]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::dropdownValue("glpi_interfacetypes","interfacetypes_id",$this->fields["interfacetypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicesoundcards" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['common'][17]."&nbsp;:</td><td>";
            autocompletionTextField("type",$this->table,"type",$this->fields["type"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicepcis" :
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicecases" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_case'][0]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::dropdownValue("glpi_devicecasetypes","devicecasetypes_id",
                          $this->fields["devicecasetypes_id"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;

         case "glpi_devicepowersupplies" :
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_power'][0]."&nbsp;:</td><td>";
            autocompletionTextField("power",$this->table,"power",$this->fields["power"],40);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'>";
            echo "<td>".$LANG['device_power'][1]."&nbsp;:</td>";
            echo "<td>";
            Dropdown::showYesNo("is_atx",$this->fields["is_atx"]);
            echo "</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            echo "<tr class='tab_bg_1'><td colspan='2'>&nbsp;</td></tr>";
            break;
      }

      $this->showFormButtons($ID,'',2);

      echo "<div id='tabcontent'></div>";
      echo "<script type='text/javascript'>loadDefaultTab();</script>";
   }


   /**
    * Make a select box form  for device type
    *
    * @param $target URL to post the form
    * @param $computers_id computer ID
    * @param $withtemplate is it a template computer ?
    * @return nothing (print out an HTML select box)
    */
   static function dropdownDeviceSelector($target,$computers_id,$withtemplate='') {
      global $LANG,$CFG_GLPI;

      if (!haveRight("computer","w")) {
         return false;
      }
      if (!empty($withtemplate) && $withtemplate == 2) {
         //do nothing
      } else {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr  class='tab_bg_1'><td colspan='2' class='right' width='30%'>";
         echo $LANG['devices'][0]."&nbsp;:";
         echo "</td>";
         echo "<td colspan='63'>";
         echo "<form action=\"$target\" method=\"post\">";

         $rand=mt_rand();

         $devices=getDictDeviceLabel();

         echo "<select name='devicetype' id='device$rand'>";

         echo '<option value="-1">-----</option>';


         foreach ($devices as $i => $name) {
            echo '<option value="'.$i.'">'.$name.'</option>';
         }
         echo "</select>";

         $params=array('idtable'=>'__VALUE__',
                       'myname'=>'devices_id');

         ajaxUpdateItemOnSelectEvent("device$rand","showdevice$rand",$CFG_GLPI["root_doc"].
                                     "/ajax/dropdownDevice.php",$params);

         echo "<span id='showdevice$rand'>&nbsp;</span>\n";

         echo '<input type="hidden" name="withtemplate" value="'.$withtemplate.'" >';
         echo '<input type="hidden" name="connect_device" value="'.true.'" >';
         echo '<input type="hidden" name="computers_id" value="'.$computers_id.'" >';
         echo '<input type="submit" class ="submit" value="'.$LANG['buttons'][2].'" >';
         echo '</form>';
         echo '</td>';
         echo '</tr></table>';
      }
   }
}
?>