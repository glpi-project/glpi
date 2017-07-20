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

if (in_array('--help', $_SERVER['argv'])) {
   die("usage: ".$_SERVER['argv'][0]."  [ --upgrade | --force ] [ --optimize ] [ --fr ]\n");
}

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

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


if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
} else {
   die("Can't write in ".GLPI_SESSION_DIR."\n");
}
Session::start();

// Init debug variable
Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);
$_SESSION['glpilanguage']  = (in_array('--fr', $_SERVER['argv']) ? 'fr_FR' : 'en_GB');

Session::loadLanguage();

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

/* ----------------------------------------------------------------- */
/**
 * Extends class Migration to redefine display mode
**/
class CliMigration extends Migration {


   function __construct($ver) {
      $this->deb = time();
      $this->setVersion($ver);
   }


   function setVersion($ver) {
      $this->version = $ver;
   }


   function displayMessage ($msg) {

      $msg .= " (".Html::clean(Html::timestampToString(time()-$this->deb)).")";
      echo str_pad($msg, 100)."\r";
   }


   function displayTitle($title) {
      echo "\n".str_pad(" $title ", 100, '=', STR_PAD_BOTH)."\n";
   }

   function addNewMessageArea($id) {
   }

   function displayWarning($msg, $red=false) {

      if ($red) {
         $msg = "** $msg";
      }
      echo str_pad($msg, 100)."\n";
   }
}

/*---------------------------------------------------------------------*/

if (!TableExists("glpi_configs")) {
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

   $current_version     = $configurationValues['version'];
   $glpilanguage        = $configurationValues['language'];
}

$migration = new CliMigration(GLPI_VERSION);

$migration->displayWarning("Current GLPI Data version: $current_version");
$migration->displayWarning("Current GLPI Code version: ".GLPI_VERSION);
$migration->displayWarning("Default GLPI Language: $glpilanguage");


// To prevent problem of execution time
ini_set("max_execution_time", "0");

// for change name of the version - to delete in next version
if (($current_version != "0.91") && (GLPI_VERSION != 9.1)) {
   if (version_compare($current_version, GLPI_VERSION, 'ne')
       && !in_array('--upgrade', $_SERVER['argv'])) {
      die("Upgrade required\n");
   }
}
switch ($current_version) {
   case "0.72.3" :
   case "0.72.4" :
      include_once("../install/update_0723_078.php");
      update0723to078();

   case "0.78" :
      include_once("../install/update_078_0781.php");
      update078to0781();

   case "0.78.1" :
      include_once("../install/update_0781_0782.php");
      update0781to0782();

   case "0.78.2":
   case "0.78.3":
   case "0.78.4":
   case "0.78.5":
      include_once("../install/update_0782_080.php");
      update0782to080();

   case "0.80" :
      include_once("../install/update_080_0801.php");
      update080to0801();
      // nobreak;

   case "0.80.1" :
   case "0.80.2" :
      include_once("../install/update_0801_0803.php");
      update0801to0803();
      // nobreak;

   case "0.80.3" :
   case "0.80.4" :
   case "0.80.5" :
   case "0.80.6" :
   case "0.80.61" :
   case "0.80.7" :
      include_once("../install/update_0803_083.php");
      update0803to083();
      // nobreak;

   case "0.83" :
      include_once("../install/update_083_0831.php");
      update083to0831();
      // nobreak;

   case "0.83.1" :
   case "0.83.2" :
      include_once("../install/update_0831_0833.php");
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
      include_once("../install/update_0831_084.php");
      update0831to084();

   case "0.84" :
      include_once("../install/update_084_0841.php");
      update084to0841();

   case "0.84.1" :
   case "0.84.2" :
      include_once("../install/update_0841_0843.php");
      update0841to0843();

   case "0.84.3" :
      include_once("../install/update_0843_0844.php");
      update0843to0844();

   case "0.84.4" :
   case "0.84.5" :
      include_once("../install/update_0845_0846.php");
      update0845to0846();

   case "0.84.6" :
   case "0.84.7" :
   case "0.84.8" :
   case "0.84.9" :
      include_once("../install/update_084_085.php");
      update084to085();

   case "0.85" :
   case "0.85.1" :
   case "0.85.2" :
      include_once("../install/update_085_0853.php");
      update085to0853();

   case "0.85.3" :
   case "0.85.4" :
      include_once("../install/update_0853_0855.php");
      update0853to0855();

   case "0.85.5" :
      include_once("../install/update_0855_090.php");
      update0855to090();

   case "0.90" :
      include_once("../install/update_090_0901.php");
      update090to0901();

   case "0.90.1" :
   case "0.90.2" :
   case "0.90.3" :
   case "0.90.4" :
      include_once("../install/update_0901_0905.php");
      update0901to0905();

   case "0.90.5" :
      include_once("../install/update_0905_91.php");
      update0905to91();

   /* remember to also change --force below for last version */
   case "0.91" : // // for change name of the version - to delete in next version
   case "9.1" :
      include_once("../install/update_91_911.php");
      update91to911();

   case "9.1.1" :
   case "9.1.2" :
      include_once("../install/update_911_913.php");
      update911to913();

   case "9.1.3":
   case "9.1.4":
   case "9.1.5":
   case GLPI_VERSION :
      break;

   default :
      die("Unsupported version ($current_version)\n");
}

if (version_compare($current_version, GLPI_VERSION, 'ne')) {

   // Update version number and default langage and new version_founded ---- LEAVE AT THE END
   Config::setConfigurationValues('core', array('version'             => GLPI_VERSION,
                                                'founded_new_version' => ''));

   // Update process desactivate all plugins
   $plugin = new Plugin();
   $plugin->unactivateAll();

   $migration->displayWarning("\nMigration Done.");

} else if (in_array('--force', $_SERVER['argv'])) {

   include_once("../install/update_911_913.php");
   update911to913();

   $migration->displayWarning("\nForced migration Done.");

} else {
   $migration->displayWarning("No migration needed.");
}


if (in_array('--optimize', $_SERVER['argv'])) {

   DBmysql::optimize_tables($migration);
   $migration->displayWarning("Optimize done.");
}
