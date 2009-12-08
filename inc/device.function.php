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

/** Get device table based on device type
*@param $dev_type device type
*@return table name string
*/
function getDeviceTable($dev_type) {

   switch ($dev_type) {
      case MOBOARD_DEVICE :
         return "glpi_devicemotherboards";
         break;

      case PROCESSOR_DEVICE :
         return "glpi_deviceprocessors";
         break;

      case RAM_DEVICE :
         return "glpi_devicememories";
         break;

      case HDD_DEVICE :
         return "glpi_deviceharddrives";
         break;

      case NETWORK_DEVICE :
         return "glpi_devicenetworkcards";
         break;

      case DRIVE_DEVICE :
         return "glpi_devicedrives";
         break;

      case CONTROL_DEVICE :
         return "glpi_devicecontrols";
         break;

      case GFX_DEVICE :
         return "glpi_devicegraphiccards";
         break;

      case SND_DEVICE :
         return "glpi_devicesoundcards";
         break;

      case PCI_DEVICE :
         return "glpi_devicepcis";
         break;

      case CASE_DEVICE :
         return "glpi_devicecases";
         break;

      case POWER_DEVICE :
         return "glpi_devicepowersupplies";
         break;
   }
}

/** Get device specifity label based on device type
*@param $dev_type device type
*@return specifity label string
*/
function getDeviceSpecifityLabel($dev_type) {
   global $LANG;

   switch ($dev_type) {
      case MOBOARD_DEVICE :
         return "";
         break;

      case PROCESSOR_DEVICE :
         return $LANG['device_ram'][1];
         break;

      case RAM_DEVICE :
         return  $LANG['device_ram'][2];
         break;

      case HDD_DEVICE :
         return $LANG['device_hdd'][4];
         break;

      case NETWORK_DEVICE :
         return $LANG['device_iface'][2];
         break;

      case DRIVE_DEVICE :
         return "";
         break;

      case CONTROL_DEVICE :
         return "";
         break;

      case GFX_DEVICE :
         return  $LANG['device_gfxcard'][0];
         break;

      case SND_DEVICE :
         return "";
         break;

      case PCI_DEVICE :
         return "";
         break;

      case CASE_DEVICE :
         return "";
         break;

      case POWER_DEVICE :
         return "";
         break;
   }
}

/**
 * Get device type name based on device type
 * 
 * @param $device_num device type
 * @return if $device_num == -1 return array of names else return device name
 **/
function getDictDeviceLabel($device_num=-1) {
   global $LANG;

   $dp=array();
   $dp[MOBOARD_DEVICE]=$LANG['devices'][5];
   $dp[PROCESSOR_DEVICE]=$LANG['devices'][4];
   $dp[NETWORK_DEVICE]=$LANG['devices'][3];
   $dp[RAM_DEVICE]=$LANG['devices'][6];
   $dp[HDD_DEVICE]=$LANG['devices'][1];
   $dp[DRIVE_DEVICE]=$LANG['devices'][19];
   $dp[CONTROL_DEVICE]=$LANG['devices'][20];
   $dp[GFX_DEVICE]=$LANG['devices'][2];
   $dp[SND_DEVICE]=$LANG['devices'][7];
   $dp[PCI_DEVICE]=$LANG['devices'][21];
   $dp[CASE_DEVICE]=$LANG['devices'][22];
   $dp[POWER_DEVICE]=$LANG['devices'][23];
   if ($device_num==-1) {
      return $dp;
   } else {
      return $dp[$device_num];
   }
}

