<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('..'));
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");
Config::detectRootDoc();

// Old itemtype for compatibility
define("GENERAL_TYPE",         0);
define("COMPUTER_TYPE",        1);
define("NETWORKING_TYPE",      2);
define("PRINTER_TYPE",         3);
define("MONITOR_TYPE",         4);
define("PERIPHERAL_TYPE",      5);
define("SOFTWARE_TYPE",        6);
define("CONTACT_TYPE",         7);
define("ENTERPRISE_TYPE",      8);
define("INFOCOM_TYPE",         9);
define("CONTRACT_TYPE",       10);
define("CARTRIDGEITEM_TYPE",  11);
define("TYPEDOC_TYPE",        12);
define("DOCUMENT_TYPE",       13);
define("KNOWBASE_TYPE",       14);
define("USER_TYPE",           15);
define("TRACKING_TYPE",       16);
define("CONSUMABLEITEM_TYPE", 17);
define("CONSUMABLE_TYPE",     18);
define("CARTRIDGE_TYPE",      19);
define("SOFTWARELICENSE_TYPE",20);
define("LINK_TYPE",           21);
define("STATE_TYPE",          22);
define("PHONE_TYPE",          23);
define("DEVICE_TYPE",         24);
define("REMINDER_TYPE",       25);
define("STAT_TYPE",           26);
define("GROUP_TYPE",          27);
define("ENTITY_TYPE",         28);
define("RESERVATION_TYPE",    29);
define("AUTHMAIL_TYPE",       30);
define("AUTHLDAP_TYPE",       31);
define("OCSNG_TYPE",          32);
define("REGISTRY_TYPE",       33);
define("PROFILE_TYPE",        34);
define("MAILGATE_TYPE",       35);
define("RULE_TYPE",           36);
define("TRANSFER_TYPE",       37);
define("BOOKMARK_TYPE",       38);
define("SOFTWAREVERSION_TYPE",39);
define("PLUGIN_TYPE",         40);
define("COMPUTERDISK_TYPE",   41);
define("NETWORKING_PORT_TYPE",42);
define("FOLLOWUP_TYPE",       43);
define("BUDGET_TYPE",         44);

// Old devicetype for compatibility
define("MOBOARD_DEVICE",   1);
define("PROCESSOR_DEVICE", 2);
define("RAM_DEVICE",       3);
define("HDD_DEVICE",       4);
define("NETWORK_DEVICE",   5);
define("DRIVE_DEVICE",     6);
define("CONTROL_DEVICE",   7);
define("GFX_DEVICE",       8);
define("SND_DEVICE",       9);
define("PCI_DEVICE",      10);
define("CASE_DEVICE",     11);
define("POWER_DEVICE",    12);

// Use default session dir if not writable
if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
}

// Init debug variable
// Only show errors
Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

$DB = new DB();


/* ----------------------------------------------------------------- */

/*---------------------------------------------------------------------*/
/**
 * To be conserved to migrations before 0.80
 * since 0.80, migration is a new class
**/
function displayMigrationMessage ($id, $msg="") {
   static $created = 0;
   static $deb;

   if ($created != $id) {
      if (empty($msg)) {
         $msg = __('Work in progress...');
      }
      echo "<div id='migration_message_$id'><p class='center'>$msg</p></div>";
      $created = $id;
      $deb     = time();

   } else {
      if (empty($msg)) {
         $msg = __('Task completed.');
      }
      $fin = time();
      $tps = Html::timestampToString($fin-$deb);
      echo "<script type='text/javascript'>document.getElementById('migration_message_$id').innerHTML =
             '<p class=\"center\" >$msg ($tps)</p>';</script>\n";
   }
   Html::glpi_flush();
}


/**
 * Add a dropdown if not exists (used by pre 0.78 update script)
 * Only use for simple dropdown (no entity and not tree)
 *
 * @param $table string table name
 * @param $name string name of the imported dropdown
 *
 * @return integer (ID of the existing/new dropdown)
**/
function update_importDropdown ($table, $name) {
   global $DB;

   $query = "SELECT `ID`
             FROM `".$table."`
             WHERE `name` = '".addslashes($name)."'";

   if ($result = $DB->query($query) ) {
      if ($DB->numrows($result) > 0) {
         return $DB->result($result,0,"ID");
      }
   }
   $query = "INSERT INTO `".$table."`
             (`name`)
             VALUES ('".addslashes($name)."')";
   if ($result = $DB->query($query)) {
      return $DB->insert_id();
   }
   return 0;
}


