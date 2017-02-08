<?php
/*
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

// Generic test classe, to be extended for CommonDBTM Object

class DbTestCase extends PHPUnit\Framework\TestCase {

   protected function setUp() {
      global $DB;

      // Need Innodb -- $DB->begin_transaction() -- workaround:
      $DB->objcreated = array();
   }


   protected function tearDown() {
      global $DB;

      // Cleanup log directory
      foreach(glob(GLPI_LOG_DIR . '/*.log') as $file) {
         unlink($file);
      }

      // Need Innodb -- $DB->rollback()  -- workaround:
      foreach ($DB->objcreated as $table => $ids) {
         foreach ($ids as $id) {
            $DB->query($q="DELETE FROM `$table` WHERE `id`=$id");
         }
      }
      unset($DB->objcreated);
   }


   /**
    * Connect using the test user
    */
   protected function login() {

      $auth = new Auth();
      if (!$auth->Login(TU_USER, TU_PASS, true)) {
         $this->markTestSkipped('No login');
      }
   }


   /**
    * change current entity
    */
   protected function setEntity($entityname, $subtree) {

      $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', $entityname,  true), $subtree));
   }
}
