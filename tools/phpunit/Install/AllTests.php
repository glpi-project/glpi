<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */
require_once 'PHPUnit/Framework.php';

// Hack for old PHPUnit
global $CFG_GLPI;

if (!defined('GLPI_ROOT')) {
   define('GLPI_ROOT', '../..');
   require GLPI_ROOT . "/inc/includes.php";
   restore_error_handler();

   error_reporting(E_ALL | E_STRICT);
   ini_set('display_errors','On');
}
require_once GLPI_ROOT . "/install/update_0723_078.php";
require_once GLPI_ROOT . "/install/update_078_0781.php";
require_once GLPI_ROOT . "/install/update_0781_0782.php";
require_once GLPI_ROOT . "/install/update_0782_080.php";

function displayMigrationMessage ($id, $msg="") {
   // display nothing
}

class Install extends PHPUnit_Framework_TestCase {

   public function testUpdate() {

// Old devicetype for compatibility
define("MOBOARD_DEVICE",1);
define("PROCESSOR_DEVICE",2);
define("RAM_DEVICE",3);
define("HDD_DEVICE",4);
define("NETWORK_DEVICE",5);
define("DRIVE_DEVICE",6);
define("CONTROL_DEVICE",7);
define("GFX_DEVICE",8);
define("SND_DEVICE",9);
define("PCI_DEVICE",10);
define("CASE_DEVICE",11);
define("POWER_DEVICE",12);

      // Install a fresh 0.72.3 DB
      $DB = new DB();
      $res = $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.72.3-empty.sql");
      $this->assertTrue($res, "Fail: SQL Error during install");

      // update default language
      $query = "UPDATE `glpi_configs` SET language='fr_FR' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set default language");
      $query = "UPDATE `glpi_users` SET language='fr_FR' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set users language");

      // Update to 0.78
      $res = update0723to078(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs` SET `version` = '0.78', language='fr_FR',founded_new_version='' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.78.1
      $res = update078to0781(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs` SET `version` = '0.78.1', language='fr_FR',founded_new_version='' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.78.2
      $res = update0781to0782(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs` SET `version` = '0.78.2', language='fr_FR',founded_new_version='' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.80
      $res = update0782to080(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs` SET `version` = '0.80', language='fr_FR',founded_new_version='' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set version");


   }

   public function testInstall() {

      // Install a fresh 0.80 DB
      $DB = new DB();
      $res = $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.80-empty.sql");
      $this->assertTrue($res, "Fail: SQL Error during install");

      // update default language
      $query = "UPDATE `glpi_configs` SET language='fr_FR' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set default language");
      $query = "UPDATE `glpi_users` SET language='fr_FR' ;";
      $this->assertTrue($DB->query($query), "Fail: can't set users language");
   }
}

class Install_AllTests  {

   public static function suite() {

      $suite = new PHPUnit_Framework_TestSuite('Install');

      return $suite;
   }
}
?>