/**
 * Display the form of content update (addslashes compatibility (V0.4))
 *
 * @return nothing (displays)
 */
function showContentUpdateForm() {

   echo "<div class='center'>";
   echo "<h3>".__('Update successful, your database is up to date')."</h3>";
   echo "<p>".__('You must now proceed to updating your database content')."</p></div>";
   echo "<p>";
   echo "<a class='vsubmit' href='update_content.php'>".__('Continue?')."</a>";
}


///// FONCTION POUR UPDATE LOCATION

function validate_new_location() {
   global $DB;

   $query = " DROP TABLE `glpi_dropdown_locations`";
   $DB->query($query);

   $query = " ALTER TABLE `glpi_dropdown_locations_new`
              RENAME `glpi_dropdown_locations`";
   $DB->query($query);
}


function display_new_locations() {
   global $DB;

   $MAX_LEVEL  = 10;
   $SELECT_ALL = "";
   $FROM_ALL   = "";
   $ORDER_ALL  = "";

   for ($i=1 ; $i<=$MAX_LEVEL ; $i++) {
      $SELECT_ALL .= " , location$i.`name` AS NAME$i
                       , location$i.`parentID` AS PARENT$i ";
      $FROM_ALL   .= " LEFT JOIN `glpi_dropdown_locations_new` AS location$i
                           ON location".($i-1).".`ID` = location$i.`parentID` ";
      $ORDER_ALL  .= " , NAME$i";
   }

   $query = "SELECT location0.`name` AS NAME0,
                    location0.`parentID` AS PARENT0
                    $SELECT_ALL
             FROM `glpi_dropdown_locations_new` AS location0
             $FROM_ALL
             WHERE location0.`parentID` = '0'
             ORDER BY NAME0 $ORDER_ALL";
   $result = $DB->query($query);

   $data_old = array();
   echo "<table><tr>";

   for ($i=0 ; $i<=$MAX_LEVEL ; $i++) {
      echo "<th>$i</th><th>&nbsp;</th>";
   }
   echo "</tr>";

   while ($data = $DB->fetch_assoc($result)) {
      echo "<tr class=tab_bg_1>";
      for ($i=0 ; $i<=$MAX_LEVEL ; $i++) {

         if (!isset($data_old["NAME$i"])
             || ($data_old["PARENT$i"] != $data["PARENT$i"])
             || ($data_old["NAME$i"] != $data["NAME$i"])) {

            $name = $data["NAME$i"];

            if (isset($data["NAME".($i+1)]) && !empty($data["NAME".($i+1)])) {
               $arrow = "--->";
            } else {
               $arrow = "";
            }

         } else {
            $name  = "";
            $arrow = "";
         }

         echo "<td>".$name."</td>";
         echo "<td>$arrow</td>";
      }

      echo "</tr>";
      $data_old=$data;
   }

   $DB->free_result($result);
   echo "</table>";
}


function display_old_locations() {
   global $DB;

   $query = "SELECT *
             FROM `glpi_dropdown_locations`
             ORDER BY `name`";
   $result = $DB->query($query);

   while ($data = $DB->fetch_assoc($result)) {
      echo "<span class='b'>".$data['name']."</span> - ";
   }

   $DB->free_result($result);
}


