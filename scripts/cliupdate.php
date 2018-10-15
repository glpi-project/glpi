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

if (in_array('--help', $_SERVER['argv'])) {
   die("usage: ".$_SERVER['argv'][0]."  [ --force ] [ --lang=xx_XX ] [ --config-dir=/path/relative/to/script ] [--dev]\n");
}

chdir(__DIR__);

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('..'));
}

$args = [];
if ($_SERVER['argc']>1) {
   for ($i=1; $i<count($_SERVER['argv']); $i++) {
      $it           = explode("=", $argv[$i], 2);
      $it[0]        = preg_replace('/^--/', '', $it[0]);
      $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

if (isset($args['tests'])) {
   define('TU_USER', 'CLI');
}

if (isset($args['config-dir'])) {
   define("GLPI_CONFIG_DIR", $args['config-dir']);
}

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

$GLPI = new GLPI();
$GLPI->initLogger();

Config::detectRootDoc();

$DB = new DB();
$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

$update = new Update($DB, $args);
$update->initSession();

Session::loadLanguage();
if (!$DB->connected) {
   echo "No DB connection\n";
   die(1);
}

$checkdb = Config::displayCheckDbEngine(true);
if ($checkdb > 0) {
   return;
}


//initialize entities
$_SESSION["glpidefault_entity"] = 0;
Session::initEntityProfiles(2);
Session::changeProfile(4);

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

   function displayWarning($msg, $red = false) {

      if ($red) {
         $msg = "** $msg";
      }
      echo str_pad($msg, 100)."\n";
   }
}

/*---------------------------------------------------------------------*/

$currents            = $update->getCurrents();
$current_version     = $currents['version'];
$current_db_version  = $currents['dbversion'];
$glpilanguage        = $currents['language'];

$migration = new CliMigration(GLPI_SCHEMA_VERSION);
$update->setMigration($migration);

$migration->displayWarning("Current GLPI version         : " . $current_version);
$migration->displayWarning("New GLPI version             : " . GLPI_VERSION);
$migration->displayWarning("Current GLPI database version: " . $current_db_version);
$migration->displayWarning("New GLPI database version    : " . GLPI_SCHEMA_VERSION);
$migration->displayWarning("Default GLPI Language        : $glpilanguage");

if (defined('GLPI_PREVER')) {
   $migration->displayWarning("Development version          : Yes");
   if ($current_db_version != GLPI_SCHEMA_VERSION && !isset($args['dev'])) {
      echo GLPI_SCHEMA_VERSION . " is not a stable release. Please upgrade manually - or add --dev.\n";
      die(1);
   }
}

if (substr($current_version, -4) === '-dev') {
   $current_version = str_replace('-dev', '', $current_version);
}
$update->doUpdates($current_version);

if (version_compare($current_db_version, GLPI_SCHEMA_VERSION, 'ne')) {
   $migration->displayWarning("\nMigration Done.");
} else if (isset($args['force']) || $current_db_version != GLPI_SCHEMA_VERSION && isset($args['dev'])) {

   include_once("../install/update_93_94.php");
   update93to94();

   $migration->displayWarning((isset($args['force']) ? "\nForced" : "\nDevelopment") . " migration Done.");
} else {
   $migration->displayWarning("No migration needed.");
}