/** print form/tab for a device linked to a computer
*@param $device device object
*@param $quantity quantity of device
*@param $specif specificity value
*@param $compID computer ID
*@param $compDevID computer device ID
*@param $withtemplate template or basic computer
*/
function printDeviceComputer($device,$quantity,$specif,$compID,$compDevID,$withtemplate='') {
   global $LANG,$CFG_GLPI;

   if (!haveRight("computer","r")) {
      return false;
   }
   $canedit=haveRight("computer","w");

   //print the good form switch the wanted device type.
   $entry=array();
   $type="";
   $name="";
   $specificity_label = getDeviceSpecifityLabel($device->devtype);
   switch($device->devtype) {
      case HDD_DEVICE :
         $type=$LANG['devices'][1];
         $name=$device->fields["designation"];
         if (!empty($device->fields["rpm"])) {
            $entry[$LANG['device_hdd'][0]]=$device->fields["rpm"];
         }
         if ($device->fields["interfacetypes_id"]) {
            $entry[$LANG['common'][65]]=getDropdownName("glpi_interfacetypes",
                                                        $device->fields["interfacetypes_id"]);
         }
         if (!empty($device->fields["cache"])) {
            $entry[$LANG['device_hdd'][1]]=$device->fields["cache"];
         }
         $specificity_size = 10;
         break;

      case GFX_DEVICE :
         $type=$LANG['devices'][2];
         $name=$device->fields["designation"];
         $entry[$LANG['common'][65]]=getDropdownName("glpi_interfacetypes",
                                                     $device->fields["interfacetypes_id"]);
         $specificity_size = 10;
         break;

      case NETWORK_DEVICE :
         $type=$LANG['devices'][3];
         $name=$device->fields["designation"];
         if (!empty($device->fields["bandwidth"])) {
            $entry[$LANG['device_iface'][0]]=$device->fields["bandwidth"];
         }
         $specificity_size = 18;
         break;

      case MOBOARD_DEVICE :
         $type=$LANG['devices'][5];
         $name=$device->fields["designation"];
         if (!empty($device->fields["chipset"])) {
            $entry[$LANG['device_moboard'][0]]=$device->fields["chipset"];
         }
         $specificity_size = 10;
         break;

      case PROCESSOR_DEVICE :
         $type=$LANG['devices'][4];
         $name=$device->fields["designation"];
         if (!empty($device->fields["frequence"])) {
            $entry[$LANG['device_ram'][1]]=$device->fields["frequence"];
         }
         $specificity_size = 10;
         break;

      case RAM_DEVICE :
         $type=$LANG['devices'][6];
         $name=$device->fields["designation"];
         if (!empty($device->fields["type"])) {
            $entry[$LANG['common'][17]]=getDropdownName("glpi_devicememorytypes",
                                                        $device->fields["type"]);
         }
         if (!empty($device->fields["frequence"])) {
            $entry[$LANG['device_ram'][1]]=$device->fields["frequence"];
         }
         $specificity_size = 10;
         break;

      case SND_DEVICE :
         $type=$LANG['devices'][7];
         $name=$device->fields["designation"];
         if (!empty($device->fields["type"])) {
            $entry[$LANG['common'][17]]=$device->fields["type"];
         }
         $specificity_size = 10;
         break;

      case DRIVE_DEVICE : 
         $type=$LANG['devices'][19];
         $name=$device->fields["designation"];
         if ($device->fields["is_writer"]) {
            $entry[$LANG['device_drive'][0]]=getYesNo($device->fields["is_writer"]);
         }
         if (!empty($device->fields["speed"])) {
            $entry[$LANG['device_drive'][1]]=$device->fields["speed"];
         }
         if (!empty($device->fields["frequence"])) {
            $entry[$LANG['common'][65]]=$device->fields["frequence"];
         }
         if ($device->fields["interfacetypes_id"]) {
            $entry[$LANG['common'][65]]=getDropdownName("glpi_interfacetypes",
                                                        $device->fields["interfacetypes_id"]);
         }
         break;

      case CONTROL_DEVICE :
         $type=$LANG['devices'][20];
         $name=$device->fields["designation"];
         if ($device->fields["is_raid"]) {
            $entry[$LANG['device_control'][0]]=getYesNo($device->fields["is_raid"]);
         }
         if ($device->fields["interfacetypes_id"]) {
            $entry[$LANG['common'][65]]=getDropdownName("glpi_interfacetypes",
                                                        $device->fields["interfacetypes_id"]);
         }
         break;

      case PCI_DEVICE :
         $type=$LANG['devices'][21];
         $name=$device->fields["designation"];
         break;

      case POWER_DEVICE :
         $type=$LANG['devices'][23];
         $name=$device->fields["designation"];
         if (!empty($device->fields["power"])) {
            $entry[$LANG['device_power'][0]]=$device->fields["power"];
         }
         if ($device->fields["is_atx"]) {
            $entry[$LANG['device_power'][1]]=getYesNo($device->fields["is_atx"]);
         }
         break;

      case CASE_DEVICE :
         $type=$LANG['devices'][22];
         $name=$device->fields["designation"];
         if (!empty($device->fields["type"])) {
            $entry[$LANG['device_case'][0]]=getDropdownName("glpi_devicecasetypes",
                                                            $device->fields["type"]);
         }
         break;
   }
   echo "<tr class='tab_bg_2'>";
   echo "<td class='center'>";
   echo "<select name='quantity_$compDevID'>";
   for ($i=0;$i<100;$i++) {
      echo "<option value='$i' ".($quantity==$i?"selected":"").">".$i."x</option>";
   }
   echo "</select>";
   echo "</td>";

   if (haveRight("device","w")) {
      echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/device.php?devicetype=".
                                $device->devtype."'>$type</a></td>";
      echo "<td class='center'><a href='".$CFG_GLPI["root_doc"]."/front/device.form.php?id=".
                                $device->fields['id']."&amp;devicetype=".$device->devtype."'>&nbsp;$name&nbsp;".($_SESSION["glpiis_ids_visible"]?" (".$device->fields['id'].")":"")."</a></td>";
   } else {
      echo "<td class='center'>$type</td>";
      echo "<td class='center'>&nbsp;$name&nbsp;".
             ($_SESSION["glpiis_ids_visible"]?" (".$device->fields['id'].")":"")."</td>";
   }

   if (count($entry)>0) {
      $more=0;
      if(!empty($specificity_label)) {
        $more=1;
      }
      $colspan=60/(count($entry)+$more);
      foreach ($entry as $key => $val) {
         echo "<td colspan='$colspan'>$key:&nbsp;$val</td>";
      }
   } else if (empty($specificity_label)) {
      echo "<td colspan='60'>&nbsp;</td>";
   } else {
      $colspan=60;
   }

   if (!empty($specificity_label)) {
      //Mise a jour des spécificités
      if (!empty($withtemplate) && $withtemplate == 2) {
         if (empty($specif)) {
            $specif = "&nbsp;";
         }
         echo "<td colspan='$colspan'>".$specificity_label.":&nbsp;$specif</td><td>&nbsp;</td>";
      } else {
         echo "<td class='right' colspan='$colspan'>".$specificity_label."&nbsp;:&nbsp;";
         echo "<input type='text' name='devicevalue_$compDevID' value=\"".$specif."\" 
                size='$specificity_size' ></td>";
      }
   }
   echo "</tr>";
}