function location_create_new($split_char, $add_first) {
   global $DB;

   $query_auto_inc = "ALTER TABLE `glpi_dropdown_locations_new`
                      CHANGE `ID` `ID` INT(11) NOT NULL";
   $result_auto_inc = $DB->query($query_auto_inc);

   $query = "SELECT MAX(`ID`) AS MAX
             FROM `glpi_dropdown_locations`";

   $result = $DB->query($query);
   $new_ID = $DB->result($result, 0, "MAX");
   $new_ID++;

   $query = "SELECT *
             FROM `glpi_dropdown_locations`";
   $result = $DB->query($query);

   $query_clear_new = "TRUNCATE TABLE `glpi_dropdown_locations_new`";
   $result_clear_new = $DB->query($query_clear_new);

   if (!empty($add_first)) {
      $root_ID = $new_ID;
      $new_ID++;
      $query_insert = "INSERT INTO `glpi_dropdown_locations_new`
                       VALUES ('$root_ID', '".addslashes($add_first)."', 0, '')";

      $result_insert = $DB->query($query_insert);

   } else {
      $root_ID = 0;
   }

   while ($data =  $DB->fetch_assoc($result)) {

      if (!empty($split_char)) {
         $splitter = explode($split_char, $data['name']);
      } else {
         $splitter = array($data['name']);
      }

      $up_ID = $root_ID;

      for ($i=0 ; $i<count($splitter)-1 ; $i++) {
         // Entree existe deja ??
         $query_search = "SELECT `ID`
                          FROM `glpi_dropdown_locations_new`
                          WHERE `name` = '".addslashes($splitter[$i])."'
                               AND `parentID` = '".$up_ID."'";
         $result_search = $DB->query($query_search);

         if ($DB->numrows($result_search)==1) { // Found
            $up_ID = $DB->result($result_search,0,"ID");
         } else { // Not FOUND -> INSERT
            $query_insert = "INSERT INTO `glpi_dropdown_locations_new`
                             VALUES ('$new_ID', '".addslashes($splitter[$i])."', '$up_ID', '')";
            $result_insert = $DB->query($query_insert);

            $up_ID = $new_ID++;
         }

      }

      // Ajout du dernier
      $query_insert = "INSERT INTO `glpi_dropdown_locations_new`
                       VALUES ('".$data["ID"]."', '".addslashes($splitter[count($splitter)-1])."',
                               '$up_ID', '')";
      $result_insert=$DB->query($query_insert);
   }

   $DB->free_result($result);
   $query_auto_inc = "ALTER TABLE `glpi_dropdown_locations_new`
                      CHANGE `ID` `ID` INT(11) NOT NULL AUTO_INCREMENT";
   $result_auto_inc = $DB->query($query_auto_inc);

}


///// FIN FONCTIONS POUR UPDATE LOCATION

function showLocationUpdateForm() {
   global $DB, $CFG_GLPI;

   if ((TableExists ("glpi_dropdown_locations")
        && FieldExists("glpi_dropdown_locations", "parentID", false))
       || (TableExists ("glpi_locations") && FieldExists("glpi_locations", "locations_id", false))) {
      updateTreeDropdown();
      return true;
   }

   if (!isset($_POST['root'])) {
      $_POST['root'] = '';
   }

   if (!isset($_POST['car_sep'])) {
      $_POST['car_sep'] = '';
   }

   if (!TableExists("glpi_dropdown_locations_new")) {
      $query = " CREATE TABLE `glpi_dropdown_locations_new` (
                  `ID` INT NOT NULL auto_increment,
                  `name` VARCHAR(255) NOT NULL ,
                  `parentID` INT NOT NULL ,
                  `comments` TEXT NULL ,
                 PRIMARY KEY (`ID`),
                 UNIQUE KEY (`name`,`parentID`),
                 KEY(`parentID`)) TYPE=MyISAM";
      $DB->queryOrDie($query, "LOCATION");
   }

   if (!isset($_POST["validate_location"])) {
      echo "<div class='center'>";
      echo "<h4>".__('Locations update')."</h4>";
      echo "<p>".__('The new structure is hierarchical')."</p>";
      echo "<p>".__('Provide a delimiter in order to automate the new hierarchy generation.')."<br>";
      echo __('You can also specify a root location which will include all the generated locations.');
      echo "</p>";
      echo "<form action='".$CFG_GLPI["root_doc"]."/install/update.php' method='post'>";
      echo "<p>".__('Delimiter')."&nbsp;".
            "<input type='text' name='car_sep' value='".$_POST['car_sep']."'></p>";
      echo "<p>".__('Root location').'&nbsp;'.
            "<input type='text' name='root' value='".$_POST['root']."'></p>";
      echo "<input type='submit' class='submit' name='new_location' value=\""._sx('button','Post')."\">";
      echo "<input type='hidden' name='from_update' value='from_update'>";
      Html::closeForm();
      echo "</div>";
   }

   if (isset($_POST["new_location"])) {
      location_create_new($_POST['car_sep'], $_POST['root']);
      echo "<h4>".__('Actual locations')." : </h4>";
      display_old_locations();
      echo "<h4>".__('New hierarchy')." : </h4>";
      display_new_locations();
      echo "<p>".__("This is the new hierarchy. If it's complete approve it.")."</p>";
      echo "<div class='center'>";
      echo "<form action='".$CFG_GLPI["root_doc"]."/install/update.php' method='post'>";
      echo "<input type='submit' class='submit' name='validate_location' value=\"".
             _sx('button','Post')."\">";
      echo "<input type='hidden' name='from_update' value='from_update'>";
      echo "</div>";
      Html::closeForm();

   } else if (isset($_POST["validate_location"])) {
      validate_new_location();
      updateTreeDropdown();
      return true;

   } else {
      display_old_locations();
   }
   exit();
}


