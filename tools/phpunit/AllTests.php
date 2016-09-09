<?php
/*
 * @version $Id$
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
if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', realpath('../..'));
}

include_once (GLPI_ROOT . "/inc/autoload.function.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");
Config::detectRootDoc();
include_once (GLPI_ROOT . "/config/config.php");
if (is_writable(GLPI_SESSION_DIR)) {
   Session::setPath();
} else {
   die("Can't write in ".GLPI_SESSION_DIR."\n");
}
Session::start();

// Init debug variable
Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);
$_SESSION['glpilanguage']  = 'en_GB';

Session::loadLanguage();

$DB = new DB();
if (!$DB->connected) {
   die("No DB connection\n");
}

require_once 'System/AllTests.php';
require_once 'Install/AllTests.php';
require_once 'Framework/AllTests.php';

class AllTests {
   public static function suite() {
      $suite = new PHPUnit_Framework_TestSuite('GLPI');
      $suite->addTest(System_AllTests::suite());
      $suite->addTest(Install_AllTests::suite());
//      $suite->addTest(Framework_AllTests::suite());
      return $suite;
   }
}
?>
