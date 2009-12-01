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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}




/// OCS config class
class OcsServer extends CommonDBTM {

   // From CommonDBTM
   public $table = 'glpi_ocsservers';
   public $type = OCSNG_TYPE;

   function defineTabs($ID,$withtemplate) {
      global $LANG;

      $tabs[1]=$LANG['help'][30];
      //If connection to the OCS DB  is ok, and all rights are ok too
      if ($ID != '' && checkOCSconnection($ID) && ocsCheckConfig(1) && ocsCheckConfig(2)
          && ocsCheckConfig(4) && ocsCheckConfig(8)) {

         $tabs[2]=$LANG['ocsconfig'][5];
         $tabs[3]=$LANG['ocsconfig'][27];
         $tabs[4]=$LANG['setup'][620];
      }
      return $tabs;
   }

   /**
    * Print ocs config form
    *
    *@param $target form target
    *@param $ID Integer : Id of the ocs config
    *@todo clean template process
    *@return Nothing (display)
    *
    **/
   function ocsFormConfig($target, $ID) {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("ocsng", "w")) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method=\"post\">";
      echo "<table class='tab_cadre'>\n";
      echo "<tr><th><input type='hidden' name='id' value='" . $ID . "'>&nbsp;";
      echo $LANG['ocsconfig'][27] ." ".$LANG['Menu'][0]. "&nbsp;</th>\n";
      echo "<th>&nbsp;" . $LANG['title'][30] . "&nbsp;</th>\n";
      echo "<th>&nbsp;" . $LANG['ocsconfig'][43] . "&nbsp;</th></tr>\n";
      echo "<tr class='tab_bg_2'>\n";

