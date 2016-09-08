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
include("../../install/update_0723_078.php");
include("../../install/update_078_0781.php");
include("../../install/update_0781_0782.php");
include("../../install/update_0782_080.php");
include("../../install/update_080_0801.php");
include("../../install/update_0801_0803.php");
include("../../install/update_0803_083.php");
include("../../install/update_083_0831.php");
include("../../install/update_0831_0833.php");
include("../../install/update_0831_084.php");
include("../../install/update_084_085.php");

function displayMigrationMessage ($id, $msg="") {
   // display nothing
}


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


   function displayWarning($msg, $red=false) {

      if ($red) {
         $msg = "** $msg";
      }
      echo str_pad($msg, 100)."\n";
   }
}
$migration = new CliMigration("0.72.3");

class Install extends PHPUnit_Framework_TestCase {

   public function testUpdate() {
      global $DB;

      $DB->connect();

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
      $res = $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.72.3-empty.sql");
      $this->assertTrue($res, "Fail: SQL Error during install");

      // update default language
      $query = "UPDATE `glpi_config`
                SET `language` = 'fr_FR'";
      $this->assertTrue($DB->query($query), "Fail: can't set default language");
      $query = "UPDATE `glpi_users`
                SET `language` = 'fr_FR'";
      $this->assertTrue($DB->query($query), "Fail: can't set users language");

      // Update to 0.78
      $res = update0723to078();
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.78',
                    `language` = 'fr_FR',
                    `founded_new_version`= ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.78.1
      $res = update078to0781();
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.78.1',
                    `language` = 'fr_FR',
                    `founded_new_version`= ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.78.2
      $res = update0781to0782(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.78.2',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.80
      $res = update0782to080(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.80',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.80.1
      $res = update080to0801(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.80.1',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.80.3
      $res = update0801to0803(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.80.3',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.83
      $res = update0803to083(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.83',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.83.1
      $res = update083to0831(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.83.1',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.84
      $res = update0831to084(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `version` = '0.84',
                    `language` = 'fr_FR',
                    `founded_new_version` = ''";
      $this->assertTrue($DB->query($query), "Fail: can't set version");

      // Update to 0.85
      $res = update084to085(false);
      $this->assertTrue($res, "Fail: SQL Error during upgrade");

      $query = "UPDATE `glpi_configs`
                SET `value` = '0.85'
                WHERE `context`='context'
                      AND `name`='version'";
      $this->assertTrue($DB->query($query), "Fail: can't set version");
   }


   public function testInstall() {
      global $DB;

      $DB->connect();

      // Install a fresh 0.85 DB
      $DB  = new DB();
      $res = $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.90-empty.sql");
      $this->assertTrue($res, "Fail: SQL Error during install");

      // update default language
      $query = "UPDATE `glpi_configs`
                SET `value` = 'fr_FR'
                WHERE `context`='context'
                      AND `name`='language'";
      $this->assertTrue($DB->query($query), "Fail: can't set default language");
      $query = "UPDATE `glpi_users`
                SET `language` = 'fr_FR'";
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
