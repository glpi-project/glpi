<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

use SebastianBergmann\Diff\Differ;

function displayUsage() {
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

if (isset($args['config-dir'])) {
   define("GLPI_CONFIG_DIR", $args['config-dir']);
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");
Config::detectRootDoc();

$GLPI = new GLPI();
$GLPI->initLogger();

$DB = new DB();
$DB->disableTableCaching(); //prevents issues on fieldExists upgrading from old versions

$update = new Update($DB, $args);
$update->initSession();

Session::loadLanguage();
if (!$DB->connected) {
   die("No DB connection\n");
}

//initialize entities
$_SESSION["glpidefault_entity"] = 0;
Session::initEntityProfiles(2);
Session::changeProfile(4);

$differ = new Differ;

$empty = file_get_contents(__DIR__ . '/../install/mysql/glpi-empty.sql');
preg_match_all(
   "/CREATE TABLE `(.+)`[^;]+/",
   $empty,
   $etables
);
$tables = $etables[0];
foreach ($tables as $i => $schema) {
   //echo "Processing $table...\n";
   $table = $etables[1][$i];
   $creation = $DB->getTableSchema($table, $schema);
   $existing = $DB->getTableSchema($table);

   if ($existing['schema'] != $creation['schema']) {
      echo "Table schema differs for table $table\n";
      print $differ->diff($creation['schema'], $existing['schema']);
   }
}
