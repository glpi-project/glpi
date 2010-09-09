<?php

/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
   public $dohistory = true;
   // Class constant - still used for import_device field
   // not used const MOBOARD_DEVICE=1;
   const PROCESSOR_DEVICE = 2;
   const RAM_DEVICE       = 3;
   const HDD_DEVICE       =4 ;
   const NETWORK_DEVICE   = 5;
   const DRIVE_DEVICE     = 6;
   // not used const CONTROL_DEVICE=7;
   const GFX_DEVICE = 8;
   const SND_DEVICE = 9;
   const PCI_DEVICE = 10;
   // not used const CASE_DEVICE=11;
   // not used const POWER_DEVICE=12;

   // Class constants - import_ management
   const FIELD_SEPARATOR = '$$$$$';
   const IMPORT_TAG_070  = '_version_070_';
   const IMPORT_TAG_072  = '_version_072_';
   const IMPORT_TAG_078  = '_version_078_';

   // Class constants - OCSNG Flags on Checksum
   const HARDWARE_FL    = 0;
   const BIOS_FL        = 1;
   const MEMORIES_FL    = 2;
   // not used const SLOTS_FL       = 3;
   const REGISTRY_FL    = 4;
   // not used const CONTROLLERS_FL = 5;
   const MONITORS_FL    = 6;
   const PORTS_FL       = 7;
   const STORAGES_FL    = 8;
   const DRIVES_FL      = 9;
   const INPUTS_FL      = 10;
   const MODEMS_FL      = 11;
   const NETWORKS_FL    = 12;
   const PRINTERS_FL    = 13;
   const SOUNDS_FL      = 14;
   const VIDEOS_FL      = 15;
   const SOFTWARES_FL   = 16;
   const MAX_CHECKSUM   = 131071;

   // Class constants - Update result
   const COMPUTER_IMPORTED       = 0;
   const COMPUTER_SYNCHRONIZED   = 1;
   const COMPUTER_LINKED         = 2;
   const COMPUTER_FAILED_IMPORT  = 3;
   const COMPUTER_NOTUPDATED     = 4;


   static function getTypeName() {
      global $LANG;

      return $LANG['ocsng'][29];
   }


   function canCreate() {
      return haveRight('ocsng', 'w');
   }


   function canView() {
      return haveRight('ocsng', 'r');
   }


   function defineTabs($options=array()) {
      global $LANG;

      $tabs[1] = $LANG['help'][30];
      //If connection to the OCS DB  is ok, and all rights are ok too
      if ($this->fields['id'] != ''
          && OcsServer::checkOCSconnection($this->fields['id'])
          && OcsServer::checkConfig(1)
          && OcsServer::checkConfig(2)
          && OcsServer::checkConfig(4)
          && OcsServer::checkConfig(8)) {

         $tabs[2] = $LANG['ocsconfig'][5];
         $tabs[3] = $LANG['ocsconfig'][27];
         $tabs[4] = $LANG['setup'][620];
         $tabs[12] = $LANG['title'][38];
      }
      return $tabs;
   }


   function getSearchOptions() {
      global $LANG;

      $tab = array();
      $tab['common'] = $LANG['login'][2];

      $tab[1]['table']         = $this->getTable();
      $tab[1]['field']         = 'name';
      $tab[1]['linkfield']     = 'name';
      $tab[1]['name']          = $LANG['common'][16];
      $tab[1]['datatype']      = 'itemlink';
      $tab[1]['itemlink_type'] = $this->getType();

      $tab[2]['table']     = $this->getTable();
      $tab[2]['field']     = 'id';
      $tab[2]['linkfield'] = '';
      $tab[2]['name']      = $LANG['common'][2];

      $tab[3]['table']     = $this->getTable();
      $tab[3]['field']     = 'ocs_db_host';
      $tab[3]['linkfield'] = 'ocs_db_host';
      $tab[3]['name']      = $LANG['common'][52];

      $tab[6]['table']     = $this->getTable();
      $tab[6]['field']     = 'is_active';
      $tab[6]['linkfield'] = 'is_active';
      $tab[6]['name']      = $LANG['common'][60];
      $tab[6]['datatype']  = 'bool';

      $tab[19]['table']       = $this->getTable();
      $tab[19]['field']       = 'date_mod';
      $tab[19]['linkfield']   = '';
      $tab[19]['name']        = $LANG['common'][26];
      $tab[19]['datatype']    = 'datetime';

      $tab[16]['table']     = $this->getTable();
      $tab[16]['field']     = 'comment';
      $tab[16]['linkfield'] = 'comment';
      $tab[16]['name']      = $LANG['common'][25];
      $tab[16]['datatype']  = 'text';

      return $tab;
   }


   /**
    * Print ocs config form
    *
    *@param $target form target
    *@param $ID Integer : Id of the ocs config
    *@return Nothing (display)
    *
    **/
   function ocsFormConfig($target, $ID) {
      global $LANG;

      if (!haveRight("ocsng", "w")) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method='post'>";
      echo "<table class='tab_cadre'>\n";
      echo "<tr><th><input type='hidden' name='id' value='$ID'>&nbsp;".$LANG['ocsconfig'][27] ." ".
                     $LANG['Menu'][0]. "&nbsp;</th>\n";
      echo "<th>&nbsp;" . $LANG['title'][30] . "&nbsp;</th>\n";
      echo "<th>&nbsp;" . $LANG['ocsconfig'][43] . "&nbsp;</th></tr>\n";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_name", $this->fields["import_general_name"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][9] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_os", $this->fields["import_general_os"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][10] . " </td>\n<td>";
      Dropdown::showYesNo("import_os_serial", $this->fields["import_os_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][19] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_serial", $this->fields["import_general_serial"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][22] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_model", $this->fields["import_general_model"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][5] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_manufacturer", $this->fields["import_general_manufacturer"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][17] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_type", $this->fields["import_general_type"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][89] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_domain", $this->fields["import_general_domain"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][18] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_contact", $this->fields["import_general_contact"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][25] . " </td>\n<td>";
      Dropdown::showYesNo("import_general_comment", $this->fields["import_general_comment"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['networking'][14] . " </td>\n<td>";
      Dropdown::showYesNo("import_ip", $this->fields["import_ip"]);
      echo "</td></tr>\n";

      echo "<tr><td>&nbsp;</td></tr>";
      echo "</table>";

      echo "</td>\n";
      echo "<td class='tab_bg_2 top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][4] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_processor", $this->fields["import_device_processor"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][6] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_memory", $this->fields["import_device_memory"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][1] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_hdd", $this->fields["import_device_hdd"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][3] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_iface", $this->fields["import_device_iface"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][2] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_gfxcard", $this->fields["import_device_gfxcard"]);
      echo "&nbsp;&nbsp;</td></tr>";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][7] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_sound", $this->fields["import_device_sound"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['devices'][19] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_drive", $this->fields["import_device_drive"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][36] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_modem", $this->fields["import_device_modem"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][37] . " </td>\n<td>";
      Dropdown::showYesNo("import_device_port", $this->fields["import_device_port"]);
      echo "</td></tr>\n";
      echo "</table>";

      echo "</td>\n";
      echo "<td class='tab_bg_2 top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][20] . " </td>\n";
      echo "<td><select name='import_otherserial'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = OcsServer::getColumnListFromAccountInfoTable($ID, "otherserial");
      echo $listColumnOCS;
      echo "</select>&nbsp;&nbsp;</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][15] . " </td>\n";
      echo "<td><select name='import_location'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = OcsServer::getColumnListFromAccountInfoTable($ID, "locations_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][35] . " </td>\n";
      echo "<td><select name='import_group'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = OcsServer::getColumnListFromAccountInfoTable($ID, "groups_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][21] . " </td>\n";
      echo "<td><select name='import_contact_num'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = OcsServer::getColumnListFromAccountInfoTable($ID, "contact_num");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['setup'][88] . " </td>\n";
      echo "<td><select name='import_network'>\n";
      echo "<option value=''>" . $LANG['ocsconfig'][11] . "</option>\n";
      $listColumnOCS = OcsServer::getColumnListFromAccountInfoTable($ID,"networks_id");
      echo $listColumnOCS;
      echo "</select></td></tr>\n";
      echo "</table>";

      echo "</td></tr>\n";

      echo "<tr><th>&nbsp;" . $LANG['ocsconfig'][27] ." ".$LANG['Menu'][3]. "&nbsp;</th>\n";
      echo "<th colspan='2'>&nbsp;</th></tr>\n";

      echo "<tr class='tab_bg_2'>\n";
      echo "<td class='tab_bg_2 top'>\n";

      echo "<table width='100%'>";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][25] . " </td>\n<td>";
      Dropdown::showYesNo("import_monitor_comment", $this->fields["import_monitor_comment"]);
      echo "</td></tr>\n";
      echo "</table>";

      echo "</td>\n";
      echo "<td class='tab_bg_2' colspan='2'>&nbsp;</td>";
      echo "</table>\n";

      echo "<p class='submit'>";
      echo "<input type='submit' name='update_server' class='submit' value='".$LANG['buttons'][2]."'>";
      echo "</p></form></div>\n";
   }


   function ocsFormImportOptions($target, $ID, $withtemplate='', $templateid='') {
      global $LANG;

      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method='post'>";
      echo "<table class='tab_cadre'>\n";
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][59];
      echo "<input type='hidden' name='id' value='$ID'>" . " </td>\n";
      echo "<td><input type='text' size='30' name='ocs_url' value=\"" . $this->fields["ocs_url"] ."\">";
      echo "</td></tr>\n";

      echo "<tr><th colspan='2'>" . $LANG['ocsconfig'][5] . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][17] . " </td>\n";
      echo "<td><input type='text' size='30' name='tag_limit' value='".$this->fields["tag_limit"]."'>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][9] . " </td>\n";
      echo "<td><input type='text' size='30' name='tag_exclude' value='".
                 $this->fields["tag_exclude"]."'></td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][16] . " </td>\n<td>";
      Dropdown::show('State', array('name'   => 'states_id_default',
                                    'value'  => $this->fields["states_id_default"]));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][48] . " </td>\n<td>";
      Dropdown::showFromArray("deconnection_behavior", array(''       => $LANG['buttons'][49],
                                                             "trash"  => $LANG['ocsconfig'][49],
                                                             "delete" => $LANG['ocsconfig'][50]),
                              array('value' => $this->fields["deconnection_behavior"]));
      echo "</td></tr>\n";

      $import_array = array("0" => $LANG['ocsconfig'][11],
                            "1" => $LANG['ocsconfig'][10],
                            "2" => $LANG['ocsconfig'][12]);

      $import_array2 = array("0" => $LANG['ocsconfig'][11],
                             "1" => $LANG['ocsconfig'][10],
                             "2" => $LANG['ocsconfig'][12],
                             "3" => $LANG['ocsconfig'][19]);

      $periph = $this->fields["import_periph"];
      $monitor = $this->fields["import_monitor"];
      $printer = $this->fields["import_printer"];
      $software = $this->fields["import_software"];
      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][16] . " </td>\n<td>";
      Dropdown::showFromArray("import_periph", $import_array, array('value' => $periph));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][3] . " </td>\n<td>";
      Dropdown::showFromArray("import_monitor", $import_array2, array('value' => $monitor));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][2] . " </td>\n<td>";
      Dropdown::showFromArray("import_printer", $import_array, array('value' => $printer));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['Menu'][4] . " </td>\n<td>";
      $import_array = array("0" => $LANG['ocsconfig'][11],
                            "1" => $LANG['ocsconfig'][12]);
      Dropdown::showFromArray("import_software", $import_array, array('value' => $software));
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['computers'][8] . " </td>\n<td>";
      Dropdown::showYesNo("import_disk", $this->fields["import_disk"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][38] . " </td>\n<td>";
      Dropdown::showYesNo("use_soft_dict", $this->fields["use_soft_dict"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][41] . " </td>\n<td>";
      Dropdown::showYesNo("import_registry", $this->fields["import_registry"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['ocsconfig'][40] . " </td>\n<td>";
      Dropdown::showInteger('cron_sync_number', $this->fields["cron_sync_number"], 1, 100, 1,
                            array (0 => $LANG['common'][49]));
      echo "</td></tr></table>\n";

      echo "<br>" . $LANG['ocsconfig'][15];
      echo "<br>" . $LANG['ocsconfig'][14];
      echo "<br>" . $LANG['ocsconfig'][13];

      echo "<p class='submit'><input type='submit' name='update_server' class='submit' value='" .
             $LANG['buttons'][2] . "'></p>";
      echo "</form></div>";
   }


   function ocsFormAutomaticLinkConfig($target, $ID, $withtemplate='', $templateid='') {
      global $LANG;

      if (!haveRight("ocsng", "w")) {
         return false;
      }
      $this->getFromDB($ID);
      echo "<br><div class='center'>";
      echo "<form name='formconfig' action=\"$target\" method='post'>\n";
      echo "<table class='tab_cadre'>\n";
      echo "<tr><th colspan='4'>" . $LANG['ocsconfig'][52];
      echo "<input type='hidden' name='id' value='$ID'></th></tr>\n";

      echo "<tr class='tab_bg_2'><td>" . $LANG['ocsconfig'][53] . " </td>\n<td colspan='3'>";
      Dropdown::showYesNo("is_glpi_link_enabled", $this->fields["is_glpi_link_enabled"]);
      echo "</td></tr>\n";

      echo "<tr><th colspan='4'>" . $LANG['ocsconfig'][54] . "</th></tr>\n";

      echo "<tr class='tab_bg_2'><td>" . $LANG['networking'][14] . " </td>\n<td>";
      Dropdown::showYesNo("use_ip_to_link", $this->fields["use_ip_to_link"]);
      echo "</td>\n";
      echo "<td>" . $LANG['device_iface'][2] . " </td>\n<td>";
      Dropdown::showYesNo("use_mac_to_link", $this->fields["use_mac_to_link"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>" . $LANG['rulesengine'][25] . " </td>\n<td>";
      $link_array = array("0" => $LANG['choice'][0],
                          "1" => $LANG['choice'][1]."&nbsp;: ".$LANG['ocsconfig'][57],
                          "2" => $LANG['choice'][1]."&nbsp;: ".$LANG['ocsconfig'][56]);
      Dropdown::showFromArray("use_name_to_link", $link_array,
                              array('value' => $this->fields["use_name_to_link"]));
      echo "</td>\n";
      echo "<td>" . $LANG['common'][19] . " </td>\n<td>";
      Dropdown::showYesNo("use_serial_to_link", $this->fields["use_serial_to_link"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_2'><td>" . $LANG['ocsconfig'][55] . " </td>\n<td colspan='3'>";
      Dropdown::show('State', array('value' => $this->fields["states_id_linkif"],
                                    'name'  => "states_id_linkif"));
      echo "</td></tr>\n";
      echo "</table><br>".$LANG['ocsconfig'][58];

      echo "<p class='submit'><input type='submit' name='update_server' class='submit' value='" .
             $LANG['buttons'][2] . "'></p>";
      echo "</form></div>";
   }


   /**
    * Print simple ocs config form (database part)
    *
    * @param $ID Integer : Id of the ocs config
    * @param $options array
    *     - target form target
    *
    *@return Nothing (display)
    *
    **/
   function showForm($ID, $options=array()) {
      global $LANG;

      if (!haveRight("ocsng", "w")) {
         return false;
      }

      $rowspan = 6;
      //If no ID provided, or if the server is created using an existing template
      if (empty ($ID)) {
         $this->getEmpty();
         $rowspan++;
      } else {
         $this->getFromDB($ID);
      }

      $this->showTabs($options);

      $out  = "\n<div class='center' id='tabsbody'>";
      $out .= "<form name='formdbconfig' action='".$this->getFormURL()."' method='post'>";
      $out .= "<table class='tab_cadre_fixe'>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][2] . "&nbsp;: </td>\n";
      $out .= "<td class='b'>" . $this->fields["id"] . "</td>\n";
      $out .= "<td class='center' rowspan='$rowspan'>" . $LANG['common'][25] . "&nbsp;: </td>\n";
      $out .= "<td rowspan='$rowspan'>";
      $out .= "<textarea cols='45' rows='4' name='comment' >".$this->fields["comment"]."</textarea>";
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['common'][16] . "&nbsp;: </td>\n";
      $out .= "<td><input type='text' name='name' value=\"" . $this->fields["name"] ."\"></td></tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][2] . "&nbsp;: </td>\n";
      $out .= "<td><input type='text' name='ocs_db_host' value=\"" .
                    $this->fields["ocs_db_host"] ."\"></td></tr>\n";

      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][4] . "&nbsp;: </td>\n";
      $out .= "<td><input type='text' name='ocs_db_name' value=\"" .
                    $this->fields["ocs_db_name"] . "\"></td></tr>\n";

      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][1] . "&nbsp;: </td>\n";
      $out .= "<td><input type='text' name='ocs_db_user' value=\"".$this->fields["ocs_db_user"]."\">";
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'><td class='center'>" . $LANG['ocsconfig'][3] . "&nbsp;: </td>\n";
      $out .= "<td><input type='password' name='ocs_db_passwd' value='' autocomplete='off'></td>";

      $out .= "</tr>\n";
      $out .= "<tr class='tab_bg_1'><td class='center'>" .
                     $LANG['ocsconfig'][7] . "&nbsp;: </td>\n";
      $out .= "<td><select name='ocs_db_utf8'>";
      $out .= "<option value='0'".($this->fields["ocs_db_utf8"] ? "" : "selected") .
               ">" . $LANG['choice'][0] . "</option>";
      $out .= "<option value='1'".($this->fields["ocs_db_utf8"] ? "selected" : "") .
               ">" . $LANG['choice'][1] . "</option>";
      $out .= "</select></td>";

      if (!empty ($ID)) {
         $out .= "<td>".$LANG['common'][26]."&nbsp;: </td>";
         $out .= "<td>";
         $out .= ($this->fields["date_mod"] ? convDateTime($this->fields["date_mod"]) : $LANG['setup'][307]);
         $out .= "</td>";
      }


      $out .= "</tr>\n";

      echo $out;
      $this->showFormButtons($options);
      $this->addDivForTabs();
   }


   function showDBConnectionStatus($ID) {
      global $LANG;

      $out="<br><div class='center'>\n";
      $out.="<table class='tab_cadre'>";
      $out.="<tr><th>" .$LANG['setup'][602] . "</th></tr>\n";
      $out.="<tr class='tab_bg_2'><td class='center'>";
      if ($ID != -1) {
         if (!OcsServer::checkOCSconnection($ID)) {
            $out .= $LANG['ocsng'][21];
         } else if (!OcsServer::checkConfig(1)) {
            $out .= $LANG['ocsng'][20];
         } else if (!OcsServer::checkConfig(2)) {
            $out .= $LANG['ocsng'][42];
         } else if (!OcsServer::checkConfig(4)) {
            $out .= $LANG['ocsng'][43];
         } else if (!OcsServer::checkConfig(8)) {
            $out .= $LANG['ocsng'][44];
         } else {
            $out .= $LANG['ocsng'][18];
            $out .= "</td></tr>\n<tr class='tab_bg_2'><td class='center'>".$LANG['ocsng'][19];
         }
      }
      $out .= "</td></tr>\n";
      $out .= "</table></div>";
      echo $out;
   }


   function prepareInputForUpdate($input) {

      $this->updateAdminInfo($input);
      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }


   function pre_updateInDB() {

      // Update checksum
      $checksum = 0;

      if ($this->fields["import_printer"]) {
         $checksum |= pow(2,self::PRINTERS_FL);
      }
      if ($this->fields["import_software"]) {
         $checksum |= pow(2,self::SOFTWARES_FL);
      }
      if ($this->fields["import_monitor"]) {
         $checksum |= pow(2,self::MONITORS_FL);
      }
      if ($this->fields["import_periph"]) {
         $checksum |= pow(2,self::INPUTS_FL);
      }
      if ($this->fields["import_registry"]) {
         $checksum |= pow(2,self::REGISTRY_FL);
      }
      if ($this->fields["import_disk"]) {
         $checksum |= pow(2,self::DRIVES_FL);
      }
      if ($this->fields["import_ip"]) {
         $checksum |= pow(2,self::NETWORKS_FL);
      }
      if ($this->fields["import_device_port"]) {
         $checksum |= pow(2,self::PORTS_FL);
      }
      if ($this->fields["import_device_modem"]) {
         $checksum |= pow(2,self::MODEMS_FL);
      }
      if ($this->fields["import_device_drive"]) {
         $checksum |= pow(2,self::STORAGES_FL);
      }
      if ($this->fields["import_device_sound"]) {
         $checksum |= pow(2,self::SOUNDS_FL);
      }
      if ($this->fields["import_device_gfxcard"]) {
         $checksum |= pow(2,self::VIDEOS_FL);
      }
      if ($this->fields["import_device_iface"]) {
         $checksum |= pow(2,self::NETWORKS_FL);
      }
      if ($this->fields["import_device_hdd"]) {
         $checksum |= pow(2,self::STORAGES_FL);
      }
      if ($this->fields["import_device_memory"]) {
         $checksum |= pow(2,self::MEMORIES_FL);
      }

      if ($this->fields["import_device_processor"]
          || $this->fields["import_general_contact"]
          || $this->fields["import_general_comment"]
          || $this->fields["import_general_domain"]
          || $this->fields["import_general_os"]
          || $this->fields["import_general_name"]) {

         $checksum |= pow(2,self::HARDWARE_FL);
      }

      if ($this->fields["import_general_manufacturer"]
          || $this->fields["import_general_type"]
          || $this->fields["import_general_model"]
          || $this->fields["import_general_serial"]) {

         $checksum |= pow(2,self::BIOS_FL);
      }

      $this->updates[] = "checksum";
      $this->fields["checksum"] = $checksum;
   }


   function prepareInputForAdd($input) {
      global $LANG, $DB;

      // Check if server config does not exists
      $query = "SELECT *
                FROM `" . $this->getTable() . "`
                WHERE `name` = '".$input['name']."';";
      $result = $DB->query($query);
      if ($DB->numrows($result)>0) {
         addMessageAfterRedirect($LANG['setup'][609],false,ERROR);
         return false;
      }

      if (isset($input["ocs_db_passwd"]) && !empty($input["ocs_db_passwd"])) {
         $input["ocs_db_passwd"] = rawurlencode(stripslashes($input["ocs_db_passwd"]));
      } else {
         unset($input["ocs_db_passwd"]);
      }
      return $input;
   }


   function cleanDBonPurge() {
      global $DB;

      $query = "DELETE
                FROM `glpi_ocslinks`
                WHERE `ocsservers_id` = '".$this->fields['id']."'";
      $result = $DB->query($query);
   }


   /**
    * Update Admin Info retrieve config
    *
    *@param $tab data array
    **/
   function updateAdminInfo($tab) {

      if (isset($tab["import_location"])
          || isset ($tab["import_otherserial"])
          || isset ($tab["import_group"])
          || isset ($tab["import_network"])
          || isset ($tab["import_contact_num"])) {

         $adm = new OcsAdminInfosLink();
         $adm->cleanForOcsServer($tab["id"]);
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


   function showSystemInformations($width) {
      global $LANG;

      $ocsServers = getAllDatasFromTable('glpi_ocsservers');
      if (!empty($ocsServers)) {
         echo "\n<tr class='tab_bg_2'><th>" . $LANG['ocsng'][0] . "</th></tr>\n";
         echo "<tr class='tab_bg_1'><td><pre>\n&nbsp;\n";

         $msg = '';
         foreach($ocsServers as $ocsServer) {
               $msg .= $LANG['ocsconfig'][2]." : '".$ocsServer['ocs_db_host']."'";
               $msg .= ', '.(OcsServer::checkOCSconnection($ocsServer['id'])?$LANG['ocsng'][18]:$LANG['ocsng'][18]);
               $msg .= ', '.$LANG['ocsconfig'][38]. " : ".$ocsServer['use_soft_dict'];
         }
      }
      echo wordwrap($msg."\n", $width, "\n\t\t");
      echo "\n</pre></td></tr>";
   }


   /**
    * Get the ocs server id of a machine, by giving the machine id
    * @param $ID the machine ID
    *
    * @return the ocs server id of the machine
    */
   static function getByMachineID($ID) {
      global $DB;

      $sql = "SELECT `ocsservers_id`
              FROM `glpi_ocslinks`
              WHERE `glpi_ocslinks`.`computers_id` = '$ID'";
      $result = $DB->query($sql);
      if ($DB->numrows($result) > 0) {
         $datas = $DB->fetch_array($result);
         return $datas["ocsservers_id"];
      }
      return -1;
   }


   /**
    * Get an Ocs Server name, by giving his ID
    * @return the ocs server name
    */
   static function getServerNameByID($ID) {

      $ocsservers_id = OcsServer::getByMachineID($ID);
      $conf = OcsServer::getConfig($ocsservers_id);
      return $conf["name"];
   }


      /**
       * Get a random ocsservers_id
       * @return an ocs server id
       */
      static function getRandomServerID() {
         global $DB;

         $sql = "SELECT `id`
                 FROM `glpi_ocsservers`
                 ORDER BY RAND()
                 LIMIT 1";
         $result = $DB->query($sql);
         if ($DB->numrows($result) > 0) {
            $datas = $DB->fetch_array($result);
            return $datas["id"];
         }
         return -1;
   }


   /**
    * Get OCSNG mode configuration
    *
    * Get all config of the OCSNG mode
    *
    * @param $id int : ID of the OCS config (default value 1)
    *@return Value of $confVar fields or false if unfound.
    *
    **/
   static function getConfig($id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_ocsservers`
                WHERE `id` = '$id'";
      $result = $DB->query($query);
      if ($result) {
         $data = $DB->fetch_assoc($result);
      } else {
         $data = 0;
      }
   return $data;
   }


   static function getTagLimit($cfg_ocs) {

      $WHERE = "";
      if (!empty ($cfg_ocs["tag_limit"])) {
         $splitter = explode("$", trim($cfg_ocs["tag_limit"]));
         if (count($splitter)) {
            $WHERE = " `accountinfo`.`TAG` = '" . $splitter[0] . "' ";
            for ($i = 1; $i < count($splitter); $i++) {
               $WHERE .= " OR `accountinfo`.`TAG` = '" .$splitter[$i] . "' ";
            }
         }
      }
      if (!empty ($cfg_ocs["tag_exclude"])) {
         $splitter = explode("$", $cfg_ocs["tag_exclude"]);
         if (count($splitter)) {
            if (!empty($WHERE)) {
               $WHERE .= " AND ";
            }
            $WHERE .= " `accountinfo`.`TAG` <> '" . $splitter[0] . "' ";
            for ($i=1 ; $i<count($splitter) ; $i++){
               $WHERE .= " AND `accountinfo`.`TAG` <> '" .$splitter[$i] . "' ";
            }
         }
      }
      return $WHERE;
   }


   /**
    * Make the item link between glpi and ocs.
    *
    * This make the database link between ocs and glpi databases
    *
    *@param $ocsid integer : ocs item unique id.
    *@param $ocsservers_id integer : ocs server id
    *@param $glpi_computers_id integer : glpi computer id
    *
    *@return integer : link id.
    *
    **/
   static function ocsLink($ocsid, $ocsservers_id, $glpi_computers_id) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);

      // Need to get device id due to ocs bug on duplicates
      $query_ocs = "SELECT *
                    FROM `hardware`
                    WHERE `ID` = '$ocsid';";
      $result_ocs = $DBocs->query($query_ocs);
      $data = $DBocs->fetch_array($result_ocs);

      $query = "INSERT INTO `glpi_ocslinks`
                       (`computers_id`, `ocsid`, `ocs_deviceid`, `last_update`, `ocsservers_id`)
                VALUES ('$glpi_computers_id','$ocsid','" . $data["DEVICEID"] . "','" .
                        $_SESSION["glpi_currenttime"] . "','$ocsservers_id')";
      $result = $DB->query($query);

      if ($result) {
         return ($DB->insert_id());
      }
      return false;
   }


   static function linkComputer($ocsid, $ocsservers_id, $computers_id, $link_auto=0) {
      global $DB, $DBocs, $LANG;

      OcsServer::checkOCSconnection($ocsservers_id);

      $query = "SELECT *
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      $result = $DB->query($query);
      $ocs_id_change = false;
      $ocs_link_exists = false;
      $numrows = $DB->numrows($result);
      // Already link - check if the OCS computer already exists
      if ($numrows > 0) {
         $ocs_link_exists = false;
         $data = $DB->fetch_assoc($result);
         $query = "SELECT *
                   FROM `hardware`
                   WHERE `ID` = '" . $data["ocsid"] . "'";
         $result_ocs = $DBocs->query($query);
         // Not found
         if ($DBocs->numrows($result_ocs)==0) {
            $ocs_id_change = true;
            $idlink = $data["id"];
            $query = "UPDATE `glpi_ocslinks`
                      SET `ocsid` = '$ocsid'
                      WHERE `id` = '" . $data["id"] . "'";
            $DB->query($query);

            //Add history to indicates that the ocsid changed
            $changes[0] = '0';
            //Old ocsid
            $changes[1] = $data["ocsid"];
            //New ocsid
            $changes[2] = $ocsid;
            Log::history($computers_id, 'Computer', $changes, 0, HISTORY_OCS_IDCHANGED);
         }
      }
      // No ocs_link or ocs id change does not exists so can link
      if ($ocs_id_change || !$ocs_link_exists) {
         $ocsConfig = OcsServer::getConfig($ocsservers_id);
         // Set OCS checksum to max value
         $query = "UPDATE `hardware`
                   SET `CHECKSUM` = '" . self::MAX_CHECKSUM . "'
                   WHERE `ID` = '$ocsid'";
         $DBocs->query($query);

         if ($ocs_id_change || $idlink = OcsServer::ocsLink($ocsid, $ocsservers_id, $computers_id)) {
            $comp = new Computer;
            $comp->getFromDB($computers_id);
            $input["id"] = $computers_id;
            $input["is_ocs_import"] = 1;
            // Not already import from OCS / mark default state
            if (!$ocs_id_change
                && ($link_auto
                    || (!$comp->fields['is_ocs_import'] && $ocsConfig["states_id_default"]>0))) {
               $input["states_id"] = $ocsConfig["states_id_default"];
            }
            $comp->update($input);
            // Auto restore if deleted
            if ($comp->fields['is_deleted']) {
               $comp->restore(array('id' => $computers_id));
            }
            // Reset using GLPI Config
            $cfg_ocs = OcsServer::getConfig($ocsservers_id);
//             $query = "SELECT *
//                       FROM `hardware`
//                       WHERE `ID` = '$ocsid'";
//             $result = $DBocs->query($query);
//             $line = $DBocs->fetch_array($result);

            // Reset only if not in ocs id change case
            if (!$ocs_id_change) {
               if ($cfg_ocs["import_general_os"]) {
                  OcsServer::resetDropdown($computers_id, "operatingsystems_id",
                                           "glpi_operatingsystems");
               }
               if ($cfg_ocs["import_device_processor"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceProcessor');
               }
               if ($cfg_ocs["import_device_iface"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceNetworkCard');
               }
               if ($cfg_ocs["import_device_memory"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceMemory');
               }
               if ($cfg_ocs["import_device_hdd"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceHardDrive');
               }
               if ($cfg_ocs["import_device_sound"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceSoundCard');
               }
               if ($cfg_ocs["import_device_gfxcard"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceGraphicCard');
               }
               if ($cfg_ocs["import_device_drive"]) {
                  Computer_Device::resetDevices($computers_id, 'DeviceDrive');
               }
               if ($cfg_ocs["import_device_modem"] || $cfg_ocs["import_device_port"]) {
                  Computer_Device::resetDevices($computers_id, 'DevicePci');
               }
               if ($cfg_ocs["import_software"]) {
                  OcsServer::resetSoftwares($computers_id);
               }
               if ($cfg_ocs["import_disk"]) {
                  OcsServer::resetDisks($computers_id);
               }
               if ($cfg_ocs["import_periph"]) {
                  OcsServer::resetPeripherals($computers_id);
               }
               if ($cfg_ocs["import_monitor"]==1) { // Only reset monitor as global in unit management
                  OcsServer::resetMonitors($computers_id);    // try to link monitor with existing
               }
               if ($cfg_ocs["import_printer"]) {
                  OcsServer::resetPrinters($computers_id);
               }
               if ($cfg_ocs["import_registry"]) {
                  OcsServer::resetRegistry($computers_id);
               }
               $changes[0] = '0';
               $changes[1] = "";
               $changes[2] = $ocsid;
               Log::history($computers_id, 'Computer', $changes, 0, HISTORY_OCS_LINK);
            }

            OcsServer::updateComputer($idlink, $ocsservers_id, 0);
            return true;
         }

      } else {
         addMessageAfterRedirect($ocsid . " - " . $LANG['ocsng'][23],false,ERROR);
      }
      return false;
   }


   static function processComputer($ocsid, $ocsservers_id, $lock = 0, $defaultentity = -1,
                                   $defaultlocation = -1, $canlink=0) {
      global $DB;

      OcsServer::checkOCSconnection($ocsservers_id);
      $comp = new Computer;

      //Check it machine is already present AND was imported by OCS AND still present in GLPI
      $query = "SELECT `glpi_ocslinks`.`id`, `computers_id`, `ocsid`
                FROM `glpi_ocslinks`
                LEFT JOIN `glpi_computers` ON `glpi_computers`.`id`=`glpi_ocslinks`.`computers_id`
                WHERE `glpi_computers`.`id` IS NOT NULL
                      AND `ocsid` = '$ocsid'
                      AND `ocsservers_id` = '$ocsservers_id'";
      $result_glpi_ocslinks = $DB->query($query);

      if ($DB->numrows($result_glpi_ocslinks)) {
         $datas = $DB->fetch_array($result_glpi_ocslinks);
         //Return code to indicates that the machine was synchronized
         //or only last inventory date changed
         return OcsServer::updateComputer($datas["id"], $ocsservers_id, 1, 0);
      }
      return OcsServer::importComputer($ocsid, $ocsservers_id, $lock, $defaultentity,
                                       $defaultlocation, $canlink);
   }


   static function checkConfig($what=1) {
      global $DBocs;

      # Check OCS version
      if ($what & 1) {
         $result = $DBocs->query("SELECT `TVALUE`
                                  FROM `config`
                                  WHERE `NAME` = 'GUI_VERSION'");
         if ($DBocs->numrows($result) != 1 || $DBocs->result($result, 0, 0) < 4020) {
            return false;
         }
      }
      # Check TRACE_DELETED in CONFIG
      if ($what & 2) {
         $result = $DBocs->query("SELECT `IVALUE`
                                  FROM `config`
                                  WHERE `NAME` = 'TRACE_DELETED'");
         if ($DBocs->numrows($result) != 1 || $DBocs->result($result, 0, 0) != 1) {
            $query = "UPDATE `config`
                      SET `IVALUE` = '1'
                      WHERE `NAME` = 'TRACE_DELETED'";

            if (!$DBocs->query($query)) {
               return false;
            }
         }
      }
      // Check write access on hardware.CHECKSUM
      if ($what & 4) {
         if (!$DBocs->query("UPDATE `hardware`
                             SET `CHECKSUM` = CHECKSUM
                             LIMIT 1")) {
         return false;
         }
      }
      // Check delete access on deleted_equiv
      if ($what & 8) {
         if (!$DBocs->query("DELETE
                             FROM `deleted_equiv`
                             LIMIT 0")) {
            return false;
         }
      }
      return true;
   }


   static function manageDeleted($ocsservers_id) {
      global $DB, $DBocs;

      if (!(OcsServer::checkOCSconnection($ocsservers_id) && OcsServer::checkConfig(1))) {
         return false;
      }

      $query = "SELECT *
                FROM `deleted_equiv`
                ORDER BY `DATE`";
      $result = $DBocs->query($query);
      if ($DBocs->numrows($result)) {
         $deleted = array ();
         while ($data = $DBocs->fetch_array($result)) {
            $deleted[$data["DELETED"]] = $data["EQUIVALENT"];
         }
         if (count($deleted)) {
            foreach ($deleted as $del => $equiv) {
               if (!empty ($equiv) && !is_null($equiv)) { // New name
                  // Get hardware due to bug of duplicates management of OCS
                  if (strstr($equiv,"-")) {
                     $query_ocs = "SELECT *
                                   FROM `hardware`
                                   WHERE `DEVICEID` = '$equiv'";
                     $result_ocs = $DBocs->query($query_ocs);
                     if ($data = $DBocs->fetch_array($result_ocs)) {
                        $query = "UPDATE `glpi_ocslinks`
                                  SET `ocsid` = '" . $data["ID"] . "',
                                      `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                  WHERE `ocs_deviceid` = '$del'
                                        AND `ocsservers_id` = '$ocsservers_id'";
                        $DB->query($query);

                        //Update hardware checksum due to a bug in OCS
                        //(when changing netbios name, software checksum is set instead of hardware checksum...)
                        $querychecksum = "UPDATE `hardware`
                                          SET `CHECKSUM` = (CHECKSUM | ".pow(2, self::HARDWARE_FL).")
                                          WHERE `ID` = '".$data["ID"]."'";
                        $DBocs->query($querychecksum);
                     }

                  } else {
                     $query_ocs = "SELECT *
                                   FROM `hardware`
                                   WHERE `ID` = '$equiv'";
                     $result_ocs = $DBocs->query($query_ocs);
                     if ($data = $DBocs->fetch_array($result_ocs)) {
                        $query = "UPDATE `glpi_ocslinks`
                                  SET `ocsid` = '" . $data["ID"] . "',
                                      `ocs_deviceid` = '" . $data["DEVICEID"] . "'
                                  WHERE `ocsid` = '$del'
                                        AND `ocsservers_id` = '$ocsservers_id'";
                        $DB->query($query);

                        //Update hardware checksum due to a bug in OCS
                        //(when changing netbios name, software checksum is set instead of hardware checksum...)
                        $querychecksum = "UPDATE `hardware`
                                          SET `CHECKSUM` = (CHECKSUM | ".pow(2, self::HARDWARE_FL).")
                                          WHERE `ID` = '".$data["ID"]."'";
                        $DBocs->query($querychecksum);
                     }
                  }
                  if ($data) {
                     $sql_id = "SELECT `computers_id`
                                FROM `glpi_ocslinks`
                                WHERE `ocsid` = '".$data["ID"]."'
                                      AND `ocsservers_id` = '$ocsservers_id'";
                     if ($res_id = $DB->query($sql_id)) {
                        if ($DB->numrows($res_id)>0) {
                           //Add history to indicates that the ocsid changed
                           $changes[0] = '0';
                           //Old ocsid
                           $changes[1] = $del;
                           //New ocsid
                           $changes[2] = $data["ID"];
                           Log::history($DB->result($res_id, 0, "computers_id"), 'Computer',
                                        $changes, 0, HISTORY_OCS_IDCHANGED);
                        }
                     }
                  }

               } else { // Deleted
                  if (strstr($del,"-")) {
                     $query = "SELECT *
                               FROM `glpi_ocslinks`
                               WHERE `ocs_deviceid` = '$del'
                                     AND `ocsservers_id` = '$ocsservers_id'";
                  } else {
                     $query = "SELECT *
                               FROM `glpi_ocslinks`
                               WHERE `ocsid` = '$del'
                                     AND `ocsservers_id` = '$ocsservers_id'";
                  }
                  if ($result = $DB->query($query)) {
                     if ($DB->numrows($result)>0) {
                        $data = $DB->fetch_array($result);
                        $comp = new Computer();
                        $comp->delete( array("id" => $data["computers_id"]), 0);

                        //Add history to indicates that the machine was deleted from OCS
                        $changes[0] = '0';
                        $changes[1] = $data["ocsid"];
                        $changes[2] = "";
                        Log::history($data["computers_id"], 'Computer', $changes, 0,
                                     HISTORY_OCS_DELETE);

                        $query = "DELETE
                                  FROM `glpi_ocslinks`
                                  WHERE `id` ='" . $data["id"] . "'";
                        $DB->query($query);
                     }
                  }
               }
               // Delete item in DB
               $equiv_clean=" `EQUIVALENT` = '$equiv'";
               if (empty($equiv)) {
                  $equiv_clean=" (`EQUIVALENT` = '$equiv'
                                OR `EQUIVALENT` IS NULL ) ";
               }
               $query="DELETE
                       FROM `deleted_equiv`
                       WHERE `DELETED` = '$del'
                             AND $equiv_clean";
               $DBocs->query($query);
            }
         }
      }
   }


   static function importComputer($ocsid, $ocsservers_id, $lock = 0, $defaultentity = -1,
                                  $defaultlocation = -1, $canlink=0) {
      global $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      $comp = new Computer;

      // Set OCS checksum to max value
      $query = "UPDATE `hardware`
                SET `CHECKSUM` = '" . self::MAX_CHECKSUM . "'
                WHERE `ID` = '$ocsid'";
      $DBocs->query($query);

      //No entity predefined, check rules
      if ($defaultentity == -1) {
         //Try to affect computer to an entity
         $rule = new RuleOcsCollection($ocsservers_id);
         $data = array ();
         $data = $rule->processAllRules(array (), array (), $ocsid);
      } else {
         //An entity has already been defined via the web interface
         $data['entities_id'] = $defaultentity;
         $data['locations_id'] = $defaultlocation;
      }

      //Try to match all the rules, return the first good one, or null if not rules matched
      if (isset ($data['entities_id']) && $data['entities_id']>=0) {
         if ($lock) {
            while (!$fp = OcsServer::setEntityLock($data['entities_id'])) {
               sleep(1);
            }
         }
         //Check if machine could be linked with another one already in DB
         if ($canlink) {
            $found_computers = OcsServer::getComputersAlreadyImported($ocsid, $ocsservers_id,
                                                                      $data['entities_id']);
            // machines founded -> try to link
            if (is_array($found_computers) && count($found_computers)>0) {
               foreach ($found_computers as $computers_id) {
                  if (OcsServer::linkComputer($ocsid, $ocsservers_id, $computers_id, $canlink)) {
                     return self::COMPUTER_LINKED;
                  }
               }
            }
            // Else simple Import
         }

         //New machine to import
         $query = "SELECT *
                   FROM `hardware`
                   WHERE `ID`='$ocsid'";
         $result = $DBocs->query($query);
         if ($result && $DBocs->numrows($result) == 1) {
            $line = $DBocs->fetch_array($result);
            $line = clean_cross_side_scripting_deep(addslashes_deep($line));
            $cfg_ocs = OcsServer::getConfig($ocsservers_id);
            $input = array ();
            $input["entities_id"] = $data['entities_id'];
            $input["name"] = $line["NAME"];
            $input["is_ocs_import"] = 1;
            if ($cfg_ocs["states_id_default"]>0) {
               $input["states_id"] = $cfg_ocs["states_id_default"];
            }
            $computers_id = $comp->add($input);
            $ocsid = $line['ID'];
            $changes[0] = '0';
            $changes[1] = "";
            $changes[2] = $ocsid;
            Log::history($computers_id, 'Computer', $changes, 0, HISTORY_OCS_IMPORT);

            if ($idlink = OcsServer::ocsLink($line['ID'], $ocsservers_id, $computers_id)) {
               OcsServer::updateComputer($idlink, $ocsservers_id, 0);
            }
         }
         if ($lock) {
            OcsServer::removeEntityLock($data['entities_id'], $fp);
         }

         //Return code to indicates that the machine was imported
         return self::COMPUTER_IMPORTED;
      }
      //ELSE Return code to indicates that the machine was not imported because it doesn't matched rules
      return self::COMPUTER_FAILED_IMPORT;
   }


   /** Return array of GLPI computers matching the OCS one using the OCS config
   * @param $ocsid integer : ocs ID of the computer
   * @param $ocsservers_id integer : ocs server ID
   * @param $entity integer : entity ID
   *
   * @return array containing the glpi computer ID
   */
   static function getComputersAlreadyImported($ocsid, $ocsservers_id, $entity) {
      global $DB, $DBocs;

      $conf = OcsServer::getConfig($ocsservers_id);
      $found_computers=array();

      $sql_fields = "`hardware`.`ID`";
      $sql_from = "`hardware`";
      $first = true;
      $ocsParams = array();

      if ($conf["is_glpi_link_enabled"]) {
         $ok = false;
         //Build the request against OCS database to get the machine's informations
         if ( $conf["use_ip_to_link"] || $conf["use_mac_to_link"]) {
            $sql_from .= " LEFT JOIN `networks` ON (`hardware`.`ID`=`networks`.`HARDWARE_ID`) ";
         }
         if ($conf["use_ip_to_link"]) {
            $sql_fields .= ", `networks`.`IPADDRESS`";
            $ocsParams["IPADDRESS"] = array();
            $ok = true;
         }
         if ($conf["use_mac_to_link"]) {
            $sql_fields .= ", `networks`.`MACADDR`";
            $ocsParams["MACADDR"] = array();
            $ok = true;
         }
         if ($conf["use_serial_to_link"]) {
            $sql_from .= " LEFT JOIN `bios` ON (`bios`.`HARDWARE_ID`=`hardware`.`ID`) ";
            $sql_fields .= ", `bios`.`SSN`";
            $ocsParams["SSN"] = array();
            $ok = true;
         }
         if ($conf["use_name_to_link"]>0) {
            $sql_fields .= ", `hardware`.`NAME`";
            $ocsParams["NAME"] = array();
            $ok = true;
         }
         // No criteria to link
         if (!$ok) {
            return -1;
         }

         //Execute request
         $sql = "SELECT ".$sql_fields."
                 FROM $sql_from
                 WHERE `hardware`.`ID` = '$ocsid';";
         $result = $DBocs->query($sql);

         //Get the list of parameters

         while ($dataOcs = $DBocs->fetch_array($result)) {
            if ($conf["use_ip_to_link"]) {
               if (!empty($dataOcs["IPADDRESS"]) && !in_array($dataOcs["IPADDRESS"],
                                                              $ocsParams["IPADDRESS"])) {
                  $ocsParams["IPADDRESS"][] = $dataOcs["IPADDRESS"];
               }
            }
            if ($conf["use_mac_to_link"]) {
               if (!empty($dataOcs["MACADDR"]) && !in_array($dataOcs["MACADDR"],
                                                            $ocsParams["MACADDR"])) {
                  $ocsParams["MACADDR"][] = $dataOcs["MACADDR"];
               }
            }
            if ($conf["use_name_to_link"] > 0) {
               if (!empty($dataOcs["NAME"]) && !in_array($dataOcs["NAME"], $ocsParams["NAME"])) {
                  $ocsParams["NAME"][] = $dataOcs["NAME"];
               }
            }
            if ($conf["use_serial_to_link"]) {
               if (!empty($dataOcs["SSN"]) && !in_array($dataOcs["SSN"], $ocsParams["SSN"])) {
                  $ocsParams["SSN"][] = $dataOcs["SSN"];
               }
            }
         }
         //Build the request to check if the machine exists in GLPI
         if (is_array($entity)) {
            $where_entity = implode($entity,',');
         } else {
            $where_entity = $entity;
         }
         $sql_where = " `entities_id` IN ($where_entity)
                       AND `is_template` = '0' ";
         $sql_from = "`glpi_computers`";
         if ( $conf["use_ip_to_link"] || $conf["use_mac_to_link"]) {
            $sql_from .= " LEFT JOIN `glpi_networkports`
                              ON (`glpi_computers`.`id`=`glpi_networkports`.`items_id`
                                  AND `glpi_networkports`.`itemtype` = 'Computer') ";
         }
         if ($conf["use_ip_to_link"]) {
            if (empty($ocsParams["IPADDRESS"])) {
               return -1;
            } else {
               $sql_where .= " AND `glpi_networkports`.`ip` IN ";
               for ($i=0 ; $i<count($ocsParams["IPADDRESS"]) ; $i++) {
                  $sql_where .= ($i>0 ? ',"' : '("').$ocsParams["IPADDRESS"][$i].'"';
               }
               $sql_where .= ")";
            }
         }
         if ($conf["use_mac_to_link"]) {
            if (empty($ocsParams["MACADDR"])) {
               return -1;
            } else {
               $sql_where .= " AND `glpi_networkports`.`mac` IN ";
               for ($i=0 ; $i<count($ocsParams["MACADDR"]) ; $i++) {
                  $sql_where .= ($i>0 ? ',"' : '("').$ocsParams["MACADDR"][$i].'"';
               }
               $sql_where .= ")";
            }
         }
         if ($conf["use_name_to_link"] > 0) {
            //Search only computers with blank name
            if ($conf["use_name_to_link"] == 2) {
               $sql_where .= " AND (`glpi_computers`.`name`='' OR `glpi_computers`.`name` IS NULL) ";
            } else {
               if (empty($ocsParams["NAME"])) {
                  return -1;
               } else {
                  $sql_where .= " AND `glpi_computers`.`name`='".$ocsParams["NAME"][0]."'";
               }
            }
         }
         if ($conf["use_serial_to_link"]) {
            if (empty($ocsParams["SSN"])) {
               return -1;
            } else {
               $sql_where .= " AND `glpi_computers`.`serial`='".$ocsParams["SSN"][0]."'";
            }
         }
         if ($conf["states_id_linkif"] > 0) {
            $sql_where .= " AND `glpi_computers`.`states_id`='".$conf["states_id_linkif"]."'";
         }

         $sql_glpi = "SELECT `glpi_computers`.`id`
                      FROM $sql_from
                      WHERE $sql_where
                      ORDER BY `glpi_computers`.`is_deleted` ASC";
         $result_glpi = $DB->query($sql_glpi);

         if ($DB->numrows($result_glpi) > 0) {
            while ($data=$DB->fetch_array($result_glpi)) {
               $found_computers[] = $data['id'];
            }
         }
      }
      return $found_computers;
   }


   /** Update a ocs computer
   * @param $ID integer : ID of ocslinks row
   * @param $ocsservers_id integer : ocs server ID
   * @param $dohistory bool : do history ?
   * @param $force bool : force update ?
   * @return action done
   */
   static function updateComputer($ID, $ocsservers_id, $dohistory, $force = 0) {
      global $DB, $DBocs, $CFG_GLPI;

      OcsServer::checkOCSconnection($ocsservers_id);
      $cfg_ocs = OcsServer::getConfig($ocsservers_id);

      $query = "SELECT *
                FROM `glpi_ocslinks`
                WHERE `id` = '$ID'
                      AND `ocsservers_id` = '$ocsservers_id'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $line = $DB->fetch_assoc($result);
         $comp = new Computer;
         $comp->getFromDB($line["computers_id"]);
         // Get OCS ID
         $query_ocs = "SELECT *
                       FROM `hardware`
                       WHERE `ID` = '" . $line['ocsid'] . "'";
         $result_ocs = $DBocs->query($query_ocs);
         // Need do history to be 2 not to lock fields
         if ($dohistory) {
            $dohistory = 2;
         }
         if ($DBocs->numrows($result_ocs) == 1) {
            $data_ocs = addslashes_deep($DBocs->fetch_array($result_ocs));
            // update last_update and and last_ocs_update
            $query = "UPDATE `glpi_ocslinks`
                      SET `last_update` = '" . $_SESSION["glpi_currenttime"] . "',
                          `last_ocs_update` = '" . $data_ocs["LASTDATE"] . "',
                          `ocs_agent_version` = '".$data_ocs["USERAGENT"]." '
                      WHERE `id` = '$ID'";
            $DB->query($query);
            if ($force) {
               $ocs_checksum = self::MAX_CHECKSUM;
               $query_ocs = "UPDATE `hardware`
                             SET `CHECKSUM` = (" . self::MAX_CHECKSUM . ")
                             WHERE `ID` = '" . $line['ocsid'] . "'";
               $DBocs->query($query_ocs);
            } else {
               $ocs_checksum = $data_ocs["CHECKSUM"];
            }
            $mixed_checksum = intval($ocs_checksum) & intval($cfg_ocs["checksum"]);

            //By default log history
            $loghistory["history"] = 1;

            // Is an update to do ?
            if ($mixed_checksum) {
               // Get updates on computers :
               $computer_updates = importArrayFromDB($line["computer_update"]);
               if (!in_array(self::IMPORT_TAG_078, $computer_updates)) {
                  $computer_updates = OcsServer::migrateComputerUpdates($line["computers_id"],
                                                                        $computer_updates);
               }
               // Update Administrative informations
               OcsServer::updateAdministrativeInfo($line['computers_id'], $line['ocsid'],
                                                   $ocsservers_id, $cfg_ocs, $computer_updates,
                                                   $comp->fields['entities_id'], $dohistory);
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  $loghistory = OcsServer::updateHardware($line['computers_id'], $line['ocsid'],
                                                          $ocsservers_id, $cfg_ocs,
                                                          $computer_updates, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::BIOS_FL)) {
                  OcsServer::updateBios($line['computers_id'], $line['ocsid'], $ocsservers_id,
                                        $cfg_ocs, $computer_updates, $dohistory);
               }
               // Get import devices
               $import_device = importArrayFromDB($line["import_device"]);
               // Migrate import device to manage several link tables
               if (!in_array(self::IMPORT_TAG_078,$import_device)) {
                  $import_device = OcsServer::migrateImportDevice($line['computers_id'],
                                                                  $import_device);
               }

               if ($mixed_checksum & pow(2, self::MEMORIES_FL)) {
                  OcsServer::updateDevices(self::RAM_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::STORAGES_FL)) {
                  OcsServer::updateDevices(self::HDD_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
                  OcsServer::updateDevices(self::DRIVE_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::HARDWARE_FL)) {
                  OcsServer::updateDevices(self::PROCESSOR_DEVICE, $line['computers_id'],
                                           $line['ocsid'], $ocsservers_id, $cfg_ocs, $import_device,
                                           '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::VIDEOS_FL)) {
                  OcsServer::updateDevices(self::GFX_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::SOUNDS_FL)) {
                  OcsServer::updateDevices(self::SND_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::NETWORKS_FL)) {
                  $import_ip = importArrayFromDB($line["import_ip"]);
                  OcsServer::updateDevices(self::NETWORK_DEVICE, $line['computers_id'],
                                           $line['ocsid'], $ocsservers_id, $cfg_ocs, $import_device,
                                           $import_ip, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::MODEMS_FL)
                   || $mixed_checksum & pow(2, self::PORTS_FL)) {

                  OcsServer::updateDevices(self::PCI_DEVICE, $line['computers_id'], $line['ocsid'],
                                           $ocsservers_id, $cfg_ocs, $import_device, '', $dohistory);
               }
               if ($mixed_checksum & pow(2, self::MONITORS_FL)) {
                  // Get import monitors
                  $import_monitor = importArrayFromDB($line["import_monitor"]);
                  OcsServer::updatePeripherals('Monitor', $comp->fields["entities_id"],
                                               $line['computers_id'], $line['ocsid'], $ocsservers_id,
                                               $cfg_ocs, $import_monitor, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::PRINTERS_FL)) {
                  // Get import printers
                  $import_printer = importArrayFromDB($line["import_printer"]);
                  OcsServer::updatePeripherals('Printer', $comp->fields["entities_id"],
                                               $line['computers_id'], $line['ocsid'], $ocsservers_id,
                                               $cfg_ocs, $import_printer, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::INPUTS_FL)) {
                  // Get import peripheral
                  $import_peripheral = importArrayFromDB($line["import_peripheral"]);
                  OcsServer::updatePeripherals('Peripheral', $comp->fields["entities_id"],
                                               $line['computers_id'], $line['ocsid'], $ocsservers_id,
                                               $cfg_ocs, $import_peripheral, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::SOFTWARES_FL)) {
                  // Get import software
                  $import_software = importArrayFromDB($line["import_software"]);
                  OcsServer::updateSoftware($line['computers_id'], $comp->fields["entities_id"],
                                            $line['ocsid'], $ocsservers_id, $cfg_ocs,
                                            $import_software,
                                            (!$loghistory["history"]?0:$dohistory));
               }
               if ($mixed_checksum & pow(2, self::DRIVES_FL)) {
                  // Get import drives
                  $import_disk = importArrayFromDB($line["import_disk"]);
                  OcsServer::updateDisk($line['computers_id'], $line['ocsid'], $ocsservers_id,
                                        $cfg_ocs, $import_disk, $dohistory);
               }
               if ($mixed_checksum & pow(2, self::REGISTRY_FL)) {
                  //import registry entries not needed
                  OcsServer::updateRegistry($line['computers_id'], $line['ocsid'], $ocsservers_id,
                                            $cfg_ocs);
               }

               // Update OCS Cheksum
               $query_ocs = "UPDATE `hardware`
                             SET `CHECKSUM` = (CHECKSUM - $mixed_checksum)
                             WHERE `ID` = '" . $line['ocsid'] . "'";
               $DBocs->query($query_ocs);

               //Return code to indicate that computer was synchronized
               return self::COMPUTER_SYNCHRONIZED;

            }
            // ELSE Return code to indicate only last inventory date changed
            return self::COMPUTER_NOTUPDATED;
         }
      }
   }


   /**
    * Update the computer hardware configuration
    *
    * Update the computer hardware configuration
    *
    *@param $computers_id integer : ocs computer id.
    *@param $ocsid integer : glpi computer id
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $computer_updates array : already updated fields of the computer
    *@param $dohistory log updates on history ?
    *
    *@return nothing.
    *
    **/
   static function updateHardware($computers_id, $ocsid, $ocsservers_id, $cfg_ocs,
                                  $computer_updates, $dohistory = 2) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);

      $query = "SELECT *
                FROM `hardware`
                WHERE `ID` = '$ocsid'";
      $result = $DBocs->query($query);

      $logHistory = 1;

      if ($DBocs->numrows($result) == 1) {
         $line = $DBocs->fetch_assoc($result);
         $line = clean_cross_side_scripting_deep(addslashes_deep($line));
         $compupdate = array ();
         if ($cfg_ocs["import_os_serial"] && !in_array("os_license_number", $computer_updates)) {
            if (!empty ($line["WINPRODKEY"])) {
               $compupdate["os_license_number"] = $line["WINPRODKEY"];
            }
            if (!empty ($line["WINPRODID"])) {
               $compupdate["os_licenseid"] = $line["WINPRODID"];
            }
         }
         $sql_computer = "SELECT `glpi_operatingsystems`.`name` AS os_name,
                                 `glpi_operatingsystemservicepacks`.`name` AS os_sp
                          FROM `glpi_computers`, `glpi_ocslinks`, `glpi_operatingsystems`,
                               `glpi_operatingsystemservicepacks`
                          WHERE `glpi_ocslinks`.`computers_id`=`glpi_computers`.`id`
                                AND `glpi_operatingsystems`.`id`=`glpi_computers`.`operatingsystems_id`
                                AND `glpi_operatingsystemservicepacks`.`id`
                                     =`glpi_computers`.`operatingsystemservicepacks_id`
                                AND `glpi_ocslinks`.`ocsid`='$ocsid'
                                AND `glpi_ocslinks`.`ocsservers_id`='$ocsservers_id'";

         $res_computer = $DB->query($sql_computer);
         if ($DB->numrows($res_computer) ==  1) {
            $data_computer = $DB->fetch_array($res_computer);
            $computerOS = $data_computer["os_name"];
            $computerOSSP = $data_computer["os_sp"];

            //Do not log software history in case of OS or Service Pack change
            if (!$dohistory
                || $computerOS != $line["OSNAME"]
                || $computerOSSP != $line["OSCOMMENTS"]) {

               $logHistory = 0;
            }
         }
         if ($cfg_ocs["import_general_os"]) {
            if (!in_array("operatingsystems_id", $computer_updates)) {
               $osname=$line["OSNAME"];
               // Hack for OCS encoding problems
               if (!$cfg_ocs["ocs_db_utf8"] && !seems_utf8($osname)) {
                  $osname = encodeInUtf8($osname);
               }
               $compupdate["operatingsystems_id"] = Dropdown::importExternal('OperatingSystem',
                                                                             $osname);
            }
            if (!in_array("operatingsystemversions_id", $computer_updates)) {
               $compupdate["operatingsystemversions_id"]
                     = Dropdown::importExternal('OperatingSystemVersion', $line["OSVERSION"]);
            }
            if (!strpos($line["OSCOMMENTS"],"CEST")
                && !in_array("operatingsystemservicepacks_id", $computer_updates)) {// Not linux comment

               $compupdate["operatingsystemservicepacks_id"]
                     = Dropdown::importExternal('OperatingSystemServicePack', $line["OSCOMMENTS"]);
            }
         }
         if ($cfg_ocs["import_general_domain"] && !in_array("domains_id", $computer_updates)) {
            $compupdate["domains_id"] = Dropdown::importExternal('Domain', $line["WORKGROUP"]);
         }
         if ($cfg_ocs["import_general_contact"] && !in_array("contact", $computer_updates)) {
            $compupdate["contact"] = $line["USERID"];
            $query = "SELECT `id`
                      FROM `glpi_users`
                      WHERE `name` = '" . $line["USERID"] . "';";
            $result = $DB->query($query);
            if ($DB->numrows($result) == 1 && !in_array("users_id", $computer_updates)) {
               $compupdate["users_id"] = $DB->result($result, 0, 0);
            }
         }
         if ($cfg_ocs["import_general_name"] && !in_array("name", $computer_updates)) {
            $compupdate["name"] = $line["NAME"];
         }
         if ($cfg_ocs["import_general_comment"] && !in_array("comment", $computer_updates)) {
            $compupdate["comment"] = "";
            if (!empty ($line["DESCRIPTION"]) && $line["DESCRIPTION"] != NOT_AVAILABLE) {
               $compupdate["comment"] .= $line["DESCRIPTION"] . "\r\n";
            }
            $compupdate["comment"] .= "Swap: " . $line["SWAP"];
         }
         if (count($compupdate)) {
            $compupdate["id"] = $computers_id;
            $comp = new Computer();
            $comp->update($compupdate, $dohistory);
         }
      }
      return array("history"=>$logHistory);
   }


   /**
    * Update the computer bios configuration
    *
    * Update the computer bios configuration
    *
    *@param $computers_id integer : ocs computer id.
    *@param $ocsid integer : glpi computer id
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $computer_updates array : already updated fields of the computer
    *@param $dohistory boolean : log changes ?
    *
    *@return nothing.
    *
    **/
   static function updateBios($computers_id, $ocsid, $ocsservers_id, $cfg_ocs, $computer_updates,
                              $dohistory = 2) {
      global $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);

      $query = "SELECT *
                FROM `bios`
                WHERE `HARDWARE_ID` = '$ocsid'";
      $result = $DBocs->query($query);
      $compupdate = array ();
      if ($DBocs->numrows($result) == 1) {
         $line = $DBocs->fetch_assoc($result);
         $line = clean_cross_side_scripting_deep(addslashes_deep($line));
         $compudate = array ();
         if ($cfg_ocs["import_general_serial"] && !in_array("serial", $computer_updates)) {
            $compupdate["serial"] = $line["SSN"];
         }
         if ($cfg_ocs["import_general_model"] && !in_array("computermodels_id", $computer_updates)) {
            $compupdate["computermodels_id"]= Dropdown::importExternal('ComputerModel',
                                                                        $line["SMODEL"], -1,
                 (isset($line["SMANUFACTURER"])?array("manufacturer" => $line["SMANUFACTURER"]):array()));
         }
         if ($cfg_ocs["import_general_manufacturer"]
             && !in_array("manufacturers_id", $computer_updates)) {

            $compupdate["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                        $line["SMANUFACTURER"]);
         }
         if ($cfg_ocs["import_general_type"]
             && !empty ($line["TYPE"])
             && !in_array("computertypes_id", $computer_updates)) {

            $compupdate["computertypes_id"] = Dropdown::importExternal('ComputerType',
                                                                        $line["TYPE"]);
         }
         if (count($compupdate)) {
            $compupdate["id"] = $computers_id;
            $comp = new Computer();
            $comp->update($compupdate, $dohistory);
         }
      }
   }


   /**
    * Import a group from OCS table.
    *
    *@param $value string : Value of the new dropdown.
    *@param $entities_id int : entity in case of specific dropdown
    *
    *@return integer : dropdown id.
    *
    **/

   static function importGroup($value, $entities_id) {
      global $DB;

      if (empty ($value)) {
         return 0;
      }

      $query2 = "SELECT `id`
                 FROM `glpi_groups`
                 WHERE `name` = '$value'
                       AND `entities_id` = '$entities_id'";
      $result2 = $DB->query($query2);
      if ($DB->numrows($result2) == 0) {
         $group = new Group;
         $input["name"] = $value;
         $input["entities_id"] = $entities_id;
         return $group->add($input);
      } else {
         $line2 = $DB->fetch_array($result2);
         return $line2["id"];
      }
   }


   static function cleanLinks($ocsservers_id) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      OcsServer::manageDeleted($ocsservers_id);

      // Delete unexisting GLPI computers
      $query = "SELECT `glpi_ocslinks`.`id`
                FROM `glpi_ocslinks`
                LEFT JOIN `glpi_computers` ON `glpi_computers`.`id`=`glpi_ocslinks`.`computers_id`
                WHERE `glpi_computers`.`id` IS NULL
                      AND `ocsservers_id` = '$ocsservers_id'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_array($result)) {
            $query2 = "DELETE
                       FROM `glpi_ocslinks`
                       WHERE `id` = '" . $data['id'] . "'";
            $DB->query($query2);
         }
      }
      // Delete unexisting OCS hardware
      $query_ocs = "SELECT *
                    FROM `hardware`";
      $result_ocs = $DBocs->query($query_ocs);

      $hardware = array ();
      if ($DBocs->numrows($result_ocs) > 0) {
         while ($data = $DBocs->fetch_array($result_ocs)) {
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));
            $hardware[$data["ID"]] = $data["DEVICEID"];
         }
      }
      $query = "SELECT *
                FROM `glpi_ocslinks`
                WHERE `ocsservers_id` = '$ocsservers_id'";

      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_array($result)) {
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));
            if (!isset ($hardware[$data["ocsid"]])) {
               $query_del = "DELETE
                             FROM `glpi_ocslinks`
                             WHERE `id` = '" . $data["id"] . "'";
               $DB->query($query_del);
               $comp = new Computer();
               $comp->delete( array("id" => $data["computers_id"]), 0);
            }
         }
      }
   }


   static function showComputersToUpdate($ocsservers_id, $check, $start) {
      global $DB, $DBocs, $LANG, $CFG_GLPI;

      OcsServer::checkOCSconnection($ocsservers_id);
      if (!haveRight("ocsng", "w")) {
         return false;
      }

      $cfg_ocs = OcsServer::getConfig($ocsservers_id);
      $query_ocs = "SELECT *
                    FROM `hardware`
                    WHERE (`CHECKSUM` & " . $cfg_ocs["checksum"] . ") > '0'
                    ORDER BY `LASTDATE`";
      $result_ocs = $DBocs->query($query_ocs);

      $query_glpi = "SELECT `glpi_ocslinks`.`last_update` AS last_update,
                            `glpi_ocslinks`.`computers_id` AS computers_id,
                            `glpi_ocslinks`.`ocsid` AS ocsid,
                            `glpi_computers`.`name` AS name,
                            `glpi_ocslinks`.`use_auto_update`,
                            `glpi_ocslinks`.`id`
                     FROM `glpi_ocslinks`
                     LEFT JOIN `glpi_computers` ON (`glpi_computers`.`id`=computers_id)
                     WHERE `glpi_ocslinks`.`ocsservers_id` = '$ocsservers_id'
                     ORDER BY `glpi_ocslinks`.`use_auto_update` DESC, last_update, name";

      $result_glpi = $DB->query($query_glpi);
      if ($DBocs->numrows($result_ocs) > 0) {
         // Get all hardware from OCS DB
         $hardware = array ();
         while ($data = $DBocs->fetch_array($result_ocs)) {
            $hardware[$data["ID"]]["date"] = $data["LASTDATE"];
            $hardware[$data["ID"]]["name"] = addslashes($data["NAME"]);
         }
         // Get all links between glpi and OCS
         $already_linked = array ();
         if ($DB->numrows($result_glpi) > 0) {
            while ($data = $DB->fetch_assoc($result_glpi)) {
               $data = clean_cross_side_scripting_deep(addslashes_deep($data));
               if (isset ($hardware[$data["ocsid"]])) {
                  $already_linked[$data["ocsid"]]["date"]            = $data["last_update"];
                  $already_linked[$data["ocsid"]]["name"]            = $data["name"];
                  $already_linked[$data["ocsid"]]["id"]              = $data["id"];
                  $already_linked[$data["ocsid"]]["computers_id"]    = $data["computers_id"];
                  $already_linked[$data["ocsid"]]["ocsid"]           = $data["ocsid"];
                  $already_linked[$data["ocsid"]]["use_auto_update"] = $data["use_auto_update"];
               }
            }
         }
         echo "<div class='center'>";
         echo "<h2>" . $LANG['ocsng'][10] . "</h2>";

         $target = $CFG_GLPI['root_doc'].'/front/ocsng.sync.php';
         if (($numrows = count($already_linked)) > 0) {
            $parameters = "check=$check";
            printPager($start, $numrows, $target, $parameters);

            // delete end
            array_splice($already_linked, $start + $_SESSION['glpilist_limit']);
            // delete begin
            if ($start > 0) {
               array_splice($already_linked, 0, $start);
            }

            echo "<form method='post' id='ocsng_form' name='ocsng_form' action='".$target."'>";

            echo "<a href='".$target."?check=all' ".
                   "onclick= \"if (markCheckboxes('ocsng_form')) return false;\">" .
                   $LANG['buttons'][18] . "</a>&nbsp;/&nbsp;\n";
            echo "<a href='".$target."?check=none' ".
                   "onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" .
                   $LANG['buttons'][19] . "</a>\n";
            echo "<table class='tab_cadre'>";
            echo "<tr><th>" . $LANG['ocsng'][11] . "</th><th>" . $LANG['ocsng'][13] . "</th>";
            echo "<th>" . $LANG['ocsng'][14] . "</th><th>" . $LANG['ocsng'][6] . "</th>";
            echo "<th>&nbsp;</th></tr>\n";

            echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
            echo "<input class='submit' type='submit' name='update_ok' value='".$LANG['ldap'][15]."'>";
            echo "</td></tr>\n";

            foreach ($already_linked as $ID => $tab) {
               echo "<tr class='tab_bg_2 center'>";
               echo "<td><a href='" . $CFG_GLPI["root_doc"] . "/front/computer.form.php?id=" .
                           $tab["computers_id"] . "'>" . $tab["name"] . "</a></td>\n";
               echo "<td>" . convDateTime($tab["date"]) . "</td>\n";
               echo "<td>" . convDateTime($hardware[$tab["ocsid"]]["date"]) . "</td>\n";
               echo "<td>" . $LANG['choice'][$tab["use_auto_update"]] . "</td>\n";
               echo "<td><input type='checkbox' name='toupdate[" . $tab["id"] . "]' " .
                           ($check == "all" ? "checked" : "") . "></td></tr>\n";
            }
            echo "<tr class='tab_bg_1'><td colspan='5' class='center'>";
            echo "<input class='submit' type='submit' name='update_ok' value='".$LANG['ldap'][15]."'>";
            echo "<input type=hidden name='ocsservers_id' value='" . $ocsservers_id . "'>";
            echo "</td></tr>";
            echo "</table></form>\n";
            printPager($start, $numrows, $target, $parameters);
         } else {
            echo "<br><strong>" . $LANG['ocsng'][11] . "</strong>";
         }
         echo "</div>";
      } else {
         echo "<div class='center'><strong>" . $LANG['ocsng'][12] . "</strong></div>";
      }
   }


   static function mergeOcsArray($computers_id, $tomerge, $field) {
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $tab = importArrayFromDB($DB->result($result, 0, 0));
            $newtab = array_merge($tomerge, $tab);
            $newtab = array_unique($newtab);
            $query = "UPDATE `glpi_ocslinks`
                      SET `$field` = '" . addslashes(exportArrayToDB($newtab)) . "'
                      WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
         }
      }
   }


   static function deleteInOcsArray($computers_id, $todel, $field, $is_value_to_del=false) {
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $tab = importArrayFromDB($DB->result($result, 0, 0));
            if ($is_value_to_del) {
               $todel = array_search($todel,$tab);
            }
            if (isset($tab[$todel])) {
               unset ($tab[$todel]);
               $query = "UPDATE `glpi_ocslinks`
                         SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                         WHERE `computers_id` = '$computers_id'";
               $DB->query($query);
            }
         }
      }
   }


   static function replaceOcsArray($computers_id, $newArray, $field) {
      global $DB;

      $newArray = addslashes(exportArrayToDB($newArray));
      $query = "SELECT `$field`
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $query = "UPDATE `glpi_ocslinks`
                      SET `$field` = '" . $newArray . "'
                      WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
         }
      }
   }


   static function addToOcsArray($computers_id, $toadd, $field) {
      global $DB;

      $query = "SELECT `$field`
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";

      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $tab = importArrayFromDB($DB->result($result, 0, 0));
            // Stripslashes because importArray get clean array
            foreach ($toadd as $key => $val) {
               $tab[$key] = stripslashes($val);
            }
            $query = "UPDATE `glpi_ocslinks`
                      SET `$field` = '" . addslashes(exportArrayToDB($tab)) . "'
                      WHERE `computers_id` = '$computers_id'";
            $DB->query($query);
         }
      }
   }


   static function ocsShowNewComputer($ocsservers_id, $advanced, $check, $start, $entity=0,
                                      $tolinked=false) {
      global $DB, $DBocs, $LANG, $CFG_GLPI;

      if (!haveRight("ocsng", "w")) {
         return false;
      }

      $target = $CFG_GLPI['root_doc'].'/front/ocsng.import.php';
      if ($tolinked) {
         $target = $CFG_GLPI['root_doc'].'/front/ocsng.link.php';
      }

      $cfg_ocs = OcsServer::getConfig($ocsservers_id);
      $WHERE = OcsServer::getTagLimit($cfg_ocs);

      $query_ocs = "SELECT `hardware`.*,
                           `accountinfo`.`TAG` AS TAG,
                           `bios`.`SSN` AS SERIAL
                    FROM `hardware`
                    INNER JOIN `accountinfo` ON (`hardware`.`id` = `accountinfo`.`HARDWARE_ID`)
                    INNER JOIN `bios` ON (`hardware`.`id` = `bios`.`HARDWARE_ID`)".
                    (!empty($WHERE)?"WHERE $WHERE":"")."
                    ORDER BY `hardware`.`NAME`";
      $result_ocs = $DBocs->query($query_ocs);

      // Existing OCS - GLPI link
      $query_glpi = "SELECT *
                     FROM `glpi_ocslinks`
                     WHERE `ocsservers_id` = '$ocsservers_id'";
      $result_glpi = $DB->query($query_glpi);

      if ($DBocs->numrows($result_ocs) > 0) {
         // Get all hardware from OCS DB
         $hardware = array ();
         while ($data = $DBocs->fetch_array($result_ocs)) {
            $data = clean_cross_side_scripting_deep(addslashes_deep($data));
            $hardware[$data["ID"]]["date"]   = $data["LASTDATE"];
            $hardware[$data["ID"]]["name"]   = $data["NAME"];
            $hardware[$data["ID"]]["TAG"]    = $data["TAG"];
            $hardware[$data["ID"]]["id"]     = $data["ID"];
            $hardware[$data["ID"]]["serial"] = $data["SERIAL"];
         }
         // Get all links between glpi and OCS
         $already_linked = array ();
         if ($DB->numrows($result_glpi) > 0) {
            while ($data = $DBocs->fetch_array($result_glpi)) {
               $already_linked[$data["ocsid"]] = $data["last_update"];
            }
         }

         // Clean $hardware from already linked element
         if (count($already_linked) > 0) {
            foreach ($already_linked as $ID => $date) {
               if (isset ($hardware[$ID]) && isset ($already_linked[$ID])) {
                  unset ($hardware[$ID]);
               }
            }
         }

         if ($tolinked && count($hardware)) {
            echo "<div class='center'><strong>" . $LANG['ocsng'][22] . "</strong></div>";
         }
         echo "<div class='center'>";
         if (($numrows = count($hardware)) > 0) {
            $parameters = "check=$check";
            printPager($start, $numrows, $target, $parameters);
            // delete end
            array_splice($hardware, $start + $_SESSION['glpilist_limit']);
            // delete begin
            if ($start > 0) {
               array_splice($hardware, 0, $start);
            }
            //Show preview form only in import and in multi-entity mode
            if (!$tolinked && isMultiEntitiesMode()) {
               echo "<form method='post' name='ocsng_import_mode' id='ocsng_import_mode' action='" .
                      $target . "'>\n";
               echo "<table class='tab_cadre'>";
               echo "<tr><th>" . $LANG['ocsng'][41] . "</th></tr>\n";
               echo "<tr class='tab_bg_1'>";
               echo "<td class='center'>";
               if ($advanced) {
                  $status = "false";
               } else {
                  $status = "true";
               }
               echo "<a href='" . $target . "?change_import_mode=" . $status . "'>";
               if ($advanced) {
                  echo $LANG['ocsng'][38];
               } else {
                  echo $LANG['ocsng'][37];
               }
               echo "</a></td>";
               echo "</tr></table></form><br>";
            }
            echo "<strong>" . $LANG['ocsconfig'][18] . "</strong><br>";
            echo "<form method='post' name='ocsng_form' id='ocsng_form' action='" .
                   $target . "'>";
            if (!$tolinked) {
               echo "<a href='".$target."?check=all&amp;start=$start' onclick= ".
                     "\"if ( markCheckboxes('ocsng_form') ) return false;\">" . $LANG['buttons'][18] .
                     "</a>&nbsp;/&nbsp;<a href='".$target."?check=none&amp;start=".
                     "$start' onclick= \"if ( unMarkCheckboxes('ocsng_form') ) return false;\">" .
                     $LANG['buttons'][19] . "</a>\n";
            }
            echo "<table class='tab_cadre'>";
            echo "<tr><th>" . $LANG['ocsng'][5] . "</th>\n<th>".$LANG['common'][19]."</th>\n";
            echo "<th>" . $LANG['common'][27] . "</th>\n<th>TAG</th>\n";
            if ($advanced && !$tolinked) {
               echo "<th>" . $LANG['ocsng'][40] . "</th>\n";
               echo "<th>" . $LANG['ocsng'][36] . "</th>\n";
               echo "<th>" . $LANG['ocsng'][39] . "</th>\n";
            }
            echo "<th>&nbsp;</th></tr>\n";

            echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 8 : 5) . "' class='center'>";
            echo "<input class='submit' type='submit' name='import_ok' value='".$LANG['buttons'][37]."'>";
            echo "</td></tr>\n";

            $rule = new RuleOcsCollection($ocsservers_id);
            foreach ($hardware as $ID => $tab) {
               $comp = new Computer;
               $comp->fields["id"] = $tab["id"];
               $data = array ();
               if ($advanced && !$tolinked) {
                  $data = $rule->processAllRules(array(), array(), $tab["id"]);
               }
               echo "<tr class='tab_bg_2'><td>" . $tab["name"] . "</td>\n";
               echo "<td>".$tab["serial"]."</td>\n";
               echo "<td>" . convDateTime($tab["date"]) . "</td>\n";
               echo "<td>" . $tab["TAG"] . "</td>\n";

               if ($advanced && !$tolinked) {
                  if (!isset ($data['entities_id'])) {
                     echo "<td class='center'><img src=\"".GLPI_ROOT. "/pics/redbutton.png\"></td>\n";
                     $data['entities_id'] = -1;
                  } else {
                     echo "<td class='center'><img src=\"".GLPI_ROOT. "/pics/greenbutton.png\"></td>\n";
                  }
                  echo "<td>";
                  Dropdown::show('Entity',
                                 array('name'     => "toimport_entities[".$tab["id"]."]=".$data['entities_id'],
                                       'value'    => $data['entities_id'],
                                       'comments' => 0));
                  echo "</td>\n";
                  echo "<td>";
                  Dropdown::show('Location',
                                 array('name'     => "toimport_locations[".$tab["id"]."]=".$data['locations_id'],
                                       'value'    => $data['locations_id'],
                                       'comments' => 0));
                  echo "</td>\n";
               }
               echo "<td>";
               if (!$tolinked) {
                  echo "<input type='checkbox' name='toimport[" . $tab["id"] . "]' " .
                         ($check == "all" ? "checked" : "") . ">";
               } else {
                  //Look for the computer using automatic link criterias as defined in OCSNG configuration
                  $computer_found = OcsServer::getComputersAlreadyImported($tab["id"],
                                                                           $ocsservers_id,
                                                                           $entity);
                  $options=array('name' => "tolink[".$tab["id"]."]");

                  if (!empty($computer_found) && $computer_found != -1) {
                     $options['value']  = $computer_found[0];
                     $options['entity'] = $entity;
                  }
                  Dropdown::show('Computer', $options);
               }
               echo "</td></tr>\n";
            }
            echo "<tr class='tab_bg_1'><td colspan='" . ($advanced ? 8 : 5) . "' class='center'>";
            echo "<input class='submit' type='submit' name='import_ok' value='".$LANG['buttons'][37]."'>\n";
            echo "<input type=hidden name='ocsservers_id' value='" . $ocsservers_id . "'>";
            echo "</td></tr>";
            echo "</table></form>\n";

            printPager($start, $numrows, $target, $parameters);
         } else {
            echo "<strong>" . $LANG['ocsng'][9] . "</strong>";
         }
         echo "</div>";
      } else {
         echo "<div class='center'><strong>" . $LANG['ocsng'][9] . "</strong></div>";
      }
   }


   static function getLockableFields() {
      global $LANG;

      return array("name"                           => $LANG['common'][16],
                   "computertypes_id"               => $LANG['common'][17],
                   "manufacturers_id"               => $LANG['common'][5],
                   "computermodels_id"              => $LANG['common'][22],
                   "serial"                         => $LANG['common'][19],
                   "otherserial"                    => $LANG['common'][20],
                   "comment"                        => $LANG['common'][25],
                   "contact"                        => $LANG['common'][18],
                   "contact_num"                    => $LANG['common'][21],
                   "domains_id"                     => $LANG['setup'][89],
                   "networks_id"                    => $LANG['setup'][88],
                   "operatingsystems_id"            => $LANG['computers'][9],
                   "operatingsystemservicepacks_id" => $LANG['computers'][53],
                   "operatingsystemversions_id"     => $LANG['computers'][52],
                   "os_license_number"              => $LANG['computers'][10],
                   "os_licenseid"                   => $LANG['computers'][11],
                   "users_id"                       => $LANG['common'][34],
                   "locations_id"                   => $LANG['common'][15],
                   "groups_id"                      => $LANG['common'][35]);
   }


   static function migrateImportDevice($computers_id, $import_device) {

      $new_import_device = array(self::IMPORT_TAG_078);
      if (count($import_device)) {
         foreach ($import_device as $key=>$val) {
            $tmp = explode(self::FIELD_SEPARATOR, $val);
            if (isset($tmp[1])) {
               $new_import_device[$tmp[0].self::FIELD_SEPARATOR.$key] = $val;
            } else {
               $new_import_device[$key] = $val;
            }
         }
      }
      //Add the new tag as the first occurence in the array
      OcsServer::replaceOcsArray($computers_id, $new_import_device, "import_device");
      return $new_import_device;
   }


   static function migrateComputerUpdates($computers_id, $computer_update) {

      $new_computer_update=array(self::IMPORT_TAG_078);

      $updates = array('ID' => 'id',
                       'FK_entities' => 'entities_id',
                       'tech_num'      => 'users_id_tech',
                       'comments'      => 'comment',
                       'os'            => 'operatingsystems_id',
                       'os_version'    => 'operatingsystemversions_id',
                       'os_sp'         => 'operatingsystemservicepacks_id',
                       'os_license_id' => 'os_licenseid',
                       'auto_update' => 'autoupdatesystems_id',
                       'location' => 'locations_id',
                       'domain' => 'domains_id',
                       'network' => 'networks_id',
                       'model' => 'computermodels_id',
                       'type' => 'computertypes_id',
                       'tplname' => 'template_name',
                       'FK_glpi_enterprise' => 'manufacturers_id',
                       'deleted' => 'is_deleted',
                       'notes' => 'notepad',
                       'ocs_import' => 'is_ocs_import',
                       'FK_users' => 'users_id',
                       'FK_groups' => 'groups_id',
                       'state' => 'states_id');

      if (count($computer_update)) {
         foreach ($computer_update as $field) {
            if (isset($updates[$field])) {
               $new_computer_update[] = $updates[$field];
            } else {
               $new_computer_update[] = $field;
            }
         }
      }
      //Add the new tag as the first occurence in the array
      OcsServer::replaceOcsArray($computers_id, $new_computer_update, "computer_update");
      return $new_computer_update;
   }


   static function unlockItems($computers_id, $field) {
      global $DB;

      if (!in_array($field ,array("import_monitor",
                                  "import_printer",
                                  "import_peripheral",
                                  "import_ip",
                                  "import_software",
                                  "import_disk"))) {
         return false;
      }
      $query = "SELECT `$field`
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$computers_id'";
      if ($result = $DB->query($query)) {
         if ($DB->numrows($result)) {
            $tab = importArrayFromDB($DB->result($result, 0, 0));
            $update_done=false;
            foreach ($tab as $key => $val) {
               if ($val != "_version_070_") {
                  switch ($field) {
                     case "import_monitor" :
                     case "import_printer" :
                     case "import_peripheral" :
                        $querySearchLocked = "SELECT `items_id`
                                              FROM `glpi_computers_items`
                                              WHERE `id` = '$key'";
                        break;

                     case "import_software" :
                        $querySearchLocked = "SELECT `id`
                                              FROM `glpi_computers_softwareversions`
                                              WHERE `id` = '$key'";
                        break;

                     case "import_ip" :
                        $querySearchLocked = "SELECT *
                                              FROM `glpi_networkports`
                                              WHERE `items_id` = '$computers_id'
                                                    AND `itemtype` = 'Computer'
                                                    AND `ip` = '$val'";
                        break;

                     case "import_disk" :
                        $querySearchLocked = "SELECT `id`
                                              FROM `glpi_computerdisks`
                                              WHERE `id` = '$key'";
                        break;

                     default :
                        return;
                  }
                  $resultSearch = $DB->query($querySearchLocked);
                  if ($DB->numrows($resultSearch) == 0) {
                     unset($tab[$key]);
                     $update_done = true;
                  }
               }
            }
            if ($update_done) {
               $query = "UPDATE `glpi_ocslinks`
                         SET `$field` = '" . exportArrayToDB($tab) . "'
                         WHERE `computers_id` = '$computers_id'";
               $DB->query($query);
            }
         }
      }
   }


   static function editLock($target, $ID) {
      global $DB, $LANG;

      if (!haveRight("computer","w")) {
         return false;
      }
      $query = "SELECT *
                FROM `glpi_ocslinks`
                WHERE `computers_id` = '$ID'";

      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $data = $DB->fetch_assoc($result);
         if (haveRight("sync_ocsng","w")) {
            echo "\n<div class='center'>";
            echo "<form method='post' action=\"$target\">";
            echo "<input type='hidden' name='id' value='$ID'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr><th>".$LANG['ocsng'][0]."</th></tr>";
            echo "<tr class='tab_bg_1'><td class='center'>";
            echo "<input type='hidden' name='resynch_id' value='" . $data["id"] . "'>";
            echo "<input class=submit type='submit' name='force_ocs_resynch' value='" .
                   $LANG['ocsng'][24] . "'>";
            echo "</td><tr></table>";
            echo "</form></div>\n";
         }

         $header = false;
         echo "<div width='50%'>";
         echo "<form method='post' action=\"$target\">";
         echo "<input type='hidden' name='id' value='$ID'>\n";
         echo "<table class='tab_cadre_fixe'>";

         // Print lock fields for OCSNG
         $lockable_fields = OcsServer::getLockableFields();
         $locked = importArrayFromDB($data["computer_update"]);

         if (!in_array(self::IMPORT_TAG_078,$locked)) {
            $locked = OcsServer::migrateComputerUpdates($ID, $locked);
         }
         if (count($locked)>0) {
            foreach ($locked as $key => $val) {
               if (!isset($lockable_fields[$val])) {
                  unset($locked[$key]);
               }
            }
         }
         if (count($locked)) {
            $header = true;
            echo "<tr><th colspan='2'>" . $LANG['ocsng'][16] . "&nbsp;:</th></tr>\n";
            foreach ($locked as $key => $val) {
               echo "<tr class='tab_bg_1'>";
               echo "<td class='right' width='50%'>" . $lockable_fields[$val] . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockfield[" . $key . "]'></td></tr>\n";
            }
         }

         //Search locked monitors

         $locked_monitor = importArrayFromDB($data["import_monitor"]);
         foreach ($locked_monitor as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedMonitor = "SELECT `items_id`
                                            FROM `glpi_computers_items`
                                            WHERE `id` = '$key'";
               $resultSearch = $DB->query($querySearchLockedMonitor);
               if ($DB->numrows($resultSearch) == 0) {
                  $header = true;
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][30] . "&nbsp;:</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockmonitor[" . $key . "]'></td></tr>\n";
               }
            }
         }

         //Search locked printers
         $locked_printer = importArrayFromDB($data["import_printer"]);
         foreach ($locked_printer as $key => $val) {
            $querySearchLockedPrinter = "SELECT `items_id`
                                         FROM `glpi_computers_items`
                                         WHERE `id` = '$key'";
            $resultSearchPrinter = $DB->query($querySearchLockedPrinter);
            if ($DB->numrows($resultSearchPrinter) == 0) {
               $header = true;
               echo "<tr><th colspan='2'>" . $LANG['ocsng'][34] . "</th></tr>\n";
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockprinter[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked peripherals
         $locked_printer = importArrayFromDB($data["import_peripheral"]);
         foreach ($locked_printer as $key => $val) {
            $querySearchLockedPeriph = "SELECT `items_id`
                                        FROM `glpi_computers_items`
                                        WHERE `id` = '$key'";
            $resultSearchPrinter = $DB->query($querySearchLockedPeriph);
            if ($DB->numrows($resultSearchPrinter) == 0) {
               $header = true;
               echo "<tr><th colspan='2'>" . $LANG['ocsng'][32] . "</th></tr>\n";
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockperiph[" . $key . "]'></td></tr>\n";
            }
         }

         // Search locked IP
         $locked_ip = importArrayFromDB($data["import_ip"]);

         if (!in_array(self::IMPORT_TAG_072,$locked_ip)) {
            $locked_ip = OcsServer::migrateImportIP($ID,$locked_ip);
         }

         foreach ($locked_ip as $key => $val) {
            if ($key>0) {
               $tmp = explode(self::FIELD_SEPARATOR,$val);
               $querySearchLockedIP = "SELECT *
                                       FROM `glpi_networkports`
                                       WHERE `items_id` = '$ID'
                                             AND `itemtype` = 'Computer'
                                             AND `ip` = '".$tmp[0]."'
                                             AND `mac` = '".$tmp[1]."'";
               $resultSearchIP = $DB->query($querySearchLockedIP);
               if ($DB->numrows($resultSearchIP) == 0) {
                  $header = true;
                  echo "<tr><th colspan='2'>" . $LANG['ocsng'][50] . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
                  echo "<td class='left' width='50%'>";
                  echo "<input type='checkbox' name='lockip[" . $key . "]'></td></tr>\n";
               }
            }
         }

         // Search locked softwares
         $locked_software = importArrayFromDB($data["import_software"]);
         foreach ($locked_software as $key => $val) {
            if ($val != "_version_070_") {
               $querySearchLockedSoft = "SELECT `id`
                                         FROM `glpi_computers_softwareversions`
                                         WHERE `id` = '$key'";
               $resultSearchSoft = $DB->query($querySearchLockedSoft);
               if ($DB->numrows($resultSearchSoft) == 0) {
                  $header = true;
                 echo "<tr><th colspan='2'>" . $LANG['ocsng'][52] . "</th></tr>\n";
                  echo "<tr class='tab_bg_1'><td class='right'width='50%'>" .
                                                       str_replace('$$$$$',' v. ',$val) . "</td>";
                  echo "<td class='left'width='50%'>";
                  echo "<input type='checkbox' name='locksoft[" . $key . "]'></td></tr>";
               }
            }
         }

         // Search locked computerdisks
         $locked = importArrayFromDB($data["import_disk"]);
         foreach ($locked as $key => $val) {
            $querySearchLocked = "SELECT `id`
                                  FROM `glpi_computerdisks`
                                  WHERE `id` = '$key'";
            $resultSearch = $DB->query($querySearchLocked);
            if ($DB->numrows($resultSearch) == 0) {
               $header = true;
               echo "<tr><th colspan='2'>" . $LANG['ocsng'][55] . "</th></tr>\n";
               echo "<tr class='tab_bg_1'><td class='right' width='50%'>" . $val . "</td>";
               echo "<td class='left' width='50%'>";
               echo "<input type='checkbox' name='lockdisk[" . $key . "]'></td></tr>\n";
            }
         }

         if ($header) {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo "<input class='submit' type='submit' name='unlock' value='" .
                  $LANG['buttons'][38] . "'></td></tr>";
         }
         else {
            echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
            echo $LANG['ocsng'][15]."</td></tr>";
         }
         echo "</table></form>";
         echo "</div>\n";
      }
   }


   /**
    * Import the devices for a computer
    *
    *@param $devicetype integer : device type
    *@param $computers_id integer : glpi computer id.
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $import_device array : already imported devices
    *@param $import_ip array : already imported ip
    *@param $dohistory boolean : log changes ?
    *
    *@return Nothing (void).
    *
    **/
   static function updateDevices($devicetype, $computers_id, $ocsid, $ocsservers_id, $cfg_ocs,
                                 $import_device, $import_ip, $dohistory) {
      global $DBocs;

      $prevalue = $devicetype.self::FIELD_SEPARATOR;

      OcsServer::checkOCSconnection($ocsservers_id);
      $types = Computer_Device::getDeviceTypes();
      $CompDevice = new Computer_Device($types[$devicetype]);

      $do_clean = false;

      switch ($devicetype) {
         case self::RAM_DEVICE :
            //Memoire
            if ($cfg_ocs["import_device_memory"]) {
               $do_clean = true;
               $query2 = "SELECT *
                          FROM `memories`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  // Drop all memories and force no history
                  if (!in_array(self::IMPORT_TAG_072,$import_device)) {
                     OcsServer::addToOcsArray($computers_id, array(0 => self::IMPORT_TAG_072),
                                                                   "import_device");
                     // Clean memories for this computer
                     if (count($import_device)) {
                        $dohistory = false;
                        foreach ($import_device as $key => $val) {
                           $tmp = explode(self::FIELD_SEPARATOR,$key);
                           if (isset($tmp[1]) && $tmp[0] == self::RAM_DEVICE) {
                              $CompDevice->delete(array('id'          => $tmp[1],
                                                        '_no_history' => true,
                                                        '_itemtype'   => 'DeviceMemory',));
                              OcsServer::deleteInOcsArray($computers_id, $key, "import_device");
                              unset($import_device[$key]);
                           }
                        }
                     }
                  }
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     if (!empty ($line2["CAPACITY"]) && $line2["CAPACITY"]!="No") {
                        $ram["designation"] = "";
                        if ($line2["TYPE"]!="Empty Slot" && $line2["TYPE"]!="Unknown") {
                           $ram["designation"] = $line2["TYPE"];
                        }
                        if ($line2["DESCRIPTION"]) {
                           if (!empty($ram["designation"])) {
                              $ram["designation"] .= " - ";
                           }
                           $ram["designation"] .= $line2["DESCRIPTION"];
                        }
                        if (!is_numeric($line2["CAPACITY"])) {
                           $line2["CAPACITY"] = 0;
                        }

                        $ram["specif_default"] = $line2["CAPACITY"];
                        if (!in_array(stripslashes($prevalue . $ram["designation"]),
                                                   $import_device)) {
                           $ram["frequence"] = $line2["SPEED"];
                           $ram["devicememorytypes_id"] = Dropdown::importExternal('DeviceMemoryType',
                                                                                   $line2["TYPE"]);

                           $DeviceMemory = new DeviceMemory();
                           $ram_id = $DeviceMemory->import($ram);
                           if ($ram_id) {
                              $devID = $CompDevice->add(array('computers_id'      => $computers_id,
                                                              '_itemtype'         => 'DeviceMemory',
                                                              'devicememories_id' => $ram_id,
                                                              'specificity'       => $line2["CAPACITY"],
                                                              '_no_history'       => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                          array($prevalue.$devID => $prevalue.$ram["designation"]),
                                                "import_device");
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue.$ram["designation"]),
                                               $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR, $tmp);
                           $CompDevice->update(array('id'          => $id,
                                                     'specificity' => $line2["CAPACITY"],
                                                     '_itemtype'   => 'DeviceMemory',));
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case self::HDD_DEVICE :
            //Disque Dur
            if ($cfg_ocs["import_device_hdd"]) {
               $do_clean = true;
               $query2 = "SELECT *
                          FROM `storages`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     if (!empty ($line2["DISKSIZE"]) && preg_match("/disk/i", $line2["TYPE"])) {
                        if ($line2["NAME"]) {
                           $dd["designation"] = $line2["NAME"];
                        } else {
                           if ($line2["MODEL"]) {
                              $dd["designation"] = $line2["MODEL"];
                           } else {
                              $dd["designation"] = "Unknown";
                           }
                        }
                        if (!is_numeric($line2["DISKSIZE"])) {
                           $line2["DISKSIZE"] = 0;
                        }
                        if (!in_array(stripslashes($prevalue.$dd["designation"]), $import_device)) {
                           $dd["specif_default"] = $line2["DISKSIZE"];
                           $DeviceHardDrive = new DeviceHardDrive();
                           $dd_id = $DeviceHardDrive->import($dd);
                           if ($dd_id) {
                              $devID = $CompDevice->add(array('computers_id'  => $computers_id,
                                                              '_itemtype'     => 'DeviceHardDrive',
                                                              'deviceharddrives_id' => $dd_id,
                                                              'specificity'   => $line2["DISKSIZE"],
                                                              '_no_history'   => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                             array($prevalue.$devID => $prevalue.$dd["designation"]),
                                             "import_device");
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue . $dd["designation"]),
                                               $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR, $tmp);
                           $CompDevice->update(array('id'          => $id,
                                                     'specificity' => $line2["DISKSIZE"],
                                                     '_itemtype'   => 'DeviceHardDrive',));
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case self::DRIVE_DEVICE :
            //lecteurs
            if ($cfg_ocs["import_device_drive"]) {
               $do_clean = true;
               $query2 = "SELECT *
                          FROM `storages`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     if (empty ($line2["DISKSIZE"]) || !preg_match("/disk/i", $line2["TYPE"])) {
                        if ($line2["NAME"]) {
                           $stor["designation"] = $line2["NAME"];
                        } else {
                           if ($line2["MODEL"]) {
                              $stor["designation"] = $line2["MODEL"];
                           } else {
                              $stor["designation"] = "Unknown";
                           }
                        }
                        if (!in_array(stripslashes($prevalue.$stor["designation"]),
                                      $import_device)) {
                           $stor["specif_default"] = $line2["DISKSIZE"];
                           $DeviceDrive = new DeviceDrive();
                           $stor_id = $DeviceDrive->import($stor);
                           if ($stor_id) {
                              $devID = $CompDevice->add(array('computers_id'    => $computers_id,
                                                              '_itemtype'       => 'DeviceDrive',
                                                              'devicedrives_id' => $stor_id,
                                                              '_no_history'     => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                          array($prevalue.$devID =>$prevalue.$stor["designation"]),
                                          "import_device");
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue.$stor["designation"]),
                                               $import_device);
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case self::PCI_DEVICE :
            //Modems
            if ($cfg_ocs["import_device_modem"]) {
               $do_clean = true;
               $query2 = "SELECT *
                          FROM `modems`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     $mdm["designation"] = $line2["NAME"];
                     if (!in_array(stripslashes($prevalue.$mdm["designation"]), $import_device)) {
                        if (!empty ($line2["DESCRIPTION"])) {
                           $mdm["comment"] = $line2["TYPE"] . "\r\n" . $line2["DESCRIPTION"];
                        }
                        $DevicePci = new DevicePci();
                        $mdm_id = $DevicePci->import($mdm);
                        if ($mdm_id) {
                           $devID = $CompDevice->add(array('computers_id'  => $computers_id,
                                                           '_itemtype'     => 'DevicePci',
                                                           'devicepcis_id' => $mdm_id,
                                                           '_no_history'   => !$dohistory));
                           OcsServer::addToOcsArray($computers_id,
                                         array($prevalue.$devID => $prevalue.$mdm["designation"]),
                                         "import_device");
                        }
                     } else {
                        $tmp = array_search(stripslashes($prevalue.$mdm["designation"]),
                                            $import_device);
                        unset ($import_device[$tmp]);
                     }
                  }
               }
            }
            //Ports
            if ($cfg_ocs["import_device_port"]) {
               $query2 = "SELECT *
                          FROM `ports`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     $port["designation"] = "";
                     if ($line2["TYPE"] != "Other") {
                        $port["designation"] .= $line2["TYPE"];
                     }
                     if ($line2["NAME"] != "Not Specified") {
                        $port["designation"] .= " " . $line2["NAME"];
                     } else if ($line2["CAPTION"] != "None") {
                        $port["designation"] .= " " . $line2["CAPTION"];
                     }
                     if (!empty ($port["designation"])) {
                        if (!in_array(stripslashes($prevalue.$port["designation"]),
                                      $import_device)) {
                           if (!empty ($line2["DESCRIPTION"]) && $line2["DESCRIPTION"] != "None") {
                              $port["comment"] = $line2["DESCRIPTION"];
                           }
                           $DevicePci = new DevicePci();
                           $port_id = $DevicePci->import($port);
                           if ($port_id) {
                           $devID = $CompDevice->add(array('computers_id'  => $computers_id,
                                                           '_itemtype'     => 'DevicePci',
                                                           'devicepcis_id' => $port_id,
                                                           '_no_history'   => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                          array($prevalue.$devID =>$prevalue.$port["designation"]),
                                          "import_device");
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue.$port["designation"]),
                                               $import_device);
                           unset ($import_device[$tmp]);
                        }
                     }
                  }
               }
            }
            break;

         case self::PROCESSOR_DEVICE :
            //Processeurs :
            if ($cfg_ocs["import_device_processor"]) {
               $do_clean = true;
               $query = "SELECT *
                         FROM `hardware`
                         WHERE `ID` = '$ocsid'
                         ORDER BY `ID`";
               $result = $DBocs->query($query);
               if ($DBocs->numrows($result) == 1) {
                  $line = $DBocs->fetch_array($result);
                  $line = clean_cross_side_scripting_deep(addslashes_deep($line));
                  for ($i=0 ; $i<$line["PROCESSORN"] ; $i++) {
                     $processor = array();
                     $processor["designation"] = $line["PROCESSORT"];
                     if (!is_numeric($line["PROCESSORS"])) {
                        $line["PROCESSORS"] = 0;
                     }

                     $processor["specif_default"] = $line["PROCESSORS"];

                     if (!in_array(stripslashes($prevalue.$processor["designation"]),
                                   $import_device)) {
                        $DeviceProcessor = new DeviceProcessor();
                        $proc_id = $DeviceProcessor->import($processor);
                        if ($proc_id) {
                           $devID = $CompDevice->add(array('computers_id'  => $computers_id,
                                                           '_itemtype'     => 'DeviceProcessor',
                                                           'deviceprocessors_id' => $proc_id,
                                                           'specificity'   => $line["PROCESSORS"],
                                                           '_no_history'   => !$dohistory));
                           OcsServer::addToOcsArray($computers_id,
                                    array($prevalue.$devID => $prevalue.$processor["designation"]),
                                    "import_device");
                        }
                     } else {
                        $tmp = array_search(stripslashes($prevalue.$processor["designation"]),
                                            $import_device);
                        list($type,$id) = explode(self::FIELD_SEPARATOR,$tmp);
                        $CompDevice->update(array('id'          => $id,
                                                  'specificity' => $line["PROCESSORS"],
                                                  '_itemtype'   => 'DeviceProcessor',));
                        unset ($import_device[$tmp]);
                     }
                  }
               }
            }
            break;

         case self::NETWORK_DEVICE :
            //Carte reseau
            if ($cfg_ocs["import_device_iface"] || $cfg_ocs["import_ip"]) {
               $do_clean = true;
               //If import_ip doesn't contain _VERSION_072_, then migrate it to the new architecture
               if (!in_array(self::IMPORT_TAG_072,$import_ip)) {
                  $import_ip = OcsServer::migrateImportIP($computers_id, $import_ip);
               }
               $query2 = "SELECT *
                          FROM `networks`
                          WHERE `HARDWARE_ID` = '$ocsid'
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               $i = 0;
               $manually_link = false;

               //Count old ip in GLPI
               $count_ip = count($import_ip);

               // Add network device
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     if ($cfg_ocs["import_device_iface"]) {
                        $network["designation"] = $line2["DESCRIPTION"];

                        if (!in_array(stripslashes($prevalue.$network["designation"]),
                                      $import_device)) {
                           if (!empty ($line2["SPEED"])) {
                              $network["bandwidth"] = $line2["SPEED"];
                           }
                           $DeviceNetworkCard = new DeviceNetworkCard();
                           $net_id = $DeviceNetworkCard->import($network);
                           if ($net_id) {
                              $devID = $CompDevice->add(array('computers_id'  => $computers_id,
                                                              '_itemtype'     => 'DeviceNetworkCard',
                                                              'devicenetworkcards_id' => $net_id,
                                                              'specificity'   => $line2["MACADDR"],
                                                              '_no_history'   => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                       array($prevalue.$devID => $prevalue.$network["designation"]),
                                       "import_device");
                           }
                        } else {
                           $tmp = array_search(stripslashes($prevalue.$network["designation"]),
                                               $import_device);
                           list($type,$id) = explode(self::FIELD_SEPARATOR,$tmp);
                           $CompDevice->update(array('id'          => $id,
                                                     'specificity' => $line2["MACADDR"],
                                                     '_itemtype'   => 'DeviceNetworkCard',));
                           unset ($import_device[$tmp]);
                        }
                     }
                     if (!empty ($line2["IPADDRESS"]) && $cfg_ocs["import_ip"]) {
                        $ocs_ips = explode(",", $line2["IPADDRESS"]);
                        $ocs_ips = array_unique($ocs_ips);
                        sort($ocs_ips);

                        //if never imported (only 0.72 tag in array), check if existing ones match
                        if ($count_ip == 1) {
                           //get old IP in DB
                           $querySelectIDandIP = "SELECT `id`, `ip`
                                                  FROM `glpi_networkports`
                                                  WHERE `itemtype` = 'Computer'
                                                        AND `items_id` = '$computers_id'
                                                        AND `mac` = '" . $line2["MACADDR"] . "'
                                                        AND `name` = '" . $line2["DESCRIPTION"] . "'";
                           $result = $DB->query($querySelectIDandIP);
                           if ($DB->numrows($result) > 0) {
                              while ($data = $DB->fetch_array($result)) {
                                 //Upate import_ip column and import_ip array
                                 OcsServer::addToOcsArray($computers_id,
                                                          array($data["id"] => $data["ip"] .
                                                                self::FIELD_SEPARATOR .
                                                                $line2["MACADDR"]),
                                                         "import_ip");
                                 $import_ip[$data["id"]] = $data["ip"].self::FIELD_SEPARATOR.
                                                           $line2["MACADDR"];
                              }
                           }
                        }
                        $netport = array();
                        $netport["mac"]      = $line2["MACADDR"];
                        $netport["networkinterfaces_id"] = Dropdown::importExternal('NetworkInterface',
                                                                                    $line2["TYPE"]);
                        $netport["name"]     = $line2["DESCRIPTION"];
                        $netport["items_id"] = $computers_id;
                        $netport["itemtype"] = 'Computer';
                        $netport["netmask"]  = $line2["IPMASK"];
                        $netport["gateway"]  = $line2["IPGATEWAY"];
                        $netport["subnet"]   = $line2["IPSUBNET"];

                        $np = new NetworkPort();
                        for ($j = 0; $j < count($ocs_ips); $j++) {
                           //First search : look for the same port (same IP and same MAC)
                           $id_ip = array_search($ocs_ips[$j].self::FIELD_SEPARATOR.$line2["MACADDR"],
                                                 $import_ip);
                           //Second search : IP may have change, so look only for mac address
                           if(!$id_ip) {
                              //Browse the whole import_ip array
                              foreach($import_ip as $ID => $ip) {
                                 if ($ID > 0) {
                                    $tmp = explode(self::FIELD_SEPARATOR,$ip);
                                    //Port was found by looking at the mac address
                                    if (isset($tmp[1]) && $tmp[1] == $line2["MACADDR"]) {
                                       //Remove port in import_ip
                                       OcsServer::deleteInOcsArray($computers_id,$ID, "import_ip");
                                       OcsServer::addToOcsArray($computers_id,
                                                                array($ID => $ocs_ips[$j] .
                                                                      self::FIELD_SEPARATOR .
                                                                      $line2["MACADDR"]),
                                                                "import_ip");
                                       $import_ip[$ID] = $ocs_ips[$j] . self::FIELD_SEPARATOR .
                                                         $line2["MACADDR"];
                                       $id_ip = $ID;
                                       break;
                                    }
                                 }
                              }
                           }
                           $netport['_no_history'] =! $dohistory;
                           //Update already in DB
                           if ($id_ip>0) {
                              $netport["ip"] = $ocs_ips[$j];
                              $netport["logical_number"] = $j;
                              $netport["id"] = $id_ip;
                              $np->update($netport);
                              unset ($import_ip[$id_ip]);
                              $count_ip++;
                           } else { //If new IP found
                              unset ($np->fields["netpoints_id"]);
                              unset ($netport["id"]);
                              unset ($np->fields["id"]);
                              $netport["ip"] = $ocs_ips[$j];
                              $netport["logical_number"] = $j;
                              $newID = $np->add($netport);
                              //ADD to array
                              OcsServer::addToOcsArray($computers_id,
                                                       array($newID => $ocs_ips[$j] .
                                                             self::FIELD_SEPARATOR .
                                                             $line2["MACADDR"]),
                                                       "import_ip");
                              $count_ip++;
                           }
                        }
                     }
                  }
               }
            }
            break;

         case self::GFX_DEVICE :
            //carte graphique
            if ($cfg_ocs["import_device_gfxcard"]) {
               $do_clean = true;
               $query2 = "SELECT DISTINCT(`NAME`) as NAME, `MEMORY`
                          FROM `videos`
                          WHERE `HARDWARE_ID` = '$ocsid'
                                AND `NAME` != ''
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     $video["designation"] = $line2["NAME"];
                     if (!is_numeric($line2["MEMORY"])) {
                        $line2["MEMORY"] = 0;
                     }
                     if (!in_array(stripslashes($prevalue.$video["designation"]), $import_device)) {
                        $video["specif_default"] = $line2["MEMORY"];
                        $DeviceGraphicCard = new DeviceGraphicCard();
                        $video_id = $DeviceGraphicCard->import($video);
                        if ($video_id) {
                           $devID = $CompDevice->add(array('computers_id'     => $computers_id,
                                                           '_itemtype'        => 'DeviceGraphicCard',
                                                           'devicegraphiccards_id' => $video_id,
                                                           'specificity'      => $line2["MEMORY"],
                                                           '_no_history'      => !$dohistory));
                           OcsServer::addToOcsArray($computers_id,
                                         array($prevalue.$devID =>$prevalue.$video["designation"]),
                                         "import_device");
                        }
                     } else {
                        $tmp = array_search(stripslashes($prevalue.$video["designation"]),
                                            $import_device);
                        list($type,$id) = explode(self::FIELD_SEPARATOR,$tmp);
                        $CompDevice->update(array('id'          => $id,
                                                  'specificity' => $line2["MEMORY"],
                                                  '_itemtype'   => 'DeviceGraphicCard',));
                        unset ($import_device[$tmp]);
                     }
                  }
               }
            }
            break;

         case self::SND_DEVICE :
            //carte son
            if ($cfg_ocs["import_device_sound"]) {
               $do_clean = true;
               $query2 = "SELECT DISTINCT(`NAME`) as NAME, `DESCRIPTION`
                          FROM `sounds`
                          WHERE `HARDWARE_ID` = '$ocsid'
                                AND `NAME` != ''
                          ORDER BY `ID`";
               $result2 = $DBocs->query($query2);
               if ($DBocs->numrows($result2) > 0) {
                  while ($line2 = $DBocs->fetch_array($result2)) {
                     $line2 = clean_cross_side_scripting_deep(addslashes_deep($line2));
                     $snd["designation"] = $line2["NAME"];
                     if (!in_array(stripslashes($prevalue.$snd["designation"]), $import_device)) {
                        if (!empty ($line2["DESCRIPTION"])) {
                           $snd["comment"] = $line2["DESCRIPTION"];
                        }
                        $DeviceSoundCard = new DeviceSoundCard();
                        $snd_id = $DeviceSoundCard->import($snd);
                        if ($snd_id) {
                           $devID = $CompDevice->add(array('computers_id'     => $computers_id,
                                                           '_itemtype'        => 'DeviceSoundCard',
                                                           'devicesoundcards_id' => $snd_id,
                                                           '_no_history'      => !$dohistory));
                           OcsServer::addToOcsArray($computers_id,
                                          array($prevalue.$devID => $prevalue.$snd["designation"]),
                                          "import_device");
                        }
                     } else {
                        $id = array_search(stripslashes($prevalue.$snd["designation"]),
                                           $import_device);
                        unset ($import_device[$id]);
                     }
                  }
               }
            }
            break;
      }

      // Delete Unexisting Items not found in OCS
      if ($do_clean && count($import_device)) {
         foreach ($import_device as $key => $val) {
            if (!(strpos($key, $devicetype . '$$') === false)) {
               list($type,$id) = explode(self::FIELD_SEPARATOR, $key);
               $CompDevice->delete(array('id'          => $id,
                                         '_itemtype'   => $types[$devicetype],
                                         '_no_history' => !$dohistory));
               OcsServer::deleteInOcsArray($computers_id, $key, "import_device");
            }
         }
      }
      if ($do_clean && count($import_ip) && $devicetype == self::NETWORK_DEVICE) {
         foreach ($import_ip as $key => $val) {
            if ($key>0) {
               $netport = new NetworkPort();
               $netport->delete(array('id' => $key));
               OcsServer::deleteInOcsArray($computers_id, $key, "import_ip");
            }
         }
      }
      //Alimentation
      //Carte mere
   }


   /**
    * Get a direct link to the computer in ocs console
    * @param $ocsservers_id the ID of the OCS server
    * @param $ocsid ID of the computer in OCS hardware table
    * @param $todisplay the link's label to display
    * @return the html link to the computer in ocs console
    */
   static function getComputerLinkToOcsConsole ($ocsservers_id, $ocsid, $todisplay) {

      $ocs_config = OcsServer::getConfig($ocsservers_id);
      $url = '';
      if ($ocs_config["ocs_url"] != '') {
         //Display direct link to the computer in ocsreports
         $url = $ocs_config["ocs_url"];
         if (!preg_match("/\/$/i",$ocs_config["ocs_url"])) {
            $url .= '/';
         }
         return "<a href='".$url."machine.php?systemid=".$ocsid."'>".$todisplay."</a>";
      }
      return $url;
   }


   static function migrateImportIP($computers_id, $import_ip) {
      global $DB;

      //Add the new tag as the first occurence in the array
      OcsServer::addToOcsArray($computers_id, array(0 => self::IMPORT_TAG_072),
                                                    "import_ip");
      $import_ip[0] = self::IMPORT_TAG_072;
      //If import_ip is empty : machine comes from pre 0.70 version
      //or new machine to be imported in glpi
      if (count($import_ip) > 1) {
         foreach($import_ip as $importip_ID => $value) {
            if ($importip_ID > 0) {
               //Delete old value in the array (ID => IP)
               OcsServer::deleteInOcsArray($computers_id, $importip_ID, "import_ip");
               unset($import_ip[$importip_ID]);
               $query = "SELECT `mac`, `ip`
                         FROM `glpi_networkports`
                         WHERE `id` = '$importip_ID'";
               $result = $DB->query($query);
               $datas = $DB->fetch_array($result);
               $new_ip = (isset($datas["ip"])?$datas["ip"]:"");
               $new_mac = (isset($datas["mac"])?$datas["mac"]:"");

               //Add new value (ID => IP.$$$$$.MAC)
               OcsServer::addToOcsArray($computers_id,
                                        array($importip_ID => $new_ip . self::FIELD_SEPARATOR .
                                                              $new_mac),
                                        "import_ip");
               $import_ip[$importip_ID] = $new_ip.self::FIELD_SEPARATOR.$new_mac;
            }
         }
      }
      return $import_ip;
   }


   /**
    * Get IP address from OCS hardware table
    * @param ocsservers_id the ID of the OCS server
    * @param computers_id ID of the computer in OCS hardware table
    * @return the ip address or ''
    */
   static function getGeneralIpAddress($ocsservers_id, $computers_id) {
      global $DBocs;

      $res = $DBocs->query("SELECT `IPADDR`
                            FROM `hardware`
                            WHERE `ID` = '$computers_id'");
      if ($DBocs->numrows($res) == 1) {
         return $DBocs->result($res, 0, "IPADDR");
      }
      return '';
   }


   static function getDevicesManagementMode($ocs_config, $itemtype) {

      switch ($itemtype) {
         case 'Monitor' :
            return $ocs_config["import_monitor"];
            break;

         case 'Printer' :
            return $ocs_config["import_printer"];
            break;

         case 'Peripheral' :
            return $ocs_config["import_periph"];
            break;
      }
   }


   static function setEntityLock($entity) {

      $fp = fopen(GLPI_LOCK_DIR . "/lock_entity_" . $entity, "w+");
      if (flock($fp, LOCK_EX)) {
         return $fp;
      }
      fclose($fp);
      return false;
   }


   static function removeEntityLock($entity, $fp) {

      flock($fp, LOCK_UN);
      fclose($fp);

      //Test if the lock file still exists before removing it
      // (sometimes another thread already removed the file)
      clearstatcache();
      if (file_exists(GLPI_LOCK_DIR . "/lock_entity_" . $entity)) {
         @unlink(GLPI_LOCK_DIR . "/lock_entity_" . $entity);
      }
   }


   static function getFormServerAction($ID, $templateid) {

      $action ="";
      if (!isset($withtemplate) || $withtemplate == "") {
         $action = "edit_server";

      } else if (isset($withtemplate) && $withtemplate == 1) {
         if ($ID == -1 && $templateid == '') {
            $action = "add_template";
         } else {
            $action = "update_template";
         }

      } else if (isset($withtemplate) && $withtemplate == 2) {
         if ($templateid== '') {
            $action = "edit_server";
         } else if ($ID == -1) {
            $action = "add_server_with_template";
         } else {
            $action = "update_server_with_template";
         }
      }
      return $action;
   }


   static function getColumnListFromAccountInfoTable($ID, $glpi_column) {
      global $DBocs, $DB;

      $listColumn = "";
      if ($ID != -1) {
         OcsServer::checkOCSconnection($ID);
         if (!$DBocs->error) {
            $result = $DBocs->query("SHOW COLUMNS FROM `accountinfo`");
            if ($DBocs->numrows($result) > 0) {
               while ($data = $DBocs->fetch_array($result)) {
                  //get the selected value in glpi if specified
                  $query = "SELECT `ocs_column`
                            FROM `glpi_ocsadmininfoslinks`
                            WHERE `ocsservers_id` = '$ID'
                                  AND `glpi_column` = '$glpi_column'";
                  $result_DB = $DB->query($query);
                  $selected = "";
                  if ($DB->numrows($result_DB) > 0) {
                     $data_DB = $DB->fetch_array($result_DB);
                     $selected = $data_DB["ocs_column"];
                  }
                  $ocs_column = $data['Field'];
                  if (!strcmp($ocs_column, $selected)) {
                     $listColumn .= "<option value='$ocs_column' selected>".$ocs_column."</option>";
                  } else {
                     $listColumn .= "<option value='$ocs_column'>" . $ocs_column . "</option>";
                  }
               }
            }
         }
      }
      return $listColumn;
   }


   /**
    * Check if OCS connection is always valid
    * If not, then establish a new connection on the good server
    *
    * @return nothing.
    */
   static function checkOCSconnection($ocsservers_id) {
      global $DBocs;

      //If $DBocs is not initialized, or if the connection should be on a different ocs server
      // --> reinitialize connection to OCS server
      if (!$DBocs || $ocsservers_id != $DBocs->getServerID()) {
         $DBocs = OcsServer::getDBocs($ocsservers_id);
      }
      return $DBocs->connected;
   }


   /**
    * Get a connection to the OCS server
    * @param $ocsservers_id the ocs server id
    * @return the connexion to the ocs database
    */
   static function getDBocs($ocsservers_id) {
      return new DBocs($ocsservers_id);
   }


   /**
    * Choose an ocs server
    *
    * @return nothing.
    */
   static function showFormServerChoice() {
      global $DB, $LANG, $CFG_GLPI;

      $query = "SELECT *
                FROM `glpi_ocsservers`
                ORDER BY `name` ASC";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 1) {
         echo "<form action=\"".$CFG_GLPI['root_doc']."/front/ocsng.php\" method='get'>";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG['ocsng'][26] . "</th></tr>\n";
         echo "<tr class='tab_bg_2'><td class='center'>" . $LANG['common'][16] . "</td>";
         echo "<td class='center'>";
         echo "<select name='ocsservers_id'>";
         while ($ocs = $DB->fetch_array($result)) {
            echo "<option value='" . $ocs["id"] . "'>" . $ocs["name"] . "</option>";
         }
         echo "</select></td></tr>\n";
         echo "<tr class='tab_bg_2'><td class='center' colspan=2>";
         echo "<input class='submit' type='submit' name='ocs_showservers' value='" .
               $LANG['buttons'][2] . "'></td></tr>";
         echo "</table></div></form>\n";

      } else if ($DB->numrows($result) == 1) {
         $ocs = $DB->fetch_array($result);
         glpi_header($CFG_GLPI['root_doc']."/front/ocsng.php?ocsservers_id=" . $ocs["id"]);

      } else {
         echo "<form action=\"$target\" method='get'>";
         echo "<div class='center'><table class='tab_cadre'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . $LANG['ocsng'][26] . "</th></tr>\n";
         echo "<tr class='tab_bg_2'><td class='center' colspan=2>" . $LANG['ocsng'][27] . "</td></tr>";
         echo "</table></div></form>\n";
      }
   }


   /**
    * Delete old dropdown value
    *
    * Delete all old dropdown value of a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *@param $field string : string of the computer table
    *@param $table string : dropdown table name
    *
    *@return nothing.
    *
    **/
   static function resetDropdown($glpi_computers_id, $field, $table) {
      global $DB;

      $query = "SELECT `$field` AS val
                FROM `glpi_computers`
                WHERE `id` = '$glpi_computers_id'";
      $result = $DB->query($query);
      if ($DB->numrows($result) == 1) {
         $value = $DB->result($result, 0, "val");
         $query = "SELECT COUNT(*) AS cpt
                   FROM `glpi_computers`
                   WHERE `$field` = '$value'";
         $result = $DB->query($query);
         if ($DB->result($result, 0, "cpt") == 1) {
            $query2 = "DELETE
                       FROM `$table`
                       WHERE `id` = '$value'";
            $DB->query($query2);
         }
      }
   }


   /**
    * Delete old registry entries
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetRegistry($glpi_computers_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_registrykeys`
                WHERE `computers_id` = '$glpi_computers_id'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query2 = "SELECT COUNT(*)
                       FROM `glpi_registrykeys`
                       WHERE `computers_id` = '" . $data['computers_id'] . "'";
            $result2 = $DB->query($query2);
            $registry = new RegistryKey();
            if ($DB->result($result2, 0, 0) == 1) {
               $registry->delete(array('id' => $data['computers_id']), 1);
            }
         }
      }
   }


   /**
    * Delete old printers
    *
    * Delete all old printers of a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetPrinters($glpi_computers_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Printer'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();
         while ($data = $DB->fetch_assoc($result)) {
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Printer'";
            $result2 = $DB->query($query2);
            $printer = new Printer();
            if ($DB->result($result2, 0, 0) == 1) {
               $printer->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete old monitors
    *
    * Delete all old monitors of a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetMonitors($glpi_computers_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Monitor'";
      $result = $DB->query($query);

      $mon = new Monitor();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();
         while ($data = $DB->fetch_assoc($result)) {
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Monitor'";
            $result2 = $DB->query($query2);
            if ($DB->result($result2, 0, 0) == 1) {
               $mon->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete old periphs
    *
    * Delete all old periphs for a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetPeripherals($glpi_computers_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_items`
                WHERE `computers_id` = '$glpi_computers_id'
                      AND `itemtype` = 'Peripheral'";
      $result = $DB->query($query);
      $per = new Peripheral();
      if ($DB->numrows($result) > 0) {
         $conn = new Computer_Item();
         while ($data = $DB->fetch_assoc($result)) {
            $conn->delete(array('id' => $data['id']));

            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_items`
                       WHERE `items_id` = '" . $data['items_id'] . "'
                             AND `itemtype` = 'Peripheral'";
            $result2 = $DB->query($query2);
            if ($DB->result($result2, 0, 0) == 1) {
               $per->delete(array('id' => $data['items_id']), 1);
            }
         }
      }
   }


   /**
    * Delete old softwares
    *
    * Delete all old softwares of a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetSoftwares($glpi_computers_id) {
      global $DB;

      $query = "SELECT *
                FROM `glpi_computers_softwareversions`
                WHERE `computers_id` = '$glpi_computers_id'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetch_assoc($result)) {
            $query2 = "SELECT COUNT(*)
                       FROM `glpi_computers_softwareversions`
                       WHERE `softwareversions_id` = '" . $data['softwareversions_id'] . "'";
            $result2 = $DB->query($query2);
            if ($DB->result($result2, 0, 0) == 1) {
               $vers = new SoftwareVersion;
               $vers->getFromDB($data['softwareversions_id']);
               $query3 = "SELECT COUNT(*)
                          FROM `glpi_softwareversions`
                          WHERE `softwares_id`='" . $vers->fields['softwares_id'] . "'";
               $result3 = $DB->query($query3);
               if ($DB->result($result3, 0, 0) == 1) {
                  $soft = new Software();
                  $soft->delete(array('id' => $vers->fields['softwares_id']), 1);
               }
               $vers->delete(array("id" => $data['softwareversions_id']));
            }
         }
         $query = "DELETE
                   FROM `glpi_computers_softwareversions`
                   WHERE `computers_id` = '$glpi_computers_id'";
         $DB->query($query);
      }
   }


   /**
    * Delete old disks
    *
    * Delete all old disks of a computer.
    *
    *@param $glpi_computers_id integer : glpi computer id.
    *
    *@return nothing.
    *
    **/
   static function resetDisks($glpi_computers_id) {
      global $DB;

      $query = "DELETE
                FROM `glpi_computerdisks`
                WHERE `computers_id` = '$glpi_computers_id'";
      $DB->query($query);
   }


   /**
    * Import config of a new version
    *
    * This function create a new software in GLPI with some general datas.
    *
    *@param $software : id of a software.
    *@param $version : version of the software
    *
    *@return integer : inserted version id.
    *
    **/
   static function importVersion($software, $version) {
      global $DB;

      $isNewVers = 0;
      $query = "SELECT `id`
                FROM `glpi_softwareversions`
                WHERE `softwares_id` = '$software'
                      AND `name` = '$version'";
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         $data = $DB->fetch_array($result);
         $isNewVers = $data["id"];
      }
      if (!$isNewVers) {
         $vers = new SoftwareVersion;
         // TODO : define a default state ? Need a new option in config
         // Use $cfg_ocs["states_id_default"] or create a specific one ?
         $input["softwares_id"] = $software;
         $input["name"] = $version;
         $isNewVers = $vers->add($input);
      }
      return ($isNewVers);
   }


   /**
    * Update config of a new software
    *
    * This function create a new software in GLPI with some general datas.
    *
    *@param $computers_id integer : glpi computer id.
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $import_disk array : already imported softwares
    *@param $dohistory array : already imported softwares
    *
    *@return Nothing (void).
    *
    **/
   static function updateDisk($computers_id, $ocsid, $ocsservers_id, $cfg_ocs, $import_disk,
                              $dohistory) {
      global $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      $query = "SELECT *
                FROM `drives`
                WHERE `HARDWARE_ID` = '$ocsid'";
      $result = $DBocs->query($query);
      $d = new ComputerDisk();
      if ($DBocs->numrows($result) > 0) {
         while ($line = $DBocs->fetch_array($result)) {
            $line = clean_cross_side_scripting_deep(addslashes_deep($line));
            // Only not empty disk
            if ($line['TOTAL']>0) {
               $disk = array();
               $disk['computers_id'] = $computers_id;
               // TYPE : vxfs / ufs  : VOLUMN = mount / FILESYSTEM = device
               if (in_array($line['TYPE'], array("vxfs", "ufs")) ) {
                  $disk['name']       = $line['VOLUMN'];
                  $disk['mountpoint'] = $line['VOLUMN'];
                  $disk['device']     = $line['FILESYSTEM'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem', $line["TYPE"]);

               } else if (in_array($line['FILESYSTEM'],array('ext4', 'ext3', 'ext2', 'ffs', 'jfs',
                                                             'jfs2', 'xfs', 'smbfs', 'nfs', 'hfs',
                                                             'ufs', 'Journaled HFS+', 'fusefs',
                                                             'fuseblk')) ) {
                  // Try to detect mount point : OCS database is dirty
                  $disk['mountpoint'] = $line['VOLUMN'];
                  $disk['device']     = $line['TYPE'];
                  // Found /dev in VOLUMN : invert datas
                  if (strstr($line['VOLUMN'],'/dev/')) {
                     $disk['mountpoint'] = $line['TYPE'];
                     $disk['device']     = $line['VOLUMN'];
                  }

                  $disk['name'] = $disk['mountpoint'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem',
                                                                     $line["FILESYSTEM"]);

               } else if (in_array($line['FILESYSTEM'], array('FAT32', 'NTFS', 'FAT')) ){
                  if (!empty($line['VOLUMN'])) {
                     $disk['name'] = $line['VOLUMN'];
                  } else {
                     $disk['name'] = $line['LETTER'];
                  }
                  $disk['mountpoint']     = $line['LETTER'];
                  $disk['filesystems_id'] = Dropdown::importExternal('Filesystem',
                                                                     $line["FILESYSTEM"]);
               }

               // Ok import disk
               if (isset($disk['name']) && !empty($disk["name"])) {
                  $disk['totalsize'] = $line['TOTAL'];
                  $disk['freesize']  = $line['FREE'];
                  if (!in_array(stripslashes($disk["name"]), $import_disk)) {
                     $d->reset();
                     if (!$dohistory) {
                        $disk['_no_history'] = true;
                     }
                     $id_disk = $d->add($disk);
                     if ($id_disk) {
                        OcsServer::addToOcsArray($computers_id, array ($id_disk => $disk["name"]),
                                                 "import_disk");
                     }

                  } else {
                     // Only update sizes if needed
                     $id = array_search(stripslashes($disk["name"]), $import_disk);
                     if ($d->getFromDB($id)) {
                        // Update on total size change or variation of 5%
                        if ($d->fields['totalsize']!=$disk['totalsize']
                            || (abs($disk['freesize']-$d->fields['freesize'])/$disk['totalsize']) > 0.05) {

                           $toupdate['id'] = $id;
                           $toupdate['totalsize'] = $disk['totalsize'];
                           $toupdate['freesize']  = $disk['freesize'];
                           $d->update($toupdate);
                        }
                        unset ($import_disk[$id]);
                     }
                  }
               }
            }
         }
      }
      // Delete Unexisting Items not found in OCS
      if (count($import_disk)) {
         foreach ($import_disk as $key => $val) {
            $d->delete(array("id" => $key));
            OcsServer::deleteInOcsArray($computers_id, $key, "import_device");
         }
      }
   }


   /**
    * Install a software on a computer - check if not already installed
    *
    * @param $computers_id ID of the computer where to install a software
    * @param $softwareversions_id ID of the version to install
    * @param $dohistory Do history ?
    * @return nothing
    */
   static function installSoftwareVersion($computers_id, $softwareversions_id, $dohistory=1) {
      global $DB;

      if (!empty ($softwareversions_id) && $softwareversions_id > 0) {
         $query_exists = "SELECT `id`
                          FROM `glpi_computers_softwareversions`
                          WHERE (`computers_id` = '$computers_id'
                                 AND `softwareversions_id` = '$softwareversions_id')";
         $result = $DB->query($query_exists);
         if ($DB->numrows($result) > 0) {
            return $DB->result($result, 0, "id");
         }
         $tmp = new Computer_SoftwareVersion();
         return $tmp->add(array('computers_id'        => $computers_id,
                                'softwareversions_id' => $softwareversions_id,
                                '_no_history'         => !$dohistory));
      }
      return 0;
   }


   /**
    * Update config of a new software
    *
    * This function create a new software in GLPI with some general datas.
    *
    *@param $computers_id integer : glpi computer id.
    *@param $entity integer : entity of the computer
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $import_software array : already imported softwares
    *@param $dohistory boolean : log changes ?
    *
    *@return Nothing (void).
    *
    **/
   static function updateSoftware($computers_id, $entity, $ocsid, $ocsservers_id, $cfg_ocs,
                                  $import_software, $dohistory) {
      global $DB, $DBocs, $LANG;

      OcsServer::checkOCSconnection($ocsservers_id);
      if ($cfg_ocs["import_software"]) {
         //------------------------------------------------------------------------------//
         //---- Import_software array is not in the new form ( ID => name+version) ------//
         //------------------------------------------------------------------------------//
         if (!in_array(self::IMPORT_TAG_070, $import_software)) {
            //Add the tag of the version at the beginning of the array
            $softs_array[0] = self::IMPORT_TAG_070;

            //For each element of the table, add instID=>name.version
            foreach ($import_software as $key => $value) {
               $query_softs = "SELECT `glpi_softwareversions`.`name` AS version
                               FROM `glpi_computers_softwareversions`,
                                    `glpi_softwareversions`
                               WHERE `glpi_computers_softwareversions`.softwareversions_id`
                                          =`glpi_softwareversions`.`id`
                                     AND `glpi_computers_softwareversions`.`computers_id`
                                          = '$computers_id'
                                     AND `glpi_computers_softwareversions`.`id` = '$key'";
               $result_softs = $DB->query($query_softs);
               $softs = $DB->fetch_array($result_softs);
               $softs_array[$key] = $value . self::FIELD_SEPARATOR. $softs["VERSION"];
            }

            //Replace in GLPI database the import_software by the new one
            OcsServer::replaceOcsArray($computers_id, $softs_array, "import_software");

            // Get import_software from the GLPI db
            $query = "SELECT `import_software`
                      FROM `glpi_ocslinks`
                      WHERE `computers_id` = '$computers_id'";
            $result = $DB->query($query);

            //Reload import_software from DB
            if ($DB->numrows($result)) {
               $tmp = $DB->fetch_array($result);
               $import_software = importArrayFromDB($tmp["import_software"]);
            }
         }
         //---- Get all the softwares for this machine from OCS -----//
         if ($cfg_ocs["use_soft_dict"]) {
            $query2 = "SELECT `softwares`.`NAME` AS INITNAME,
                              `dico_soft`.`FORMATTED` AS NAME,
                              `softwares`.`VERSION` AS VERSION,
                              `softwares`.`PUBLISHER` AS PUBLISHER,
                              `softwares`.`COMMENTS` AS COMMENTS
                       FROM `softwares`
                       INNER JOIN `dico_soft` ON (`softwares`.`NAME` = dico_soft.EXTRACTED)
                       WHERE `softwares`.`HARDWARE_ID` = '$ocsid'";
         } else {
            $query2 = "SELECT `softwares`.`NAME` AS INITNAME,
                              `softwares`.`NAME` AS NAME,
                              `softwares`.`VERSION` AS VERSION,
                              `softwares`.`PUBLISHER` AS PUBLISHER,
                              `softwares`.`COMMENTS` AS COMMENTS
                       FROM `softwares`
                       WHERE `softwares`.`HARDWARE_ID` = '$ocsid'";
         }
         $result2 = $DBocs->query($query2);
         $to_add_to_ocs_array = array ();
         $soft = new Software;

         if ($DBocs->numrows($result2) > 0) {
            while ($data2 = $DBocs->fetch_array($result2)) {
               $data2 = clean_cross_side_scripting_deep(addslashes_deep($data2));
               $initname = $data2["INITNAME"];
               // Hack for OCS encoding problems
               if (!$cfg_ocs["ocs_db_utf8"] && !seems_utf8($initname)) {
                  $initname = encodeInUtf8($initname);
               }
               $name = $data2["NAME"];
               // Hack for OCS encoding problems
               if (!$cfg_ocs["ocs_db_utf8"] && !seems_utf8($name)) {
                  $name = encodeInUtf8($name);
               }
               $version = $data2["VERSION"];
               $manufacturer = Manufacturer::processName($data2["PUBLISHER"]);
               $use_glpi_dictionnary = false;
               if (!$cfg_ocs["use_soft_dict"]) {
                  //Software dictionnary
                  $rulecollection = new RuleDictionnarySoftwareCollection;
                  $res_rule = $rulecollection->processAllRules(array("name"         => $name,
                                                                     "manufacturer" => $manufacturer,
                                                                     "old_version"  => $version),
                                                               array (), array());
                  $res_rule = addslashes_deep($res_rule);

                  if (isset($res_rule["name"])) {
                     $modified_name = $res_rule["name"];
                  } else {
                     $modified_name = $name;
                  }
                  if (isset($res_rule["version"]) && $res_rule["version"]!= '') {
                     $modified_version = $res_rule["version"];
                  } else {
                     $modified_version = $version;
                  }
               } else {
                  $modified_name = $name;
                  $modified_version = $version;
               }

               //Ignore this software
               if (!isset($res_rule["_ignore_ocs_import"]) || !$res_rule["_ignore_ocs_import"]) {
                  // Clean software object
                  $soft->reset();
                  //If name+version not in present for this computer in glpi, add it
                  if (!in_array(stripslashes($initname . self::FIELD_SEPARATOR. $version),
                                $import_software)) {
                     //------------------------------------------------------------------------//
                     //---- The software doesn't exists in this version for this computer -----//
                     //------------------------------------------------------------------------//
                     $isNewSoft = $soft->addOrRestoreFromTrash($modified_name, $manufacturer,
                                                               $entity);
                     //Import version for this software
                     $versionID = OcsServer::importVersion($isNewSoft, $modified_version);
                     //Install license for this machine
                     $instID = self::installSoftwareVersion($computers_id, $versionID, $dohistory);
                     //Add the software to the table of softwares for this computer to add in database
                     $to_add_to_ocs_array[$instID] = $initname . self::FIELD_SEPARATOR. $version;
                  } else {
                     $instID = -1;
                     //-------------------------------------------------------------------------//
                     //---- The software exists in this version for this computer --------------//
                     //---------------------------------------------------- --------------------//

                     //Get the name of the software in GLPI to know if the software's name
                     //have already been changed by the OCS dictionnary
                     $instID = array_search(stripslashes($initname . self::FIELD_SEPARATOR. $version),
                                            $import_software);

                     $query_soft = "SELECT `glpi_softwares`.`id`,
                                           `glpi_softwares`.`name`
                                    FROM `glpi_softwares`,
                                         `glpi_computers_softwareversions`,
                                         `glpi_softwareversions`
                                    WHERE `glpi_computers_softwareversions`.`id` = '$instID'
                                          AND `glpi_computers_softwareversions`.`softwareversions_id`
                                                = `glpi_softwareversions`.`id`
                                          AND `glpi_softwareversions`.`softwares_id`
                                                = `glpi_softwares`.`id`";
                     $result_soft = $DB->query($query_soft);
                     $tmpsoft = $DB->fetch_array($result_soft);
                     $softName = $tmpsoft["name"];
                     $softID = $tmpsoft["id"];
                     $s = new Software;
                     $input["id"]=$softID;

                     //First, get the name of the software into GLPI db IF dictionnary is used
                     if ($cfg_ocs["use_soft_dict"]) {
                        //First use of the OCS dictionnary OR name changed in the dictionnary
                        if ($softName != $name) {
                           $input["name"] = $name;
                           $s->update($input);
                        }
                     } else if ($softName != $modified_name) {
                        // OCS Dictionnary not use anymore : revert to original name
                        $input["name"] = $modified_name;
                        $s->update($input);
                     }
                     unset ($import_software[$instID]);
                  }
               }
            }
         }
         //Remove the tag from the import_software array
         unset ($import_software[0]);

         //Add all the new softwares
         if (count($to_add_to_ocs_array)) {
            OcsServer::addToOcsArray($computers_id, $to_add_to_ocs_array, "import_software");
         }

         // Remove softwares not present in OCS
         if (count($import_software)) {
            $inst = new Computer_SoftwareVersion();
            foreach ($import_software as $key => $val) {
               $query = "SELECT *
                         FROM `glpi_computers_softwareversions`
                         WHERE `id` = '$key'";
               $result = $DB->query($query);
               if ($DB->numrows($result) > 0) {
                  if ($data = $DB->fetch_assoc($result)) {
                     $inst->delete(array('id'          => $key,
                                         '_no_history' => !$dohistory));

                     if (countElementsInTable('glpi_computers_softwareversions',
                              "softwareversions_id = '".$data['softwareversions_id']."'") == 0
                         && countElementsInTable('glpi_softwarelicenses',
                              "softwareversions_id_buy ='" .$data['softwareversions_id']."'") == 0) {

                        $vers = new SoftwareVersion;

                        if ($vers->getFromDB($data['softwareversions_id'])
                            && countElementsInTable('glpi_softwarelicenses',
                                    "softwares_id = '".$vers->fields['softwares_id']."'") == 0
                            && countElementsInTable('glpi_softwareversions',
                                    "softwares_id = '".$vers->fields['softwares_id']."'") == 1) {
                           // 1 is the current to be removed
                           $soft->putInTrash($vers->fields['softwares_id'], $LANG['ocsng'][54]);
                        }
                        $vers->delete(array ("id" => $data['softwareversions_id']));
                     }
                  }
               }
               OcsServer::deleteInOcsArray($computers_id, $key, "import_software");
            }
         }
      }
   }


   /**
    * Update config of the registry
    *
    * This function erase old data and import the new ones about registry (Microsoft OS after Windows 95)
    *
    *@param $computers_id integer : glpi computer id.
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@return Nothing (void).
    *
    **/
   static function updateRegistry($computers_id, $ocsid, $ocsservers_id, $cfg_ocs) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      if ($cfg_ocs["import_registry"]) {
         //before update, delete all entries about $computers_id
         $query_delete = "DELETE
                          FROM `glpi_registrykeys`
                          WHERE `computers_id` = '$computers_id'";
         $DB->query($query_delete);

         //Get data from OCS database
         $query = "SELECT `registry`.`NAME` AS name,
                          `registry`.`REGVALUE` AS regvalue,
                          `registry`.`HARDWARE_ID` AS computers_id,
                          `regconfig`.`REGTREE` AS regtree,
                          `regconfig`.`REGKEY` AS regkey
                   FROM `registry`
                   LEFT JOIN `regconfig` ON (`registry`.`NAME` = `regconfig`.`NAME`)
                   WHERE `HARDWARE_ID` = '$ocsid'";
         $result = $DBocs->query($query);
         if ($DBocs->numrows($result) > 0) {
            $reg = new RegistryKey();
            //update data
            while ($data = $DBocs->fetch_array($result)) {
               $data = clean_cross_side_scripting_deep(addslashes_deep($data));
               $input = array ();
               $input["computers_id"] = $computers_id;
               $input["hive"]         = $data["regtree"];
               $input["value"]        = $data["regvalue"];
               $input["path"]         = $data["regkey"];
               $input["ocs_name"]     = $data["name"];
               $isNewReg = $reg->add($input);
               unset($reg->fields);
            }
         }
      }
      return;
   }


   /**
    * Update the administrative informations
    *
    * This function erase old data and import the new ones about administrative informations
    *
    *@param $computers_id integer : glpi computer id.
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : configuration ocs of the server
    *@param $computer_updates array : already updated fields of the computer
    *@param $entity integer : entity of the computer
    *@param $dohistory boolean : log changes ?
    *
    *@return Nothing (void).
    *
    **/
   static function updateAdministrativeInfo($computers_id, $ocsid, $ocsservers_id, $cfg_ocs,
                                            $computer_updates, $entity, $dohistory) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      //check link between ocs and glpi column
      $queryListUpdate = "SELECT *
                          FROM `glpi_ocsadmininfoslinks`
                          WHERE `ocsservers_id` = '$ocsservers_id' ";
      $result = $DB->query($queryListUpdate);
      if ($DB->numrows($result) > 0) {
         $queryOCS = "SELECT *
                      FROM `accountinfo`
                      WHERE `HARDWARE_ID` = '$ocsid'";
         $resultOCS = $DBocs->query($queryOCS);
         if ($DBocs->numrows($resultOCS) > 0) {
            $data_ocs = $DBocs->fetch_array($resultOCS);
            $comp = new Computer();

            //update data
            while ($links_glpi_ocs = $DB->fetch_array($result)) {
               //get info from ocs
               $ocs_column  = $links_glpi_ocs['ocs_column'];
               $glpi_column = $links_glpi_ocs['glpi_column'];
               if (isset ($data_ocs[$ocs_column]) && !in_array($glpi_column, $computer_updates)) {
                  $var = $data_ocs[$ocs_column];
                  switch ($glpi_column) {
                     case "groups_id" :
                        $var = OcsServer::importGroup($var, $entity);
                        break;

                     case "locations_id" :
                        $var = Dropdown::importExternal("Location", $var, $entity);
                        break;

                     case "networks_id" :
                        $var = Dropdown::importExternal("Network", $var);
                        break;
                  }
                  $input = array ();
                  $input[$glpi_column] = $var;
                  $input["id"] = $computers_id;
                  $comp->update($input, $dohistory);
               }
            }
         }
      }
   }


   /**
    * Import the devices for a computer
    *
    *@param $itemtype integer : item type
    *@param $entity integer : entity of the computer
    *@param $computers_id integer : glpi computer id.
    *@param $ocsid integer : ocs computer id (ID).
    *@param $ocsservers_id integer : ocs server id
    *@param $cfg_ocs array : ocs config
    *@param $import_periph array : already imported periph
    *@param $dohistory boolean : log changes ?
    *
    *@return Nothing (void).
    *
    **/
   static function updatePeripherals($itemtype, $entity, $computers_id, $ocsid, $ocsservers_id,
                                     $cfg_ocs, $import_periph, $dohistory) {
      global $DB, $DBocs;

      OcsServer::checkOCSconnection($ocsservers_id);
      $do_clean = false;
      $connID = 0;
      //Tag for data since 0.70 for the import_monitor array.
      $count_monitor = count($import_periph);
      switch ($itemtype) {
         case 'Monitor' :
            if ($cfg_ocs["import_monitor"]) {
               //Update data in import_monitor array for 0.70
               if (!in_array(self::IMPORT_TAG_070, $import_periph)) {
                  foreach ($import_periph as $key => $val) {
                     $monitor_tag = $val;
                     //delete old value
                     OcsServer::deleteInOcsArray($computers_id, $key, "import_monitor");
                     //search serial when it exists
                     $monitor_serial = "";
                     $query_monitor_id = "SELECT `items_id`
                                          FROM `glpi_computers_items`
                                          WHERE `id` = '$key'";
                     $result_monitor_id = $DB->query($query_monitor_id);
                     if ($DB->numrows($result_monitor_id) == 1) {
                        //get monitor Id
                        $id_monitor = $DB->result($result_monitor_id, 0, "items_id");
                        $query_monitor_serial = "SELECT `serial`
                                                 FROM `glpi_monitors`
                                                 WHERE `id` = '$id_monitor'";
                        $result_monitor_serial = $DB->query($query_monitor_serial);
                        //get serial
                        if ($DB->numrows($result_monitor_serial) == 1) {
                           $monitor_serial = $DB->result($result_monitor_serial, 0, "serial");
                        }
                     }
                     //concat name + serial
                     $monitor_tag .= $monitor_serial;
                     //add new value (serial + name when its possible)
                     OcsServer::addToOcsArray($computers_id, array ($key => $monitor_tag),
                                              "import_monitor");
                     //Update the array with the new value of the monitor
                     $import_periph[$key] = $monitor_tag;
                  }
                  //add the tag for the array version's
                  OcsServer::addToOcsArray($computers_id, array(0 => self::IMPORT_TAG_070),
                                           "import_monitor");
               }
               $do_clean = true;
               $m = new Monitor;

               $query = "SELECT DISTINCT `CAPTION`, `MANUFACTURER`, `DESCRIPTION`, `SERIAL`, `TYPE`
                         FROM `monitors`
                         WHERE `HARDWARE_ID` = '$ocsid'";
               $result = $DBocs->query($query);
               $lines = array();
               $checkserial=true;

               // First pass - check if all serial present
               if ($DBocs->numrows($result) > 0) {
                  while ($line = $DBocs->fetch_array($result)) {
                     if (empty($line["SERIAL"])) {
                        $checkserial = false;
                     }
                     $lines[] = clean_cross_side_scripting_deep(addslashes_deep($line));
                  }
               }
               if (count($lines)>0
                   && ($cfg_ocs["import_monitor"]<=2 || $checkserial)) {
                  foreach ($lines as $line) {
                     $mon = array ();
                     $mon["name"] = $line["CAPTION"];
                     if (empty ($line["CAPTION"]) && !empty ($line["MANUFACTURER"])) {
                        $mon["name"] = $line["MANUFACTURER"];
                     }
                     if (empty ($line["CAPTION"]) && !empty ($line["TYPE"])) {
                        if (!empty ($line["MANUFACTURER"])) {
                           $mon["name"] .= " ";
                        }
                        $mon["name"] .= $line["TYPE"];
                     }
                     $mon["serial"] = $line["SERIAL"];
                     $checkMonitor = $mon["name"];
                     if (!empty ($mon["serial"])) {
                        $checkMonitor .= $mon["serial"];
                     }
                     if (!empty ($mon["name"])) {
                        $id = array_search(stripslashes($checkMonitor), $import_periph);
                     }
                     if ($id === false) {
                        // Clean monitor object
                        $m->reset();
                        $mon["manufacturers_id"] = Dropdown::importExternal('Manufacturer',
                                                                            $line["MANUFACTURER"]);
                        if ($cfg_ocs["import_monitor_comment"]) {
                           $mon["comment"] = $line["DESCRIPTION"];
                        }
                        $id_monitor = 0;

                        if ($cfg_ocs["import_monitor"] == 1) {
                           //Config says : manage monitors as global
                           //check if monitors already exists in GLPI
                           $mon["is_global"] = 1;
                           $query = "SELECT `id`
                                     FROM `glpi_monitors`
                                     WHERE `name` = '" . $mon["name"] . "'
                                           AND `is_global` = '1'
                                           AND `entities_id` = '$entity'";
                           $result_search = $DB->query($query);
                           if ($DB->numrows($result_search) > 0) {
                              //Periph is already in GLPI
                              //Do not import anything just get periph ID for link
                              $id_monitor = $DB->result($result_search, 0, "id");
                           } else {
                              $input = $mon;
                              if ($cfg_ocs["states_id_default"]>0) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $input["entities_id"] = $entity;
                              $id_monitor = $m->add($input);
                           }

                        } else if ($cfg_ocs["import_monitor"] >= 2) {
                           //COnfig says : manage monitors as single units
                           //Import all monitors as non global.
                           $mon["is_global"] = 0;

                           // Try to find a monitor with the same serial.
                           if (!empty ($mon["serial"])) {
                              $query = "SELECT `id`
                                        FROM `glpi_monitors`
                                        WHERE `serial` LIKE '%" . $mon["serial"] . "%'
                                              AND `is_global` = '0'
                                              AND `entities_id` = '$entity'";
                              $result_search = $DB->query($query);
                              if ($DB->numrows($result_search) == 1) {
                                 //Monitor founded
                                 $id_monitor = $DB->result($result_search, 0, "id");
                              }
                           }
                           //Search by serial failed, search by name
                           if ($cfg_ocs["import_monitor"]==2 && !$id_monitor) {
                              //Try to find a monitor with no serial, the same name and not already connected.
                              if (!empty ($mon["name"])) {
                                 $query = "SELECT `glpi_monitors`.`id`
                                           FROM `glpi_monitors`
                                           LEFT JOIN `glpi_computers_items`
                                             ON (`glpi_computers_items`.`itemtype`='Monitor'
                                                 AND `glpi_computers_items`.`items_id`=`glpi_monitors`.`id`)
                                           WHERE `serial` = ''
                                                 AND `name` = '" . $mon["name"] . "'
                                                 AND `is_global` = '0'
                                                 AND `entities_id` = '$entity'
                                                 AND `glpi_computers_items`.`computers_id` IS NULL";
                                 $result_search = $DB->query($query);
                                 if ($DB->numrows($result_search) == 1) {
                                    $id_monitor = $DB->result($result_search, 0, "id");
                                 }
                              }
                           }
                           if (!$id_monitor) {
                              $input = $mon;
                              if ($cfg_ocs["states_id_default"]>0) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $input["entities_id"] = $entity;
                              $id_monitor = $m->add($input);
                           }
                        } // ($cfg_ocs["import_monitor"] >= 2)

                        if ($id_monitor) {
                           //Import unique : Disconnect monitor on other computer done in Connect function
                           $conn = new Computer_Item();
                           $connID = $conn->add(array('computers_id' => $computers_id,
                                                      'itemtype'     => 'Monitor',
                                                      'items_id'     => $id_monitor,
                                                      '_no_history'  => !$dohistory));

                           if (!in_array(self::IMPORT_TAG_070, $import_periph)) {
                              OcsServer::addToOcsArray($computers_id, array (0 => self::IMPORT_TAG_070),
                                            "import_monitor");
                           }
                           OcsServer::addToOcsArray($computers_id, array ($connID => $checkMonitor),
                                                    "import_monitor");
                           $count_monitor++;

                           //Update column "is_deleted" set value to 0 and set status to default
                           $input = array ();
                           $old = new Monitor;
                           if ($old->getFromDB($id_monitor)) {
                              if ($old->fields["is_deleted"]) {
                                 $input["is_deleted"] = 0;
                              }
                              if ($cfg_ocs["states_id_default"]>0
                                  && $old->fields["states_id"]!=$cfg_ocs["states_id_default"]) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              if (empty($old->fields["name"]) && !empty($mon["name"])) {
                                 $input["name"] = $mon["name"];
                              }
                              if (empty($old->fields["serial"]) && !empty($mon["serial"])) {
                                 $input["serial"] = $mon["serial"];
                              }
                              if (count($input)) {
                                 $input["id"] = $id_monitor;
                                 $m->update($input);
                              }
                           }
                        }
                     } else { // found in array
                        unset ($import_periph[$id]);
                     }
                  } // foreach ligne 2625
               }
               if (in_array(self::IMPORT_TAG_070, $import_periph)) {
                  //unset the version Tag
                  unset ($import_periph[0]);
               }
            }
            break;

         case 'Printer' :
            if ($cfg_ocs["import_printer"]) {
               $do_clean = true;
               $query = "SELECT *
                         FROM `printers`
                         WHERE `HARDWARE_ID` = '$ocsid'";
               $result = $DBocs->query($query);
               $p = new Printer;

               if ($DBocs->numrows($result) > 0) {
                  while ($line = $DBocs->fetch_array($result)) {
                     $line = clean_cross_side_scripting_deep(addslashes_deep($line));
                     // TO TEST : PARSE NAME to have real name.
                     $print["name"] = $line["NAME"];
                     if (empty ($print["name"])) {
                        $print["name"] = $line["DRIVER"];
                     }
                     if (!empty ($print["name"])) {
                        if (!in_array(stripslashes($print["name"]), $import_periph)) {
                           // Clean printer object
                           $p->reset();
                           $print["comment"] = $line["PORT"] . "\r\n" . $line["DRIVER"];
                           $id_printer = 0;
                           if ($cfg_ocs["import_printer"] == 1) {
                              //Config says : manage printers as global
                              //check if printers already exists in GLPI
                              $print["is_global"] = 1;
                              $query = "SELECT `id`
                                        FROM `glpi_printers`
                                        WHERE `name` = '" . $print["name"] . "'
                                              AND `is_global` = '1'
                                              AND `entities_id` = '$entity'";
                              $result_search = $DB->query($query);
                              if ($DB->numrows($result_search) > 0) {
                                 //Periph is already in GLPI
                                 //Do not import anything just get periph ID for link
                                 $id_printer = $DB->result($result_search, 0, "id");
                              } else {
                                 $input = $print;
                                 if ($cfg_ocs["states_id_default"]>0) {
                                    $input["states_id"] = $cfg_ocs["states_id_default"];
                                 }
                                 $input["entities_id"] = $entity;
                                 $id_printer = $p->add($input);
                              }

                           } else if ($cfg_ocs["import_printer"] == 2) {
                              //COnfig says : manage printers as single units
                              //Import all printers as non global.
                              $input = $print;
                              $input["is_global"] = 0;
                              if ($cfg_ocs["states_id_default"]>0) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $input["entities_id"] = $entity;
                              $id_printer = $p->add($input);
                           }

                           if ($id_printer) {
                              $conn = new Computer_Item();
                              $connID = $conn->add(array('computers_id' => $computers_id,
                                                         'itemtype'     => 'Printer',
                                                         'items_id'     => $id_printer,
                                                         '_no_history'  => !$dohistory));
                              OcsServer::addToOcsArray($computers_id,
                                                       array ($connID => $print["name"]),
                                                       "import_printer");
                              //Update column "is_deleted" set value to 0 and set status to default
                              $input = array ();
                              $input["id"] = $id_printer;
                              $input["is_deleted"] = 0;
                              if ($cfg_ocs["states_id_default"]>0) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $p->update($input);
                           }

                        } else {
                           $id = array_search(stripslashes($print["name"]), $import_periph);
                           unset ($import_periph[$id]);
                        }
                     }
                  }
               }
            }
            break;

         case 'Peripheral' :
            if ($cfg_ocs["import_periph"]) {
               $do_clean = true;
               $p = new Peripheral;

               $query = "SELECT DISTINCT `CAPTION`, `MANUFACTURER`, `INTERFACE`, `TYPE`
                         FROM `inputs`
                         WHERE `HARDWARE_ID` = '$ocsid'
                               AND `CAPTION` <> ''";
               $result = $DBocs->query($query);
               if ($DBocs->numrows($result) > 0) {
                  while ($line = $DBocs->fetch_array($result)) {
                     $line = clean_cross_side_scripting_deep(addslashes_deep($line));
                     $periph["name"] = $line["CAPTION"];
                     if (!in_array(stripslashes($periph["name"]), $import_periph)) {
                        // Clean peripheral object
                        $p->reset();
                        if ($line["MANUFACTURER"] != "NULL") {
                           $periph["brand"] = $line["MANUFACTURER"];
                        }
                        if ($line["INTERFACE"] != "NULL") {
                           $periph["comment"] = $line["INTERFACE"];
                        }
                        $periph["peripheraltypes_id"] = Dropdown::importExternal('PeripheralType',
                                                                                 $line["TYPE"]);
                        $id_periph = 0;

                        if ($cfg_ocs["import_periph"] == 1) {
                           //Config says : manage peripherals as global
                           //check if peripherals already exists in GLPI
                           $periph["is_global"] = 1;
                           $query = "SELECT `id`
                                     FROM `glpi_peripherals`
                                     WHERE `name` = '" . $periph["name"] . "'
                                           AND `is_global` = '1'
                                           AND `entities_id` = '$entity'";
                           $result_search = $DB->query($query);
                           if ($DB->numrows($result_search) > 0) {
                              //Periph is already in GLPI
                              //Do not import anything just get periph ID for link
                              $id_periph = $DB->result($result_search, 0, "id");
                           } else {
                              $input = $periph;
                              if ($cfg_ocs["states_id_default"]>0){
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $input["entities_id"] = $entity;
                              $id_periph = $p->add($input);
                           }
                        } else if ($cfg_ocs["import_periph"] == 2) {
                           //Config says : manage peripherals as single units
                           //Import all peripherals as non global.
                           $input = $periph;
                           $input["is_global"] = 0;
                           if ($cfg_ocs["states_id_default"]>0) {
                              $input["states_id"] = $cfg_ocs["states_id_default"];
                           }
                           $input["entities_id"] = $entity;
                           $id_periph = $p->add($input);
                        }
                        if ($id_periph) {
                           $conn = new Computer_Item();
                           if ($connID = $conn->add(array('computers_id' => $computers_id,
                                                          'itemtype'     => 'Peripheral',
                                                          'items_id'     => $id_periph,
                                                          '_no_history'  => !$dohistory))) {
                              OcsServer::addToOcsArray($computers_id,
                                                       array($connID => $periph["name"]),
                                                       "import_peripheral");
                              //Update column "is_deleted" set value to 0 and set status to default
                              $input = array ();
                              $input["id"] = $id_periph;
                              $input["is_deleted"] = 0;
                              if ($cfg_ocs["states_id_default"]>0) {
                                 $input["states_id"] = $cfg_ocs["states_id_default"];
                              }
                              $p->update($input);
                           }
                        }

                     } else {
                        $id = array_search(stripslashes($periph["name"]), $import_periph);
                        unset ($import_periph[$id]);
                     }
                  }
               }
            }
            break;
      }

      // Disconnect Unexisting Items not found in OCS
      if ($do_clean && count($import_periph)) {
         $conn = new Computer_Item();

         foreach ($import_periph as $key => $val) {
            switch ($itemtype) {
               case 'Monitor' :
                  // Only if sync done
                  if ($cfg_ocs["import_monitor"]<=2 || $checkserial) {
                     $conn->delete(array('id'             => $key,
                                         '_ocsservers_id' => $ocsservers_id));
                     OcsServer::deleteInOcsArray($computers_id, $key, "import_monitor");
                  }
                  break;

               case 'Printer' :
                  $conn->delete(array('id'             => $key,
                                      '_ocsservers_id' => $ocsservers_id));
                  OcsServer::deleteInOcsArray($computers_id, $key, "import_printer");
                  break;

               case 'Peripheral' :
                  $conn->delete(array('id'             => $key,
                                      '_ocsservers_id' => $ocsservers_id));
                  OcsServer::deleteInOcsArray($computers_id, $key, "import_peripheral");
                  break;

               default :
                  $conn->delete(array('id'             => $key,
                                      '_ocsservers_id' => $ocsservers_id));
            }
         }
      }
   }


   static function cronInfo($name) {
      global $LANG;

      return array('description' => $LANG['crontask'][1]);
   }


   static function cronOcsng($task) {
      global $DB, $CFG_GLPI;

      //Get a randon server id
      $ocsservers_id = OcsServer::getRandomServerID();
      if ($ocsservers_id > 0) {
         //Initialize the server connection
         $DBocs = OcsServer::getDBocs($ocsservers_id);

         $cfg_ocs = OcsServer::getConfig($ocsservers_id);
         $task->log("Check updates from server " . $cfg_ocs['name'] . "\n");

         if (!$cfg_ocs["cron_sync_number"]) {
            return 0;
         }
         OcsServer::manageDeleted($ocsservers_id);

         $query = "SELECT MAX(`last_ocs_update`)
                   FROM `glpi_ocslinks`
                   WHERE `ocsservers_id`='$ocsservers_id'";
         $max_date="0000-00-00 00:00:00";
         if ($result=$DB->query($query)) {
            if ($DB->numrows($result)>0) {
               $max_date = $DB->result($result,0,0);
            }
         }

         $query_ocs = "SELECT *
                       FROM `hardware`
                       INNER JOIN `accountinfo` ON (`hardware`.`ID` = `accountinfo`.`HARDWARE_ID`)
                       WHERE ((`hardware`.`CHECKSUM` & " . $cfg_ocs["checksum"] . ") > '0'
                              OR `hardware`.`LASTDATE` > '$max_date') ";

         // workaround to avoid duplicate when synchro occurs during an inventory
         // "after" insert in ocsweb.hardware  and "before" insert in ocsweb.deleted_equiv
         $query_ocs .= " AND TIMESTAMP(`LASTDATE`) < (NOW()-180) ";

         $tag_limit = OcsServer::getTagLimit($cfg_ocs);
         if (!empty($tag_limit)) {
            $query_ocs .= "AND ".$tag_limit;
         }

         $query_ocs .= " ORDER BY `hardware`.`LASTDATE` ASC
                        LIMIT ".intval($cfg_ocs["cron_sync_number"]);

         $result_ocs = $DBocs->query($query_ocs);
         $nbcomp = $DBocs->numrows($result_ocs);
         if ($nbcomp > 0) {
            while ($data = $DBocs->fetch_array($result_ocs)) {
               $task->log("Update computer " . $data["ID"] . "\n");
               OcsServer::processComputer($data["ID"], $ocsservers_id, 0, -1, -1, 1);
            }
            $task->setVolume($nbcomp);
         } else {
            return 0;
         }
      }
      return 1;
   }
}
?>
