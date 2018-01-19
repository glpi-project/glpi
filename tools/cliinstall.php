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

use DI\ContainerBuilder;

function displayUsage() {
   die("\nusage: ".$_SERVER['argv'][0]." [ --host=<dbhost> ] --db=<dbname> --user=<dbuser> [ --pass=<dbpassword> ] [ --lang=xx_XX] [ --tests ] [ --force ]\n\n");
}

$args = [ 'host' => 'localhost', 'pass' => ''];

if ($_SERVER['argc']>1) {
   for ($i=1; $i<count($_SERVER['argv']); $i++) {
      $it           = explode("=", $argv[$i], 2);
      $it[0]        = preg_replace('/^--/', '', $it[0]);
      $args[$it[0]] = (isset($it[1]) ? $it[1] : true);
   }
}

define('GLPI_ROOT', dirname(__DIR__));
chdir(GLPI_ROOT);

if (isset($args['tests'])) {
   define("GLPI_CONFIG_DIR", GLPI_ROOT . "/tests");
   @mkdir(GLPI_CONFIG_DIR . '/files/_log', 0775, true);
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");

$builder = new ContainerBuilder();
$builder->useAnnotations(true);
$builder->addDefinitions(__DIR__ . '/../inc/di_define.php');
global $container;
/*if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE || defined('TU_USER')) {
   $container = $builder->buildDevContainer();
} else {
   $container = $builder->build();
}*/
$container = $builder->build();
Config::detectRootDoc();

if (isset($args['help']) || !(isset($args['db']) && isset($args['user']))) {
   displayUsage();
}

if (isset($args['lang']) && !isset($CFG_GLPI['languages'][$args['lang']])) {
   $kl = implode(', ', array_keys($CFG_GLPI['languages']));
   echo "Unkown locale (use one of: $kl)\n";
   die(1);
}

if (file_exists(GLPI_CONFIG_DIR . '/config_db.php') && !isset($args['force'])) {
   echo "Already installed (see --force option)\n";
   die(1);
}

$_SESSION = ['glpilanguage' => (isset($args['lang']) ? $args['lang'] : 'en_GB')];
Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

echo "Connect to the DB...\n";

//Check if the port is in url
$hostport = explode(':', $args['host']);
if (count($hostport) < 2) {
   $link = new mysqli($hostport[0], $args['user'], $args['pass']);
} else {
   $link = new mysqli($hostport[0], $args['user'], $args['pass'], '', $hostport[1]);
}

if (!$link || mysqli_connect_error()) {
   echo "DB connection failed\n";
   die(1);
}

$args['db'] = $link->real_escape_string($args['db']);

echo "Create the DB...\n";
if (!$link->query("CREATE DATABASE IF NOT EXISTS `" . $args['db'] ."`")) {
   echo "Can't create the DB\n";
   die(1);
}

if (!$link->select_db($args['db'])) {
   echo "Can't select the DB\n";
   die(1);
}

echo "Save configuration file...\n";
if (!DBConnection::createMainConfig($args['host'], $args['user'], $args['pass'], $args['db'])) {
   echo "Can't write configuration file\n";
   die(1);
}

echo "Load default schema...\n";
Toolbox::createSchema($_SESSION['glpilanguage']);

echo "Done\n";
