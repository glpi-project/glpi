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

include_once ('Version.php');
include_once ('CommonDBTM/AllTests.php');
include_once ('Dropdown/AllTests.php');

class Framework_GLPI extends PHPUnit_Framework_TestSuite {

   public $tables = array();
   public $sharedFixture = array();

   public static function suite() {
      $suite = new Framework_GLPI('Framework_Version');
      $suite->addTest(Framework_CommonDBTM_AllTests::suite());
//      $suite->addTest(Framework_Dropdown_AllTests::suite());

      return $suite;
   }


   protected function setUp() {
      global $DB;
      
      $DB->connect();

      // Store Max(id) for each glpi tables
      $result = $DB->list_tables();
      while ($data=$DB->fetch_row($result)) {
         $query = "SELECT MAX(`id`) AS MAXID
                   FROM `".$data[0]."`";
         foreach ($DB->request($query) as $row) {
            $this->tables[$data[0]] = (empty($row['MAXID']) ? 0 : $row['MAXID']);
         }
      }
      $DB->free_result($result);

      $tab  = array();
      $auth = new Auth();
      // First session
      $auth->Login('glpi', 'glpi') ;

      // Create entity tree
      $entity = new Entity();
      $tab['entity'][0] = $entity->add(array('name' => 'PHP Unit root',
                                             'entities_id' => 0));

      if (!$tab['entity'][0]                                   // Crash detection
          || !FieldExists('glpi_profiles','notification')   // Schema detection
          || countElementsInTable('glpi_rules')!=6) {    // Old rules

         if (!$tab['entity'][0]) {
            echo "Couldn't run test (previous run not cleaned)\n";
         } else {
            echo "Schema need to be updated\n";
         }
         echo "Loading a fresh empty database:";
         $DB->runFile(GLPI_ROOT ."/install/mysql/glpi-0.84-empty.sql");
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
//
//
//   protected function tearDown() {
//      global $DB;
//      
//      $DB->connect();
//
//      $tot = 0;
//      // Cleanup the object created by the suite
//      foreach ($this->tables as $table => $maxid) {
//         $query = "DELETE
//                   FROM `$table`
//                   WHERE `id` > ".$maxid;
//         $res = $DB->query($query);
//         $tot += $DB->affected_rows();
//      }
//      echo "\nCleanup of $tot records\n";
//   }
}



class Framework_AllTests  {

   public static function suite() {

      $suite = new PHPUnit_Framework_TestSuite('Framework');
      $suite->addTest(Framework_GLPI::suite());

      return $suite;
   }
}
?>