//test la connection a la base de donn�.
function test_connect() {
   global $DB;

   if ($DB->error == 0) {
      return true;
   }
   return false;
}


//Change table2 from varchar to ID+varchar and update table1.chps with depends
function changeVarcharToID($table1, $table2, $chps) {
   global $DB;

   if (!FieldExists($table2, "ID", false)) {
      $query = " ALTER TABLE `$table2`
                 ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
      $DB->queryOrDie($query);
   }

   $query = "ALTER TABLE `$table1`
             ADD `temp` INT";
   $DB->queryOrDie($query);

   $query = "SELECT `$table1`.`ID` AS row1,
                    `$table2`.`ID` AS row2
             FROM `$table1`, `$table2`
             WHERE `$table2`.`name` = `$table1`.`$chps`";
   $result = $DB->queryOrDie($query);

   while ($line = $DB->fetch_assoc($result)) {
      $query = "UPDATE `$table1`
                SET `temp` = ". $line["row2"] ."
                WHERE `ID` = '". $line["row1"] ."'";
      $DB->queryOrDie($query);
   }
   $DB->free_result($result);

   $query = "ALTER TABLE `$table1`
             DROP `$chps`";
   $DB->queryOrDie($query);

   $query = "ALTER TABLE `$table1`
             CHANGE `temp` `$chps` INT";
   $DB->queryOrDie($query);
}



