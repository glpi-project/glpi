<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Cache\CacheManager;

// Be sure to use global objects if this file is included outside normal process
global $CFG_GLPI, $GLPI, $GLPI_CACHE;

include_once (GLPI_ROOT."/inc/based_config.php");
include_once (GLPI_ROOT."/inc/dbconnection.class.php");

Session::setPath();
Session::start();

// Default Use mode
if (!isset($_SESSION['glpi_use_mode'])) {
   $_SESSION['glpi_use_mode'] = Session::NORMAL_MODE;
}

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

//init cache
$cache_manager = new CacheManager();
$GLPI_CACHE = $cache_manager->getCoreCacheInstance();

Config::detectRootDoc();

if (!file_exists(GLPI_CONFIG_DIR . "/config_db.php")) {
   Session::loadLanguage('', false);
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

   //Database connection
   DBConnection::establishDBConnection((isset($USEDBREPLICATE) ? $USEDBREPLICATE : 0),
                                       (isset($DBCONNECTION_REQUIRED) ? $DBCONNECTION_REQUIRED : 0));

   // *************************** Statics config options **********************
   // ********************options d'installation statiques*********************
   // *************************************************************************

   //Options from DB, do not touch this part.

   if (!Config::loadLegacyConfiguration()) {
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

   if (isset($_SESSION["glpiroot"]) && $CFG_GLPI["root_doc"]!=$_SESSION["glpiroot"]) {
      Html::redirect($_SESSION["glpiroot"]);
   }

   if (!isset($_SESSION["glpilanguage"])) {
      $_SESSION["glpilanguage"] = Session::getPreferredLanguage();
   }

   // Override cfg_features by session value
   foreach ($CFG_GLPI['user_pref_field'] as $field) {
      if (!isset($_SESSION["glpi$field"]) && isset($CFG_GLPI[$field])) {
         $_SESSION["glpi$field"] = $CFG_GLPI[$field];
      }
   }

   // Check maintenance mode
   if (isset($CFG_GLPI["maintenance_mode"])
       && $CFG_GLPI["maintenance_mode"]
       && !isset($dont_check_maintenance_mode)) {
      if (isset($_GET['skipMaintenance']) && $_GET['skipMaintenance']) {
         $_SESSION["glpiskipMaintenance"] = 1;
      }

      if (!isset($_SESSION["glpiskipMaintenance"]) || !$_SESSION["glpiskipMaintenance"]) {
         Session::loadLanguage('', false);
         if (isCommandLine()) {
            echo __('Service is down for maintenance. It will be back shortly.');
            echo "\n";

         } else {
            Glpi\Application\View\TemplateRenderer::getInstance()->display('maintenance.html.twig', [
               'title'            => "MAINTENANCE MODE",
               'maintenance_text' => $CFG_GLPI["maintenance_text"] ?? "",
            ]);
         }
         exit();
      }
   }
   // Check version
   if ((!isset($CFG_GLPI['dbversion']) || (trim($CFG_GLPI["dbversion"]) != GLPI_SCHEMA_VERSION))
       && !isset($_GET["donotcheckversion"])) {

      Session::loadLanguage('', false);

      if (isCommandLine()) {
         echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.');
         echo "\n";

      } else {
         Html::nullHeader("UPDATE NEEDED", $CFG_GLPI["root_doc"]);
         echo "<div class='container-fluid mb-4'>";
         echo "<div class='row justify-content-evenly'>";
         echo "<div class='col-12 col-xxl-4'>";
         echo "<div class='card text-center mb-4'>";
         echo "<table class='table table-card'>";
         $error = Toolbox::commonCheckForUseGLPI();
         echo "</table><br>";

         if ($error) {
            echo "<form action='".$CFG_GLPI["root_doc"]."/index.php' method='post'>";
            echo Html::submit(__s('Try again'), [
               'class' => "btn btn-primary",
               'icon'  => "fas fa-redo",
            ]);
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
               } else if (isset($CFG_GLPI['dbversion']) && strlen($CFG_GLPI['dbversion']) > 40) {
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
               echo "<p class='mt-2 mb-n2 alert alert-important alert-warning'>";
               echo __('The version of the database is not compatible with the version of the installed files. An update is necessary.')."</p>";
               echo Html::submit(_sx('button', 'Upgrade'), [
                  'name'  => 'from_update',
                  'class' => "btn btn-primary",
                  'icon'  => "fas fa-check",
               ]);
               Html::closeForm();
            } else if ($newer === true) {
               echo "<p class='mt-2 mb-n2 alert alert-important alert-warning'>".
                     __('You are trying to use GLPI with outdated files compared to the version of the database. Please install the correct GLPI files corresponding to the version of your database.')."</p>";
            } else if ($dev === true) {
               echo "<p class='mt-2 mb-n2 alert alert-important alert-warning'>".
                     __('You are trying to update to a development version from a development version. This is not supported.')."</strong></p>";
            }
         }

         echo "</div>";
         echo "</div>";
         echo "</div>";
         echo "</div>";
         Html::nullFooter();
      }
      exit();
   }
}
