<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

include_once (GLPI_ROOT."/inc/based_config.php");
include_once (GLPI_ROOT."/inc/define.php");
include_once (GLPI_ROOT."/inc/dbconnection.class.php");

//init cache
$GLPI_CACHE = Config::getCache('cache_db');

Session::setPath();
Session::start();

Config::detectRootDoc();

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   Session::loadLanguage();
   // no translation
   if (!isCommandLine()) {
      Html::nullHeader("DB Error", $CFG_GLPI["root_doc"]);
      echo "<div class='center'>";
      echo "<p>Error: GLPI seems to not be configured properly.</p>";
      echo "<p>config_db.php file is missing.</p>";
      echo "<p>Please restart the install process.</p>";
      echo "<p><a class='red' href='".$CFG_GLPI['root_doc']."/install/install.php'>Click here to proceed</a></p>";
      echo "</div>";
      Html::nullFooter();

   } else {
      echo "Error: GLPI seems to not be configured properly.\n";
      echo "config_db.php file is missing.\n";
      echo "Please connect to GLPI web interface to complete the install process.\n";
   }
   die(1);

} else {
   include_once(GLPI_CONFIG_DIR . "/config_db.php");

   // Default Use mode
   if (!isset($_SESSION['glpi_use_mode'])) {
      $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
   }

   $GLPI = new GLPI();
   $GLPI->initLogger();

   //Database connection
   DBConnection::establishDBConnection((isset($USEDBREPLICATE) ? $USEDBREPLICATE : 0),
                                       (isset($DBCONNECTION_REQUIRED) ? $DBCONNECTION_REQUIRED : 0));

   // *************************** Statics config options **********************
   // ********************options d'installation statiques*********************
   // *************************************************************************

   //Options from DB, do not touch this part.

   $config_object  = new Config();
   $current_config = [];

   if (!isset($_GET['donotcheckversion'])  // use normal config table on restore process
       && (isset($TRY_OLD_CONFIG_FIRST) // index case
           || (isset($_SESSION['TRY_OLD_CONFIG_FIRST']) && $_SESSION['TRY_OLD_CONFIG_FIRST']))) { // backup case

      if (isset($_SESSION['TRY_OLD_CONFIG_FIRST'])) {
         unset($_SESSION['TRY_OLD_CONFIG_FIRST']);
      }

      // First try old config table : for update process management from < 0.80 to >= 0.80
      $config_object->forceTable('glpi_config');

      if ($DB->tableExists('glpi_config') && $config_object->getFromDB(1)) {
         $current_config = $config_object->fields;
      } else {
         $config_object->forceTable('glpi_configs');
         if ($config_object->getFromDB(1)) {
            if (isset($config_object->fields['context'])) {
               $current_config = Config::getConfigurationValues('core');
            } else {
               $current_config = $config_object->fields;
            }
            $config_ok = true;
         }
      }

   } else { // Normal load process : use normal config table. If problem try old one
      if ($config_object->getFromDB(1)) {
         if (isset($config_object->fields['context'])) {
            $current_config = Config::getConfigurationValues('core');
         } else {
            $current_config = $config_object->fields;
         }
      } else {
         // Manage glpi_config table before 0.80
         $config_object->forceTable('glpi_config');
         if ($config_object->getFromDB(1)) {
            $current_config = $config_object->fields;
         }
      }
   }

   if (count($current_config) > 0) {
      $CFG_GLPI = array_merge($CFG_GLPI, $current_config);

      if (isset($CFG_GLPI['priority_matrix'])) {
         $CFG_GLPI['priority_matrix'] = importArrayFromDB($CFG_GLPI['priority_matrix'],
                                                          true);
      }
      if (isset($CFG_GLPI['lock_item_list'])) {
          $CFG_GLPI['lock_item_list'] = importArrayFromDB($CFG_GLPI['lock_item_list']);
      }
      if (isset($CFG_GLPI['lock_lockprofile_id'])
          && $CFG_GLPI["lock_use_lock_item"]
          && ($CFG_GLPI["lock_lockprofile_id"] > 0)
          && !isset($CFG_GLPI['lock_lockprofile']) ) {

            $prof = new Profile();
            $prof->getFromDB($CFG_GLPI["lock_lockprofile_id"]);
            $prof->cleanProfile();
            $CFG_GLPI['lock_lockprofile'] = $prof->fields;
      }

      // Path for icon of document type (web mode only)
      if (isset($CFG_GLPI["root_doc"])) {
         $CFG_GLPI["typedoc_icon_dir"] = $CFG_GLPI["root_doc"]."/pics/icones";
      }

   } else {
      echo "Error accessing config table";
      exit();
   }

   if (isCommandLine()
       && isset($_SERVER['argv'])) {
      $key = array_search('--debug', $_SERVER['argv']);
      if ($key) {
         $_SESSION['glpi_use_mode'] = Session::DEBUG_MODE;
         unset($_SERVER['argv'][$key]);
         $_SERVER['argv']           = array_values($_SERVER['argv']);
         $_SERVER['argc']--;
      }
   }
   Toolbox::setDebugMode();

   //deprecated configuration options
   //@deprecated 9.4
   if ($_SESSION['glpi_use_mode'] != Session::DEBUG_MODE) {
      $_SESSION['glpiticket_timeline'] = 1;
      $_SESSION['glpiticket_timeline_keep_replaced_tabs'] = 0;
   } else {
      unset($_SESSION['glpiticket_timeline']);
      unset($_SESSION['glpiticket_timeline_keep_replaced_tabs']);
      unset($CFG_GLPI['use_rich_text']);
      unset($CFG_GLPI['ticket_timeline']);
      unset($CFG_GLPI['ticket_timeline_keep_replaced_tabs']);
   }

   if (isset($_SESSION["glpiroot"]) && $CFG_GLPI["root_doc"]!=$_SESSION["glpiroot"]) {
      Html::redirect($_SESSION["glpiroot"]);
   }

   // Override cfg_features by session value
   foreach ($CFG_GLPI['user_pref_field'] as $field) {
      if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
         $_SESSION["glpi$field"] = $CFG_GLPI[$field];
      }
   }

   // Check maintenance mode
   if (isset($CFG_GLPI["maintenance_mode"]) && $CFG_GLPI["maintenance_mode"]) {
      if (isset($_GET['skipMaintenance']) && $_GET['skipMaintenance']) {
         $_SESSION["glpiskipMaintenance"] = 1;
      }

      if (!isset($_SESSION["glpiskipMaintenance"]) || !$_SESSION["glpiskipMaintenance"]) {
         Session::loadLanguage();
         if (isCommandLine()) {
            echo __('Service is down for maintenance. It will be back shortly.');
            echo "\n";

         } else {
            Html::nullHeader("MAINTENANCE MODE", $CFG_GLPI["root_doc"]);
            echo "<div class='center'>";

            echo "<p class='red'>";
            echo __('Service is down for maintenance. It will be back shortly.');
            echo "</p>";
            if (isset($CFG_GLPI["maintenance_text"]) && !empty($CFG_GLPI["maintenance_text"])) {
               echo "<p>".$CFG_GLPI["maintenance_text"]."</p>";
            }
            echo "</div>";
            Html::nullFooter();
         }
         exit();
      }
   }
   // Check version
   if ((!isset($CFG_GLPI['dbversion']) || (trim($CFG_GLPI["dbversion"]) != GLPI_SCHEMA_VERSION))
       && !isset($_GET["donotcheckversion"])) {

      Session::loadLanguage();

      if (isCommandLine()) {
         echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.');
         echo "\n";

      } else {
         Html::nullHeader("UPDATE NEEDED", $CFG_GLPI["root_doc"]);
         echo "<div class='center'>";
         echo "<table class='tab_cadre'>";
         $error = Toolbox::commonCheckForUseGLPI();
         echo "</table><br>";

         if ($error) {
            echo "<form action='".$CFG_GLPI["root_doc"]."/index.php' method='post'>";
            echo "<input type='submit' name='submit' class='submit' value=\"".__s('Try again')."\">";
            Html::closeForm();
         }
         if ($error < 2) {
            $older = false;
            $newer = false;
            $dev   = false;

            if (!isset($CFG_GLPI["version"])) {
               $older = true;
            } else {
               if (strlen(GLPI_SCHEMA_VERSION) > 40) {
                  $dev   = true;
                  //got a sha1sum on both sides... cannot know if version is older or newer
                  if (!isset($CFG_GLPI['dbversion']) || strlen(trim($CFG_GLPI['dbversion'])) < 40) {
                     //not sure this is older... User will be warned.
                     if (trim($CFG_GLPI["version"]) < GLPI_PREVER) {
                        $older = true;
                     } else if (trim($CFG_GLPI['version']) >= GLPI_PREVER) {
                        $newer = true;
                     }
                  }
               } else if (strlen($CFG_GLPI['dbversion']) > 40) {
                  //got a dev version in database, but current stable
                  if (Toolbox::startsWith($CFG_GLPI['dbversion'], GLPI_SCHEMA_VERSION)) {
                     $older = true;
                  } else {
                     $newer = true;
                  }
               } else if (!isset($CFG_GLPI['dbversion']) || trim($CFG_GLPI["dbversion"]) < GLPI_SCHEMA_VERSION) {
                  $older = true;
               } else if (trim($CFG_GLPI["dbversion"]) > GLPI_SCHEMA_VERSION) {
                  $newer = true;
               }
            }

            if ($older === true) {
               echo "<form method='post' action='".$CFG_GLPI["root_doc"]."/install/update.php'>";
               if ($dev === true) {
                  echo Config::agreeDevMessage();
               }
               echo "<p class='red'>";
               echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.')."</p>";
               echo "<input type='submit' name='from_update' value=\""._sx('button', 'Upgrade')."\"
                      class='submit'>";
               Html::closeForm();
            } else if ($newer === true) {
               echo "<p class='red'>".
                     __('You are trying to use GLPI with outdated files compared to the version of the database. Please install the correct GLPI files corresponding to the version of your database.')."</p>";
            } else if ($dev === true) {
               echo "<p class='red'><strong>".
                     __('You are trying to update to a development version from a development version. This is not supported.')."</strong></p>";
            }
         }

         echo "</div>";
         Html::nullFooter();
      }
      exit();
   }

   $GLPI_CACHE = Config::getCache('cache_db');
}