//update database up to 0.31
function updateDbUpTo031() {
   global $DB, $migration;

   $ret = array();

   // Before 0.31
   if (!TableExists("glpi_config") && !TableExists("glpi_configs")) {
      $query = "CREATE TABLE `glpi_config` (
                  `ID` int(11) NOT NULL auto_increment,
                  `num_of_events` varchar(200) NOT NULL default '',
                  `jobs_at_login` varchar(200) NOT NULL default '',
                  `sendexpire` varchar(200) NOT NULL default '',
                  `cut` varchar(200) NOT NULL default '',
                  `expire_events` varchar(200) NOT NULL default '',
                  `list_limit` varchar(200) NOT NULL default '',
                  `version` varchar(200) NOT NULL default '',
                  `logotxt` varchar(200) NOT NULL default '',
                  `root_doc` varchar(200) NOT NULL default '',
                  `event_loglevel` varchar(200) NOT NULL default '',
                  `mailing` varchar(200) NOT NULL default '',
                  `imap_auth_server` varchar(200) NOT NULL default '',
                  `imap_host` varchar(200) NOT NULL default '',
                  `ldap_host` varchar(200) NOT NULL default '',
                  `ldap_basedn` varchar(200) NOT NULL default '',
                  `ldap_rootdn` varchar(200) NOT NULL default '',
                  `ldap_pass` varchar(200) NOT NULL default '',
                  `admin_email` varchar(200) NOT NULL default '',
                  `mailing_signature` varchar(200) NOT NULL default '',
                  `mailing_new_admin` varchar(200) NOT NULL default '',
                  `mailing_followup_admin` varchar(200) NOT NULL default '',
                  `mailing_finish_admin` varchar(200) NOT NULL default '',
                  `mailing_new_all_admin` varchar(200) NOT NULL default '',
                  `mailing_followup_all_admin` varchar(200) NOT NULL default '',
                  `mailing_finish_all_admin` varchar(200) NOT NULL default '',
                  `mailing_new_all_normal` varchar(200) NOT NULL default '',
                  `mailing_followup_all_normal` varchar(200) NOT NULL default '',
                  `mailing_finish_all_normal` varchar(200) NOT NULL default '',
                  `mailing_new_attrib` varchar(200) NOT NULL default '',
                  `mailing_followup_attrib` varchar(200) NOT NULL default '',
                  `mailing_finish_attrib` varchar(200) NOT NULL default '',
                  `mailing_new_user` varchar(200) NOT NULL default '',
                  `mailing_followup_user` varchar(200) NOT NULL default '',
                  `mailing_finish_user` varchar(200) NOT NULL default '',
                  `ldap_field_name` varchar(200) NOT NULL default '',
                  `ldap_field_email` varchar(200) NOT NULL default '',
                  `ldap_field_location` varchar(200) NOT NULL default '',
                  `ldap_field_realname` varchar(200) NOT NULL default '',
                  `ldap_field_phone` varchar(200) NOT NULL default '',
                PRIMARY KEY (`ID`)
                ) TYPE=MyISAM AUTO_INCREMENT=2 ";
      $DB->queryOrDie($query);

      $query = "INSERT INTO `glpi_config`
                VALUES (1, '10', '1', '1', '80', '30', '15', ' 0.31', 'GLPI powered by indepnet',
                        '/glpi', '5', '0', '', '', '', '', '', '', 'admsys@xxxxx.fr', 'SIGNATURE',
                        '1', '1', '1', '1', '0', '0', '0', '0', '0', '0', '0', '0','1', '1', '1',
                        'uid', 'mail', 'physicaldeliveryofficename', 'cn', 'telephonenumber')";
      $DB->queryOrDie($query);

      echo "<p class='center'>Version > 0.31  </p>";
   }

   // Save if problem with session during update
   $glpilanguage = $_SESSION["glpilanguage"];

   // < 0.78
   if (TableExists("glpi_config")) {
      // Get current version
      // Use language from session, even if sometime not reliable
      $query = "SELECT `version`, 'language'
                FROM `glpi_config`";
      $result = $DB->queryOrDie($query, "get current version");

      $current_version = trim($DB->result($result,0,0));
      $glpilanguage    = trim($DB->result($result,0,1));
   // < 0.85
   } else if (FieldExists('glpi_configs', 'version')) {
      // Get current version and language
      $query = "SELECT `version`, `language`
                FROM `glpi_configs`";
      $result = $DB->queryOrDie($query, "get current version");

      $current_version = trim($DB->result($result,0,0));
      $glpilanguage    = trim($DB->result($result,0,1));
   } else {
      $configurationValues = Config::getConfigurationValues('core', array('version', 'language'));

      $current_version = $configurationValues['version'];
      $glpilanguage    = $configurationValues['language'];
   }

   // To prevent problem of execution time
   ini_set("max_execution_time", "0");

   $migration = new Migration(GLPI_VERSION);

   switch ($current_version) {
      case "0.31" :
         include_once("update_031_04.php");
         update031to04();

      case "0.4" :
      case "0.41" :
         include_once("update_04_042.php");
         update04to042();

      case "0.42" :
         showLocationUpdateForm();
         include_once("update_042_05.php");
         update042to05();

      case "0.5" :
         include_once("update_05_051.php");
         update05to051();

      case "0.51" :
      case "0.51a" :
         include_once("update_051_06.php");
         update051to06();

      case "0.6" :
         include_once("update_06_065.php");
         update06to065();

      case "0.65" :
         include_once("update_065_068.php");
         update065to068();

      case "0.68" :
         include_once("update_068_0681.php");
         update068to0681();

      case "0.68.1" :
      case "0.68.2" :
      case "0.68.3" :
         // Force update content
         if (showLocationUpdateForm()) {
            $query = "UPDATE `glpi_config`
                      SET `version` = ' 0.68.3x'";
            $DB->queryOrDie($query, "0.68.3");

            showContentUpdateForm();
            exit();
         }
      case "0.68.3x": // Special version for replay upgrade process from here
         include_once("update_0681_07.php");
         update0681to07();

      case "0.7" :
      case "0.70.1" :
      case "0.70.2" :
         include_once("update_07_071.php");
         update07to071();

      case "0.71" :
      case "0.71.1" :
         include_once("update_071_0712.php");
         update071to0712();

      case "0.71.2" :
         include_once("update_0712_0713.php");
         update0712to0713();

      case "0.71.3" :
      case "0.71.4" :
      case "0.71.5" :
      case "0.71.6" :
         include_once("update_0713_072.php");
         update0713to072();

      case "0.72" :
         include_once("update_072_0721.php");
         update072to0721();

      case "0.72.1" :
         include_once("update_0721_0722.php");
         update0721to0722();

      case "0.72.2" :
      case "0.72.21" :
         include_once("update_0722_0723.php");
         update0722to0723();

      case "0.72.3" :
      case "0.72.4" :
         include_once("update_0723_078.php");
         update0723to078();

      case "0.78" :
         include_once("update_078_0781.php");
         update078to0781();

      case "0.78.1" :
         include_once("update_0781_0782.php");
         update0781to0782();

      case "0.78.2":
      case "0.78.3":
      case "0.78.4":
      case "0.78.5":
         include_once("update_0782_080.php");
         update0782to080();

      case "0.80" :
         include_once("update_080_0801.php");
         update080to0801();

      case "0.80.1" :
      case "0.80.2" :
         include_once("update_0801_0803.php");
         update0801to0803();

      case "0.80.3" :
      case "0.80.4" :
      case "0.80.5" :
      case "0.80.6" :
      case "0.80.61" :
      case "0.80.7" :
         include_once("update_0803_083.php");
         update0803to083();

      case "0.83" :
         include_once("update_083_0831.php");
         update083to0831();

      case "0.83.1" :
      case "0.83.2" :
         include_once("update_0831_0833.php");
         update0831to0833();

      case "0.83.3" :
      case "0.83.31" :
      case "0.83.4" :
      case "0.83.5" :
      case "0.83.6" :
      case "0.83.7" :
      case "0.83.8" :
      case "0.83.9" :
      case "0.83.91" :
         include_once("update_0831_084.php");
         update0831to084();

      case "0.84" :
         include_once("update_084_0841.php");
         update084to0841();

      case "0.84.1" :
      case "0.84.2" :
         include_once("update_0841_0843.php");
         update0841to0843();

      case "0.84.3" :
         include_once("update_0843_0844.php");
         update0843to0844();

      case "0.84.4" :
      case "0.84.5" :
         include_once("update_0845_0846.php");
         update0845to0846();

      case "0.84.6" :
      case "0.84.7" :
      case "0.84.8" :
      case "0.84.9" :
         include_once("update_084_085.php");
         update084to085();

      case "0.85" :
      case "0.85.1" :
      case "0.85.2" :
         include_once("update_085_0853.php");
         update085to0853();

      case "0.85.3" :
      case "0.85.4" :
         include_once("update_0853_0855.php");
         update0853to0855();

      case "0.85.5" :
         include_once("update_0855_090.php");
         update0855to090();

      case "0.90" :
         include_once("update_090_0901.php");
         update090to0901();

      case "0.90.1" :
      case "0.90.2" :
      case "0.90.3" :
      case "0.90.4" :
         include("update_0901_0905.php");
         update0901to0905();

      case "0.90.5" :
         include_once("update_0905_91.php");
         update0905to91();

      case "9.1" :
      case "0.91":
         include_once("update_91_911.php");
         update91to911();

      case "9.1.1" :
      case "9.1.2" :
         include_once("update_911_913.php");
         update911to913();

      case "9.1.3" :
      case "9.1.4" :
      case "9.1.5" :
      case GLPI_VERSION:
         break;

      default :
         include_once("update_031_04.php");
         update031to04();
         include_once("update_04_042.php");
         update04to042();
         showLocationUpdateForm();
         include_once("update_042_05.php");
         update042to05();
         include_once("update_05_051.php");
         update05to051();
         include_once("update_051_06.php");
         update051to06();
         include_once("update_06_065.php");
         update06to065();
         include_once("update_065_068.php");
         update065to068();
         include_once("update_068_0681.php");
         update068to0681();
         // Force update content
         $query = "UPDATE `glpi_config`
                   SET `version` = ' 0.68.3x'";
         $DB->queryOrDie($query, "0.68.3");

         showContentUpdateForm();
         exit();
   }

   // Update version number and default langage and new version_founded ---- LEAVE AT THE END
   Config::setConfigurationValues('core', array('version'             => GLPI_VERSION,
                                                'language'            => $glpilanguage,
                                                'founded_new_version' => ''));

   // Update process desactivate all plugins
   $plugin = new Plugin();
   $plugin->unactivateAll();

   if (defined('GLPI_SYSTEM_CRON')) {
      // Downstream packages may provide a good system cron
      $query = "UPDATE `glpi_crontasks` SET `mode`=2 WHERE `name`!='watcher' AND (`allowmode` & 2)";
      $DB->queryOrDie($query);
   }

   DBmysql::optimize_tables($migration);

   return $ret;
}