/**  Update an internal device specificity
* @param $newValue new specifity value
* @param $compDevID computer device ID
* @param $strict update based on ID
* @param $checkcoherence check coherence of new value before updating : do not update if it is not coherent
*/
function update_device_specif($newValue,$compDevID,$strict=false,$checkcoherence=false) {
   global $DB;

   // Check old value for history 
   $query ="SELECT * 
            FROM `glpi_computers_devices` 
            WHERE `id` = '".$compDevID."'";

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result)) {
         $data = addslashes_deep($DB->fetch_array($result));
         if ($checkcoherence) {
            switch ($data["devicetype"]) {
               case PROCESSOR_DEVICE :
                  //Prevent division by O error if newValue is null or doesn't contains any value
                  if ($newValue == null || $newValue=='') {
                     return false;
                  }
                  //Calculate pourcent change of frequency
                  $pourcent =  ( $newValue / ($data["specificity"] / 100) ) - 100;
                  //If new processor speed value is superior to the old one, 
                  //and if the change is at least 5% change 
                  if ($data["specificity"] < $newValue && $pourcent > 4) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;

               case GFX_DEVICE :
                  //If memory has changed and his new value is not 0
                  if ($data["specificity"] != $newValue && $newValue > 0) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;

               default :
                  if ($data["specificity"] != $newValue) {
                     $condition = true;
                  } else {
                     $condition = false;
                  }
                  break;
            }
         } else {
            if ($data["specificity"] != $newValue) {
               $condition = true;
            } else {
               $condition = false;
            }
         }
         // Is it a real change ? 
         if( $condition) {
            // Update specificity 
            $WHERE=" WHERE `devices_id` = '".$data["devices_id"]."'
                           AND `computers_id` = '".$data["computers_id"]."' 
                           AND `devicetype` = '".$data["devicetype"]."'
                           AND `specificity` = '".$data["specificity"]."'";
            if ($strict) {
                $WHERE=" WHERE `id` = '$compDevID'";
            }

            $query2 = "UPDATE 
                       `glpi_computers_devices` 
                       SET `specificity` = '".$newValue."' $WHERE";
            if ($DB->query($query2)) {
               $changes[0]='0';
               $changes[1]=addslashes($data["specificity"]);
               $changes[2]=$newValue;
               // history log
               historyLog ($data["computers_id"],COMPUTER_TYPE,$changes,$data["devicetype"],
                           HISTORY_UPDATE_DEVICE);
               return true;
            } else {
               return false;
            }
         }
      }
   }
}

