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
   define('GLPI_ROOT', realpath('..'));
}

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

global $CONTAINER;
$translation_cache = $CONTAINER->get('translation_cache');
$translation_cache->clear(); // Force cache cleaning to prevent usage of outdated cache data

Config::detectRootDoc();

$GLPI = new GLPI();
$GLPI->initLogger();

$DB = \Glpi\DatabaseFactory::create();
$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

$update = new Update($DB);
$update->initSession();

if (isset($_POST['update_end'])) {
   if (isset($_POST['send_stats'])) {
      Telemetry::enable();
   }
   header('Location: ../index.php');
}

//test la connection a la base de donn???.
function test_connect() {
   global $DB;

   return !$DB->error();
}

//update database
function doUpdateDb() {
   global $DB, $migration, $update;

   $currents            = $update->getCurrents();
   $current_version     = $currents['version'];
   $current_db_version  = $currents['dbversion'];

   $migration = new Migration(GLPI_SCHEMA_VERSION);
   $update->setMigration($migration);

   if (defined('GLPI_PREVER')) {
      if ($current_db_version != GLPI_SCHEMA_VERSION && !isset($_POST['agree_dev'])) {
         return;
      }
   }

   $update->doUpdates($current_version);
}

//Debut du script
$HEADER_LOADED = true;

Session::start();

Session::loadLanguage('', false);

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE html>";
echo "<html lang='fr' class='legacy'>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta http-equiv='Content-Script-Type' content='text/javascript'>";
echo "<meta http-equiv='Content-Style-Type' content='text/css'>";
echo "<title>Setup GLPI</title>";
//JS
echo Html::script("public/lib/jquery/jquery.js");
echo Html::script('public/lib/jquery-migrate/jquery-migrate.js');
echo Html::script('public/lib/jquery-ui-dist/jquery-ui.js');
// CSS
echo "<link rel='stylesheet' href='../css/style_install.css' type='text/css' media='screen' >";
echo Html::css('public/lib/jquery-ui-dist/jquery-ui.css');
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
      echo "<h3><span class='migred'>".__('Impossible to accomplish an update by this way!')."</span>";
      echo "<p>";
      echo "<a class='vsubmit' href='../index.php'>".__('Go back to GLPI')."</a></p>";
      echo "</div>";

   } else {
      echo "<div class='center'>";
      echo "<h3><span class='migred'>".sprintf(__('Caution! You will update the GLPI database named: %s'), $DB->dbdefault) ."</h3>";

      echo "<form action='update.php' method='post'>";
      if (strlen(GLPI_SCHEMA_VERSION) > 40) {
         echo Config::agreeDevMessage();
      }
      echo "<input type='submit' class='submit' name='continuer' value=\"".__('Continue')."\">";
      Html::closeForm();
      echo "</div>";
   }

} else {
   // Step 2
   if (test_connect()) {
      echo "<h3>".__('Database connection successful')."</h3>";
      echo "<p class='center'>";
      $result = Config::displayCheckDbEngine(true);
      echo "</p>";
      if ($result > 0) {
         die(1);
      }

      echo "<p class='center'>";
      $result = Config::displayCheckInnoDB(true);
      echo "</p>";
      if ($result > 0) {
         die(1);
      }

      echo "<div class='center'>";
      doUpdateDb();

      echo "<form action='".$CFG_GLPI["root_doc"]."/install/update.php' method='post'>";
      echo "<input type='hidden' name='update_end' value='1'/>";

      echo "<hr />";
      echo "<h2>".__('One last thing before starting')."</h2>";
      echo "<p>";
      echo GlpiNetwork::showInstallMessage();
      echo "</p>";
      echo "<a href='".GLPI_NETWORK_SERVICES."' target='_blank' class='vsubmit'>".
         __('Donate')."</a><br /><br />";

      if (!Telemetry::isEnabled()) {
         echo "<hr />";
         echo Telemetry::showTelemetry();
      }
      echo Telemetry::showReference();

      echo "<p class='submit'><input type='submit' name='submit' class='submit' value='".
            __('Use GLPI')."'></p>";
      Html::closeForm();
      echo "</div>";

   } else {
      echo "<h3>";
      echo __("Connection to database failed, verify the connection parameters included in db.yaml file")."</h3>";
   }
}

echo "</div></div></body></html>";