function updateTreeDropdown() {
   global $DB;

   // Update Tree dropdown
   if (TableExists("glpi_dropdown_locations")
       && !FieldExists("glpi_dropdown_locations", "completename", false)) {
      $query = "ALTER TABLE `glpi_dropdown_locations`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in dropdown_locations");
   }

   if (TableExists("glpi_dropdown_kbcategories")
       && !FieldExists("glpi_dropdown_kbcategories", "completename", false)) {
      $query = "ALTER TABLE `glpi_dropdown_kbcategories`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in dropdown_kbcategories");
   }

   if (TableExists("glpi_locations") && !FieldExists("glpi_locations", "completename", false)) {
      $query = "ALTER TABLE `glpi_locations`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in glpi_locations");
   }

   if (TableExists("glpi_knowbaseitemcategories")
       && !FieldExists("glpi_knowbaseitemcategories", "completename", false)) {
      $query = "ALTER TABLE `glpi_knowbaseitemcategories`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in glpi_knowbaseitemcategories");
   }
}

//Debut du script
$HEADER_LOADED = true;

Session::start();

Session::loadLanguage();

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'
       'http://www.w3.org/TR/html4/loose.dtd'>";
echo "<html>";
echo "<head>";
echo "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>";
echo "<meta http-equiv='Content-Script-Type' content='text/javascript'>";
echo "<meta http-equiv='Content-Style-Type' content='text/css'>";
echo "<meta http-equiv='Content-Language' content='fr'>";
echo "<meta name='generator' content=''>";
echo "<meta name='DC.Language' content='fr' scheme='RFC1766'>";
echo "<title>Setup GLPI</title>";
// CSS
echo "<link rel='stylesheet' href='../css/style_install.css' type='text/css' media='screen' >";

