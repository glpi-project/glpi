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

use Glpi\Cache\CacheManager;

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('..'));
}

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

global $DB, $GLPI, $GLPI_CACHE;

$GLPI = new GLPI();
$GLPI->initLogger();
$GLPI->initErrorHandler();

$cache_manager = new CacheManager();
$GLPI_CACHE = $cache_manager->getCoreCacheInstance();
$GLPI_CACHE->clear(); // Force cache cleaning to prevent usage of outdated cache data

$translation_cache = Config::getTranslationCacheInstance();
$translation_cache->clear(); // Force cache cleaning to prevent usage of outdated cache data

Config::detectRootDoc();

if (!($DB instanceof DBmysql)) { // $DB can have already been init in install.php script
   $DB = new DB();
}
$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

Config::loadLegacyConfiguration();

$update = new Update($DB);
$update->initSession();

if (isset($_POST['update_end'])) {
   if (isset($_POST['send_stats'])) {
      Telemetry::enable();
   }
   header('Location: ../index.php');
}

/* ----------------------------------------------------------------- */

/*---------------------------------------------------------------------*/
/**
 * To be conserved to migrations before 0.80
 * since 0.80, migration is a new class
**/
function displayMigrationMessage ($id, $msg = "") {
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

   if ($result = $DB->query($query)) {
      if ($DB->numrows($result) > 0) {
         return $DB->result($result, 0, "ID");
      }
   }
   $query = "INSERT INTO `".$table."`
             (`name`)
             VALUES ('".addslashes($name)."')";
   if ($result = $DB->query($query)) {
      return $DB->insertId();
   }
   return 0;
}


//test la connection a la base de donn???.
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

   if (!$DB->fieldExists($table2, "ID", false)) {
      $query = " ALTER TABLE `$table2`
                 ADD `ID` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
      $DB->queryOrDie($query);
   }

   $query = "ALTER TABLE `$table1`
             ADD `temp` INT";
   $DB->queryOrDie($query);

   $iterator = $DB->request([
      'SELECT' => [
         "$table1.ID AS row1",
         "$table2.ID AS row2",
      ],
      'FROM'   => [$table1, $table2],
      'WHERE'  => [
         "$table2.name" => new \QueryExpression(DBmysql::quoteName("$table1.$chps"))
      ]
   ]);

   while ($line = $iterator->next()) {
      $DB->updateOrDie(
         $table1,
         ['temp' => $line['row2']],
         ['ID' => $line['row1']]
      );
   }
   $DB->freeResult($result);

   $query = "ALTER TABLE `$table1`
             DROP `$chps`";
   $DB->queryOrDie($query);

   $query = "ALTER TABLE `$table1`
             CHANGE `temp` `$chps` INT";
   $DB->queryOrDie($query);
}



//update database
function doUpdateDb() {
   global $GLPI_CACHE, $migration, $update;

   $currents            = $update->getCurrents();
   $current_version     = $currents['version'];
   $current_db_version  = $currents['dbversion'];
   $glpilanguage        = $currents['language'];

   $migration = new Migration(GLPI_SCHEMA_VERSION);
   $update->setMigration($migration);

   if (defined('GLPI_PREVER')) {
      if ($current_db_version != GLPI_SCHEMA_VERSION && !isset($_POST['agree_dev'])) {
         return;
      }
   }

   $update->doUpdates($current_version);
   $GLPI_CACHE->clear();
}


function updateTreeDropdown() {
   global $DB;

   // Update Tree dropdown
   if ($DB->tableExists("glpi_dropdown_locations")
       && !$DB->fieldExists("glpi_dropdown_locations", "completename", false)) {
      $query = "ALTER TABLE `glpi_dropdown_locations`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in dropdown_locations");
   }

   if ($DB->tableExists("glpi_dropdown_kbcategories")
       && !$DB->fieldExists("glpi_dropdown_kbcategories", "completename", false)) {
      $query = "ALTER TABLE `glpi_dropdown_kbcategories`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in dropdown_kbcategories");
   }

   if ($DB->tableExists("glpi_locations") && !$DB->fieldExists("glpi_locations", "completename", false)) {
      $query = "ALTER TABLE `glpi_locations`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in glpi_locations");
   }

   if ($DB->tableExists("glpi_knowbaseitemcategories")
       && !$DB->fieldExists("glpi_knowbaseitemcategories", "completename", false)) {
      $query = "ALTER TABLE `glpi_knowbaseitemcategories`
                ADD `completename` TEXT NOT NULL ";
      $DB->queryOrDie($query, "0.6 add completename in glpi_knowbaseitemcategories");
   }
}

/**
 * Display security key check form.
 *
 * @return void
 */
function showSecurityKeyCheckForm() {
   global $CFG_GLPI, $update;

   echo '<form action="update.php" method="post">';
   echo '<input type="hidden" name="continuer" value="1" />';
   echo '<input type="hidden" name="missing_key_warning_shown" value="1" />';
   echo '<div class="center">';
   echo '<h3>' . __('Missing security key file') . '</h3>';
   echo '<p>';
   echo '<img src="' . $CFG_GLPI['root_doc'] . '/pics/ko_min.png" />';
   echo sprintf(
      __('The key file "%s" used to encrypt/decrypt sensitive data is missing. You should retrieve it from your previous installation or encrypted data will be unreadable.'),
      $update->getExpectedSecurityKeyFilePath()
   );
   echo '</p>';
   echo '<input type="submit" name="ignore" class="submit" value="' . __('Ignore warning') . '" />';
   echo '&nbsp;&nbsp;';
   echo '<input type="submit" name="retry" class="submit" value="' . __('Try again') . '" />';
   echo '</form>';
}

//Debut du script
$HEADER_LOADED = true;

Session::start();

Session::loadLanguage('', false);

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta http-equiv='Content-Script-Type' content='text/javascript'>";
echo "<meta http-equiv='Content-Style-Type' content='text/css'>";
echo "<title>Setup GLPI</title>";
//JS
echo Html::script("public/lib/base.js");
// CSS
echo Html::css('public/lib/base.css');
echo Html::css('css/style_install.css');
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
      if ($update->isExpectedSecurityKeyFileMissing()
          && (!isset($_POST['missing_key_warning_shown']) || !isset($_POST['ignore']))) {
         // Display missing security key file form if key file is missing
         // unless it has already been displayed and user clicks on "ignore" button.
         showSecurityKeyCheckForm();
      } else if (!isset($_POST["update_location"])) {
         $current_version = "0.31";
         $config_table    = "glpi_config";

         if ($DB->tableExists("glpi_configs")) {
            $config_table = "glpi_configs";
         }

         if ($DB->tableExists($config_table)) {
            $current_version = Config::getCurrentDBVersion();
         }
         echo "<div class='center'>";
         doUpdateDb();

         echo "<form action='".$CFG_GLPI["root_doc"]."/install/update.php' method='post'>";
         echo "<input type='hidden' name='update_end' value='1'/>";

         echo "<hr />";
         echo "<h2>".__('One last thing before starting')."</h2>";
         echo "<p>";
         echo GLPINetwork::showInstallMessage();
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
      }

   } else {
      echo "<h3>";
      echo __("Connection to database failed, verify the connection parameters included in config_db.php file")."</h3>";
   }

}

echo "</div></div></body></html>";
