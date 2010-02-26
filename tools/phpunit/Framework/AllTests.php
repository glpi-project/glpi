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
include 'Version.php';
include 'Dropdown/AllTests.php';
include 'CommonDBTM/AllTests.php';

class Framework_GLPI extends PHPUnit_Framework_TestSuite {

   private $tables=array();

   public static function suite() {

      $suite = new Framework_GLPI('Framework_Version');
      $suite->addTest(Framework_CommonDBTM_AllTests::suite());
      $suite->addTest(Framework_Dropdown_AllTests::suite());

      return $suite;
   }

   protected function setUp() {
      global $DB;

      // Store Max(id) for each glpi tables
      $result = $DB->list_tables("glpi_%");
      while ($data=$DB->fetch_row($result)) {
         $query = "SELECT MAX(`id`) AS MAXID FROM ".$data[0];
         foreach ($DB->request($query) as $row) {
            $this->tables[$data[0]] = (empty($row['MAXID']) ? 0 : $row['MAXID']);
         }
      }
      $DB->free_result($result);

      $tab = array();

      $auth = new Auth();
      // First session
      $auth->Login('glpi', 'glpi') or die("Login glpi/glpi invalid !\n");

      // Create entity tree
      $entity = new Entity();
      $tab['entity'][0] = $entity->add(array('name'        => 'PHP Unit root'));


      if (!$tab['entity'][0]                                   // Crash detection
          || !FieldExists('glpi_profiles','notification')   // Schema detection
          || countElementsInTable('glpi_rules')!=2) {    // Old rules

         if (!$tab['entity'][0]) {
            echo "Couldn't run test (previous run not cleaned)\n";
         } else {
            echo "Schema need to be updated\n";
         }
         echo "Loading a fresh empty database:";
         $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.78-empty.sql");
         die(" done\nTry again\n");
      }

      $tab['entity'][1] = $entity->add(array('name'        => 'PHP Unit Child 1',
                                             'entities_id' => $tab['entity'][0]));

      $tab['entity'][2] = $entity->add(array('name'        => 'PHP Unit Child 2',
                                             'entities_id' => $tab['entity'][0]));

      $tab['entity'][3] = $entity->add(array('name'        => 'PHP Unit Child 2.1',
                                             'entities_id' => $tab['entity'][2]));

      $tab['entity'][4] = $entity->add(array('name'        => 'PHP Unit Child 2.2',
                                             'entities_id' => $tab['entity'][2]));

      // New session with all the entities
      $auth->Login('glpi', 'glpi') or die("Login glpi/glpi invalid !\n");

      // Shared this with all tests
      $this->sharedFixture = $tab;
   }

   protected function tearDown() {
      global $DB;

      $tot = 0;
      // Cleanup the object created by the suite
      foreach ($this->tables as $table => $maxid) {
         $query = "DELETE FROM $table WHERE id>".$maxid;
         $res = $DB->query($query);
         $tot += $DB->affected_rows();
      }
      echo "\nCleanup of $tot records\n";
   }
}

class Framework_AllTests  {

   public static function suite() {

      $suite = new PHPUnit_Framework_TestSuite('Framework');
      $suite->addTest(Framework_GLPI::suite());

      return $suite;
   }
}
?>