echo "</head>";
echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div id='logo_bloc'></div>";
echo "<h2>GLPI SETUP</h2>";
echo "<br><h3>".__('Upgrade')."</h3>";

// step 1    avec bouton de confirmation

if (empty($_POST["continuer"]) && empty($_POST["from_update"])) {

   if (empty($from_install) && !isset($_POST["from_update"])) {
      echo "<div class='center'>";
      echo "<h3><span class='red'>".__('Impossible to accomplish an update by this way!')."</span>";
      echo "<p>";
      echo "<a class='vsubmit' href='../index.php'>".__('Go back to GLPI')."</a></p>";
      echo "</div>";

   } else {
      echo "<div class='center'>";
      echo "<h3><span class='red'>".sprintf(__('Caution! You will update the GLPI database named: %s'),$DB->dbdefault) ."</h3>";

      echo "<form action='update.php' method='post'>";
      echo "<input type='submit' class='submit' name='continuer' value=\"".__('Continue')."\">";
      Html::closeForm();
      echo "</div>";
   }

// Step 2
} else {
   if (test_connect()) {
      echo "<h3>".__('Database connection successful')."</h3>";
      if (!isset($_POST["update_location"])) {
         $current_verison = "0.31";
         $config_table    = "glpi_config";

         if (TableExists("glpi_configs")) {
            $config_table = "glpi_configs";
         }

         // Find 2 tables to manage databases before 0.78
         if (!TableExists($config_table)) {
            include_once("update_to_031.php");
            updateDbTo031();
            $tab = updateDbUpTo031();

         } else {
            $current_version = Config::getCurrentDBVersion();
            $tab = updateDbUpTo031();
         }

         echo "<div class='center'>";
         if (!empty($tab) && $tab["adminchange"]) {
            echo "<div class='center'> <h2>". __("All users having administrators rights have have been updated to 'super-admin' rights with the creation of his new user type.") ."<h2></div>";
         }

         if (showLocationUpdateForm()) {
            switch ($current_version) {
               case "0.31" :
               case "0.4" :
               case "0.41" :
               case "0.42" :
               case "0.5" :
               case "0.51" :
               case "0.51a" :
               case "0.6" :
               case "0.65" :
               case "0.68" :
               case "0.68.1" :
               case "0.68.2" :
               case "0.68.3" :
                  showContentUpdateForm();
                  break;

               default :
                  echo "<a class='vsubmit' href='../index.php'>".__('Use GLPI')."</a>";
            }
         }
         echo "</div>";
      }

   } else {
      echo "<h3>";
      echo __("Connection to database failed, verify the connection parameters included in config_db.php file")."</h3>";
   }

}

echo "</div></div></body></html>";