/**  Update an internal device quantity
* @param $newNumber new quantity value
* @param $compDevID computer device ID
*/
function update_device_quantity($newNumber,$compDevID) {
   global $DB;

   // Check old value for history 
   $query ="SELECT * 
            FROM `glpi_computers_devices` 
            WHERE `id` = '".$compDevID."'";
   if ($result = $DB->query($query)) {
      $data = addslashes_deep($DB->fetch_array($result));

      $query2 = "SELECT `id` 
                 FROM `glpi_computers_devices` 
                 WHERE `devices_id` = '".$data["devices_id"]."' 
                       AND `computers_id` = '".$data["computers_id"]."'
                       AND `devicetype` = '".$data["devicetype"]."' 
                       AND `specificity` = '".$data["specificity"]."'";
      if ($result2 = $DB->query($query2)) {
         // Delete devices
         $number=$DB->numrows($result2);
         if ($number>$newNumber) {
            for ($i=$newNumber;$i<$number;$i++) {
               $data2 = $DB->fetch_array($result2);
               unLink_ItemType_computer($data2["id"],1);
            }
         // Add devices
         } else if ($number<$newNumber) {
            for ($i=$number;$i<$newNumber;$i++) {
               compdevice_add($data["computers_id"],$data["devicetype"],$data["devices_id"],
                              $data["specificity"],1);
            }
         }
      }
   }
}

/**
 * Unlink a device, linked to a computer.
 * 
 * Unlink a device and a computer witch link ID is $compDevID (on table glpi_computers_devices)
 *
 * @param $compDevID ID of the computer-device link (table glpi_computers_devices)
 * @param $dohistory log history updates ?
 * @returns boolean
 **/
function unLink_ItemType_computer($compDevID,$dohistory=1) {
   global $DB;

   // get old value  and id for history 
   $query ="SELECT * 
            FROM `glpi_computers_devices` 
            WHERE `id` = '".$compDevID."'";
   if ($result = $DB->query($query)) {
      $data = $DB->fetch_array($result);
   } 

   $query2 = "DELETE 
              FROM `glpi_computers_devices` 
              WHERE `id` = '".$compDevID."'";
   if ($DB->query($query2)) {
      if ($dohistory) {
         $device = new Device($data["devicetype"]);
         if ($device->getFromDB($data["devices_id"])) {
            $changes[0]='0';
            $changes[1]=addslashes($device->fields["designation"]);
            $changes[2]="";
            // history log
            historyLog ($data["computers_id"],COMPUTER_TYPE,$changes,$data["devicetype"],
                        HISTORY_DELETE_DEVICE);
         }
      }
      return true;
   } else { 
      return false;
   }
}

/**
 * Link the device to the computer
 * 
 * @param $computers_id Computer ID
 * @param $devicetype device type
 * @param $dID device ID
 * @param $specificity specificity value
 * @param $dohistory do history log
 * @returns new computer device ID
 **/