      echo "<td class='tab_bg_2 top'>\n";
      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . " </td>\n<td>";
      dropdownYesNo("import_general_name", $this->fields["import_general_name"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][9] . " </td>\n<td>";
      dropdownYesNo("import_general_os", $this->fields["import_general_os"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][10] . " </td>\n<td>";
      dropdownYesNo("import_os_serial", $this->fields["import_os_serial"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][19] . " </td>\n<td>";
      dropdownYesNo("import_general_serial", $this->fields["import_general_serial"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][22] . " </td>\n<td>";
      dropdownYesNo("import_general_model", $this->fields["import_general_model"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][5] . " </td>\n<td>";
      dropdownYesNo("import_general_manufacturer", $this->fields["import_general_manufacturer"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][17] . " </td>\n<td>";
      dropdownYesNo("import_general_type", $this->fields["import_general_type"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][89] . " </td>\n<td>";
      dropdownYesNo("import_general_domain", $this->fields["import_general_domain"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][18] . " </td>\n<td>";
      dropdownYesNo("import_general_contact", $this->fields["import_general_contact"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][25] . " </td>\n<td>";
      dropdownYesNo("import_general_comment", $this->fields["import_general_comment"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['networking'][14] . " </td>\n<td>";
      dropdownYesNo("import_ip", $this->fields["import_ip"]);
      echo "</td></tr>\n";
      echo "<tr><td>&nbsp;</td></tr>";
      echo "</table></td>\n";

      echo "<td class='tab_bg_2 top'>\n";
      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][4] . " </td>\n<td>";
      dropdownYesNo("import_device_processor", $this->fields["import_device_processor"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][6] . " </td>\n<td>";
      dropdownYesNo("import_device_memory", $this->fields["import_device_memory"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][1] . " </td>\n<td>";
      dropdownYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][3] . " </td>\n<td>";
      dropdownYesNo("import_device_iface", $this->fields["import_device_iface"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][2] . " </td>\n<td>";
      dropdownYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
      echo "&nbsp;&nbsp;</td></tr>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][7] . " </td>\n<td>";
      dropdownYesNo("import_device_sound", $this->fields["import_device_sound"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][19] . " </td>\n<td>";
      dropdownYesNo("import_device_drive", $this->fields["import_device_drive"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][36] . " </td>\n<td>";
      dropdownYesNo("import_device_modem", $this->fields["import_device_modem"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][37] . " </td>\n<td>";
      dropdownYesNo("import_device_port", $this->fields["import_device_port"]);
      echo "</td></tr>\n";
      echo "</table></td>\n";

      echo "<td class='tab_bg_2 top'>\n";
      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][20] . " </td>\n<td>";
      echo "<select name='import_otherserial'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = getColumnListFromAccountInfoTable($ID,"otherserial");
      echo $listColumnOCS;
      echo "</select>&nbsp;&nbsp;</td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][15] . " </td>\n<td>";
      echo "<select name='import_location'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = getColumnListFromAccountInfoTable($ID,"locations_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][35] . " </td>\n<td>";
      echo "<select name='import_group'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = getColumnListFromAccountInfoTable($ID,"groups_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][21] . " </td>\n<td>";
      echo "<select name='import_contact_num'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = getColumnListFromAccountInfoTable($ID,"contact_num");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][88] . " </td>\n<td>";
      echo "<select name='import_network'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = getColumnListFromAccountInfoTable($ID,"networks_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";
      echo "</table></td>";

      echo "</tr>\n";
      echo "<tr><th>&nbsp;" . $LANG['ocsconfig'][27] ." ".$LANG['Menu'][3]. "&nbsp;</th>\n";
      echo "<th colspan='2'>&nbsp;</th></tr>\n";
      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='tab_bg_2 top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][25] . " </td>\n<td>";
      dropdownYesNo("import_monitor_comment", $this->fields["import_monitor_comment"]);
      echo "</td></tr>\n";
      echo "</table></td>\n";

      echo "<td class='tab_bg_2' colspan='2'>&nbsp;</td>";
      echo "</table>\n";
      echo "<p class='submit'><input type='submit' name='update_server' class='submit' value=\"" .
                               $LANG['buttons'][2] . "\" ></p>";
      echo "</form></div>\n";
   }

   function ocsFormImportOptions($target, $ID,$withtemplate='',$templateid='') {
      global $LANG;

      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method='post'>";
      echo "<table class='tab_cadre'>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][59];
      echo "<input type='hidden' name='id' value='" . $ID . "'>" . " </td>\n";
      echo "<td><input type='text' size='30' name='ocs_url' value=\"" . $this->fields["ocs_url"] ."\">";
      echo "</td></tr>\n";

      echo "<tr><th colspan='2'>" . $LANG['ocsconfig'][5] . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][17] . " </td>\n";
      echo "<td><input type='text' size='30' name='tag_limit' value=\"" .
                 $this->fields["tag_limit"] . "\"></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][9] . " </td>\n";
      echo "<td><input type='text' size='30' name='tag_exclude' value=\"" .
                 $this->fields["tag_exclude"] . "\"></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][16] . " </td>\n<td>";
      dropdownValue("glpi_states", "states_id_default", $this->fields["states_id_default"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][48] . " </td>\n<td>";
      dropdownArrayValues("deconnection_behavior",array(''=>$LANG['buttons'][49],
                                                        "trash"=>$LANG['ocsconfig'][49],
                                                        "delete"=>$LANG['ocsconfig'][50]),
                                                        $this->fields["deconnection_behavior"]);
      echo "</td></tr>\n";

      $import_array = array("0"=>$LANG['ocsconfig'][11],
                            "1"=>$LANG['ocsconfig'][10],
                            "2"=>$LANG['ocsconfig'][12]);
      $import_array2= array("0"=>$LANG['ocsconfig'][11],
                            "1"=>$LANG['ocsconfig'][10],
                            "2"=>$LANG['ocsconfig'][12],
                            "3"=>$LANG['ocsconfig'][19]);
      $periph = $this->fields["import_periph"];
      $monitor = $this->fields["import_monitor"];
      $printer = $this->fields["import_printer"];
      $software = $this->fields["import_software"];
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][16] . " </td>\n<td>";
      dropdownArrayValues("import_periph",$import_array,$periph);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][3] . " </td>\n<td>";
      dropdownArrayValues("import_monitor",$import_array2,$monitor);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][2] . " </td>\n<td>";
      dropdownArrayValues("import_printer",$import_array,$printer);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][4] . " </td>\n<td>";
      $import_array = array("0"=>$LANG['ocsconfig'][11],
                            "1"=>$LANG['ocsconfig'][12]);
      dropdownArrayValues("import_software",$import_array,$software);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][8] . " </td>\n<td>";
      dropdownYesNo("import_disk", $this->fields["import_disk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][38] . " </td>\n<td>";
      dropdownYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][41] . " </td>\n<td>";
      dropdownYesNo("import_registry", $this->fields["import_registry"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][40] . " </td>\n<td>";
      dropdownInteger('cron_sync_number', $this->fields["cron_sync_number"], 0, 100);
      echo "</td></tr></table>\n";

      echo "<br>" . $LANG['ocsconfig'][15];
      echo "<br>" . $LANG['ocsconfig'][14];
      echo "<br>" . $LANG['ocsconfig'][13];

      echo "<p class='submit'><input type='submit' name='update_server' class='submit' value=\"" .
             $LANG['buttons'][2] . "\" ></p>";
      echo "</form></div>";
   }

   function ocsFormAutomaticLinkConfig($target, $ID,$withtemplate='',$templateid='') {
      global $DB, $LANG, $CFG_GLPI;

      if (!haveRight("ocsng", "w")) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method='post'>\n";
      echo "<table class='tab_cadre'>\n";
      echo "<tr><th colspan='4'>" . $LANG['ocsconfig'][52];
      echo "<input type='hidden' name='id' value='" . $ID . "'></th></tr>\n";
      echo "<tr class='tab_bg_2'><td>" . $LANG['ocsconfig'][53] . " </td>\n<td colspan='3'>";
      dropdownYesNo("is_glpi_link_enabled", $this->fields["is_glpi_link_enabled"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . $LANG['ocsconfig'][54] . "</th></tr>\n";
      echo "<tr class='tab_bg_2'><td>" . $LANG['networking'][14] . " </td>\n<td>";
      dropdownYesNo("use_ip_to_link", $this->fields["use_ip_to_link"]);
      echo "</td>\n";
      echo "<td>" . $LANG['device_iface'][2] . " </td>\n<td>";
      dropdownYesNo("use_mac_to_link", $this->fields["use_mac_to_link"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td>" . $LANG['rulesengine'][25] . " </td>\n<td>";
      $link_array=array("0"=>$LANG['choice'][0],
                        "1"=>$LANG['choice'][1]." : ".$LANG['ocsconfig'][57],
                        "2"=>$LANG['choice'][1]." : ".$LANG['ocsconfig'][56]);
      dropdownArrayValues("use_name_to_link", $link_array,$this->fields["use_name_to_link"]);
      echo "</td>\n";
      echo "<td>" . $LANG['common'][19] . " </td>\n<td>";
      dropdownYesNo("use_serial_to_link", $this->fields["use_serial_to_link"]);
      echo "</td></tr>\n";
      echo "<tr class='tab_bg_2'><td>" . $LANG['ocsconfig'][55] . " </td>\n<td colspan='3'>";
      dropdownValue("glpi_states", "states_id_linkif", $this->fields["states_id_linkif"]);
      echo "</td></tr>\n";

      echo "</table><br>".$LANG['ocsconfig'][58];
      echo "<p class='submit'><input type='submit' name='update_server' class='submit' value=\"" .
             $LANG['buttons'][2] . "\" ></p>";
      echo "</form></div>";
   }

   /**
    * Print simple ocs config form (database part)
    *
    *@param $target form target
    *@param $ID Integer : Id of the ocs config
    *@return Nothing (display)
    *
    **/
   function showForm($target, $ID) {
      global $DB, $DBocs, $LANG, $CFG_GLPI;

      if (!haveRight("ocsng", "w")) {
         return false;
      }

      //If no ID provided, or if the server is created using an existing template
      if (empty ($ID)) {
         $this->getEmpty();
      } else {
         $this->getFromDB($ID);
      }

      $this->showTabs($ID, '',getActiveTab($this->type));

      $out  = "\n<div class='center' id='tabsbody'>";
      $out .= "<form name='formdbconfig' action=\"$target\" method=\"post\">";
      $out .= "<table class='tab_cadre_fixe'>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][88] . " </td>\n";
      $out .= "<td><strong>" . $this->fields["id"] . "</strong></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][16] . " </td>\n";
      $out .= "<td><input type='text' name='name' value=\"" . $this->fields["name"] ."\"></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][2] . " </td>\n";
      $out .= "<td><input type='text' name='ocs_db_host' value=\"" .
                    $this->fields["ocs_db_host"] ."\"></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][4] . " </td>\n";
      $out .= "<td><input type='text' name='ocs_db_name' value=\"" .
                    $this->fields["ocs_db_name"] . "\"></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][1] . " </td>\n";
      $out .= "<td><input type='text' name='ocs_db_user' value=\"" .
                    $this->fields["ocs_db_user"] . "\"></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][3] . " </td>\n";
      $out .= "<td><input type='password' name='ocs_db_passwd' value=''></td></tr>\n";

      if ($ID == '') {
         $out .= "<tr class='tab_bg_2'><td class='center' colspan=2>";
         $out .= "<input type='submit' name='add' class='submit' value=\"" .
                   $LANG['buttons'][2] . "\" ></td></tr>\n";
      } else {
         $out .= "<tr class='tab_bg_2'><td class='center' colspan=2>";
         $out .= "<input type='hidden' name='id' value='$ID'>\n";
         $out .= "<input type='submit' name='update' class='submit' value=\"" .
                   $LANG['buttons'][2] . "\" >&nbsp;";
         $out .= "<input type='submit' name='delete' class='submit' value=\"" .
                   $LANG['buttons'][6] . "\" ></td></tr>\n";
      }
      $out .= "</table></form></div>\n";
      $out .= "<div id='tabcontent'></div>";
      $out .= "<script type='text/javascript'>loadDefaultTab();</script>";
      echo $out;
   }

   function showDBConnectionStatus($ID) {
      global $LANG;

      $out="<br><div class='center'>\n";
      $out.="<table class='tab_cadre'>";
      $out.="<tr><th>" .$LANG['setup'][602] . "</th></tr>\n";
      $out.="<tr class='tab_bg_2'><td class='center'>";
      if ($ID != -1) {
         if (!checkOCSconnection($ID)) {
            $out.=$LANG['ocsng'][21];
         } else if (!ocsCheckConfig(1)) {
            $out.=$LANG['ocsng'][20];
         } else if (!ocsCheckConfig(2)) {
            $out.=$LANG['ocsng'][42];
         } else if (!ocsCheckConfig(4)) {
            $out.=$LANG['ocsng'][43];
         } else if (!ocsCheckConfig(8)) {
            $out.=$LANG['ocsng'][44];
         } else {
            $out.=$LANG['ocsng'][18];
            $out.="</td></tr>\n<tr class='tab_bg_2'><td class='center'>".$LANG['ocsng'][19];
         }
      }
      $out.="</td></tr>\n";
      $out.="</table></div>";
      echo $out;
   }

   function prepareInputForUpdate($input) {

      $this->updateAdminInfo($input);
      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"]=rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }

   function pre_updateInDB($input,$updates,$oldvalues=array()) {

      // Update checksum
      $input["checksum"]=0;

      if ($this->fields["import_printer"]) {
         $input["checksum"]|= pow(2,PRINTERS_FL);
      }
      if ($this->fields["import_software"]) {
         $input["checksum"]|= pow(2,SOFTWARES_FL);
      }
      if ($this->fields["import_monitor"]) {
         $input["checksum"]|= pow(2,MONITORS_FL);
      }
      if ($this->fields["import_periph"]) {
         $input["checksum"]|= pow(2,INPUTS_FL);
      }
      if ($this->fields["import_registry"]) {
         $input["checksum"]|= pow(2,REGISTRY_FL);
      }
      if ($this->fields["import_disk"]) {
         $input["checksum"]|= pow(2,DRIVES_FL);
      }
      if ($this->fields["import_ip"]) {
         $input["checksum"]|= pow(2,NETWORKS_FL);
      }
      if ($this->fields["import_device_port"]) {
         $input["checksum"]|= pow(2,PORTS_FL);
      }
      if ($this->fields["import_device_modem"]) {
         $input["checksum"]|= pow(2,MODEMS_FL);
      }
      if ($this->fields["import_device_drive"]) {
         $input["checksum"]|= pow(2,STORAGES_FL);
      }
      if ($this->fields["import_device_sound"]) {
         $input["checksum"]|= pow(2,SOUNDS_FL);
      }
      if ($this->fields["import_device_gfxcard"]) {
         $input["checksum"]|= pow(2,VIDEOS_FL);
      }
      if ($this->fields["import_device_iface"]) {
         $input["checksum"]|= pow(2,NETWORKS_FL);
      }
      if ($this->fields["import_device_hdd"]) {
         $input["checksum"]|= pow(2,STORAGES_FL);
      }
      if ($this->fields["import_device_memory"]) {
         $input["checksum"]|= pow(2,MEMORIES_FL);
      }
      if ($this->fields["import_device_processor"] || $this->fields["import_general_contact"]
          || $this->fields["import_general_comment"] || $this->fields["import_general_domain"]
          || $this->fields["import_general_os"] || $this->fields["import_general_name"]) {

         $input["checksum"]|= pow(2,HARDWARE_FL);
      }
      if ($this->fields["import_general_manufacturer"] || $this->fields["import_general_type"]
          || $this->fields["import_general_model"] || $this->fields["import_general_serial"]) {

         $input["checksum"]|= pow(2,BIOS_FL);
      }
      $updates[]="checksum";
      $this->fields["checksum"]=$input["checksum"];
      return array($input,$updates);
   }

   function prepareInputForAdd($input) {
      global $LANG,$DB;

      // Check if server config does not exists
      $query = "SELECT *
                FROM `" . $this->table . "`
                WHERE `name` = '".$input['name']."';";
      $result=$DB->query($query);
      if ($DB->numrows($result)>0) {
         addMessageAfterRedirect($LANG['setup'][609],false,ERROR);
         return false;
      }

      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"]=rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }

   function cleanDBonPurge($ID) {
      global $DB;

      $query = "DELETE
                FROM `glpi_ocslinks`
                WHERE `ocsservers_id` = '$ID'";
      $result = $DB->query($query);
   }

   /**
    * Update Admin Info retrieve config
    *
    *@param $tab data array
    **/
   function updateAdminInfo($tab) {

      if (isset($tab["import_location"]) || isset ($tab["import_otherserial"])
          || isset ($tab["import_group"]) || isset ($tab["import_network"])
          || isset ($tab["import_contact_num"])) {

         $adm = new OcsAdminInfosLink();
         $adm->cleanDBonPurge($tab["id"]);
         if (isset ($tab["import_location"])) {
            if ($tab["import_location"]!="") {
               $adm = new OcsAdminInfosLink();
               $adm->fields["ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "locations_id";
               $adm->fields["ocs_column"] = $tab["import_location"];
               $isNewAdm = $adm->addToDB();
            }
         }
         if (isset ($tab["import_otherserial"])) {
            if ($tab["import_otherserial"]!="") {
               $adm = new OcsAdminInfosLink();
               $adm->fields["ocsservers_id"] =  $tab["id"];
               $adm->fields["glpi_column"] = "otherserial";
               $adm->fields["ocs_column"] = $tab["import_otherserial"];
               $isNewAdm = $adm->addToDB();
            }
         }
         if (isset ($tab["import_group"])) {
            if ($tab["import_group"]!="") {
               $adm = new OcsAdminInfosLink();
               $adm->fields["ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "groups_id";
               $adm->fields["ocs_column"] = $tab["import_group"];
               $isNewAdm = $adm->addToDB();
            }
         }
         if (isset ($tab["import_network"])) {
            if ($tab["import_network"]!="") {
               $adm = new OcsAdminInfosLink();
               $adm->fields["ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "networks_id";
               $adm->fields["ocs_column"] = $tab["import_network"];
               $isNewAdm = $adm->addToDB();
            }
         }
         if (isset ($tab["import_contact_num"])) {
            if ($tab["import_contact_num"]!="") {
               $adm = new OcsAdminInfosLink();
               $adm->fields["ocsservers_id"] = $tab["id"];
               $adm->fields["glpi_column"] = "contact_num";
               $adm->fields["ocs_column"] = $tab["import_contact_num"];
               $isNewAdm = $adm->addToDB();
            }
         }
      }
   }
}

?>
