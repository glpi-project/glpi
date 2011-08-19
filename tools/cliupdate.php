<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------

if (in_array('--help', $_SERVER['argv'])) {
   die("usage: ".$_SERVER['argv'][0]."  [ --upgrade | --force ] [ --optimize ]\n");
}

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '..');
}

include_once (GLPI_ROOT . "/config/define.php");
include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_ROOT . "/config/based_config.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");
Config::detectRootDoc();


if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
} else {
   die("Can't write in ".GLPI_SESSION_DIR."\n");
}
Session::start();

// Init debug variable
$_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
$_SESSION['glpilanguage']  = "en_GB";

Session::loadLanguage();

// Only show errors
$CFG_GLPI["debug_sql"]        = $CFG_GLPI["debug_vars"] = 0;
$CFG_GLPI["use_log_in_files"] = 1;
ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);
set_error_handler(array('Toolbox', 'userErrorHandlerDebug'));

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
      global $LANG;

      $this->deb     = time();
      $this->version = $ver;
   }


   function displayMessage ($msg) {

      $msg .= " (".Html::clean(Html::timestampToString(time()-$this->deb)).")";
      echo str_pad($msg, 100)."\r";
   }


   function displayTitle($title) {
      echo "\n".str_pad(" $title ", 100, '=', STR_PAD_BOTH)."\n";
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
   die("Bad schema\n");
}

$query = "SELECT `version`, `language`
          FROM `glpi_configs`";

$result          = $DB->query($query) or die("get current version ".$DB->error());
$current_version = trim($DB->result($result,0,0));
$glpilanguage    = trim($DB->result($result,0,1));

$migration = new CliMigration($current_version);

$migration->displayWarning("Current GLPI Data version: $current_version");
$migration->displayWarning("Current GLPI Code version: ".GLPI_VERSION);
$migration->displayWarning("Default GLPI Language: $glpilanguage");


// To prevent problem of execution time
ini_set("max_execution_time", "0");

if (version_compare($current_version, GLPI_VERSION, 'ne')
    && !in_array('--upgrade', $_SERVER['argv'])) {
   die("Upgrade required\n");
}

switch ($current_version) {
   case "0.78.2":
   case "0.78.3":
   case "0.78.4":
   case "0.78.5":
      include("../install/update_0782_080.php");
      update0782to080();

   case "0.80" :
      include("../install/update_080_0801.php");
      update080to0801();
      // nobreak;

   case "0.80.1" :
   case "0.80.2" :
      include("../install/update_0801_083.php");
      update0801to083();
      // nobreak;

   case GLPI_VERSION :
      break;

   default :
      die("Unsupported version ($current_version)\n");
}

if (version_compare($current_version, GLPI_VERSION, 'ne')) {

   // Update version number and default langage and new version_founded ---- LEAVE AT THE END
   $query = "UPDATE `glpi_configs`
             SET `version` = '".GLPI_VERSION."',
                 `founded_new_version` = ''";
   $DB->query($query) or die($LANG['update'][90].$DB->error());

   // Update process desactivate all plugins
   $plugin = new Plugin();
   $plugin->unactivateAll();

   $migration->displayWarning("Migration Done.");

} else if (in_array('--force', $_SERVER['argv'])) {

   include("../install/update_0801_083.php");
   update0801to083();

   $migration->displayWarning("Forced migration Done.");

} else {
   $migration->displayWarning("No migration needed.");
}


if (in_array('--optimize', $_SERVER['argv'])) {

   $migration->displayTitle($LANG['update'][139]);
   DBmysql::optimize_tables($migration);

   $migration->displayWarning("Optimize done.");
}