function compdevice_add($computers_id,$devicetype,$dID,$specificity='',$dohistory=1) {

   $device = new Device($devicetype);
   $device->getFromDB($dID);
   if (empty($specificity)) {
      $specificity=$device->fields['specif_default'];
   }
   $newID=$device->computer_link($computers_id,$devicetype,$specificity);

   if ($dohistory) {
      $changes[0]='0';
      $changes[1]="";
      $changes[2]=addslashes($device->fields["designation"]);
      // history log
      historyLog ($computers_id,COMPUTER_TYPE,$changes,$devicetype,HISTORY_ADD_DEVICE);
   }
   return $newID;
}

/**
 * Show Device list of a defined type
 * 
 * @param $devicetype device type
 * @param $target wher to go on action
 **/
function showDevicesList($devicetype,$target) {
   global $DB,$CFG_GLPI, $LANG;

   if (isset($_REQUEST['start'])) {
      $start = $_REQUEST['start'];
   } else {
      $start = 0;
   }
   $params = 'devicetype='.$devicetype;
   $where = '';
   if (isset($_REQUEST['name']) && !empty($_REQUEST['name'])) {
      $params .= '&name='.urlencode(stripslashes($_REQUEST['name']));
      $where = "`designation`" . makeTextSearch($_REQUEST['name']);
   }
   $number = countElementsInTable(getDeviceTable($devicetype), $where);
   
   printPager($start,$number,$_SERVER['PHP_SELF'],$params);

   // Lists Device from a devicetype
   $query = "SELECT `device`.`id`, `device`.`designation`, `glpi_manufacturers`.`name` as manufacturer 
             FROM `".getDeviceTable($devicetype)."` as device 
             LEFT JOIN `glpi_manufacturers` ON (`glpi_manufacturers`.`id` = `device`.`manufacturers_id`) ";
   if ($where) {
      $query .= " WHERE $where ";
   }
   $query .= "ORDER by `device`.`designation` ASC 
             LIMIT ".intval($start)."," . intval($_SESSION['glpilist_limit']);

   // Get it from database	
   if ($result = $DB->query($query)) {
      $numrows = $DB->numrows($result);
      $numrows_limit = $numrows;
      $result_limit = $result;
      if ($numrows_limit>0) {
         initNavigateListItems(DEVICE_TYPE);
         // Produce headline
         echo "<div class='center'><table class='tab_cadre_fixe'><tr>";
         // designation
         echo "<th>";
         echo $LANG['common'][16]."</th>";
         // Manufacturer		
         echo "<th>";
         echo $LANG['common'][5]."</th>";

         echo "</tr>";

         while ($data=$DB->fetch_array($result)) {
            addToNavigateListItems(DEVICE_TYPE,$data["id"]);
            echo "<tr class='tab_bg_2'>";
            echo "<td><strong>";
            echo "<a href=\"".$CFG_GLPI["root_doc"]."/front/device.form.php?id=".
                   $data["id"]."&amp;devicetype=$devicetype\">";
            if (utf8_strlen($data["designation"])>100 
               && (!strpos(' ',$data["designation"]) || strpos(' ',$data["designation"])>100)) {
               // sometime OCS send very long strange string
               echo utf8_substr($data["designation"],0,100)."&hellip;";
            } else {
               echo $data["designation"];
            }
            if ($_SESSION["glpiis_ids_visible"]) {
               echo " (".$data["id"].")";
            }
            echo "</a></strong></td>";
            echo "<td>". $data["manufacturer"]."</td>";
            echo "</tr>";
         }
         // Close Table
         echo "</table></div>";
      } else {
         echo "<div class='center'><strong>".$LANG['devices'][18]."</strong></div>";
      }
   }
}

/**
 * title for Devices
 * 
 * @param $devicetype device type
 **/
function titleDevices($devicetype) {
   global  $LANG,$CFG_GLPI;

   displayTitle($CFG_GLPI["root_doc"]."/pics/periph.png",$LANG['devices'][12],"",
                array("device.form.php?devicetype=$devicetype"=>$LANG['devices'][12]));
}

?>
