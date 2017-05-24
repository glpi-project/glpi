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

// Generic test classe, to be extended for CommonDBTM Object

class DbTestCase extends PHPUnit\Framework\TestCase {
   private $int;
   private $str;

   protected function setUp() {
      global $DB;

      // Need Innodb -- $DB->begin_transaction() -- workaround:
      $DB->objcreated = array();

      // By default, no session, not connected
      $_SESSION = [
         'glpi_use_mode'         => Session::NORMAL_MODE,
         'glpi_currenttime'      => date("Y-m-d H:i:s"),
         'glpiis_ids_visible'    => 0,
         'glpiticket_timeline'   => 1
      ];
   }


   protected function tearDown() {
      global $DB;

      // Cleanup log directory
      foreach (glob(GLPI_LOG_DIR . '/*.log') as $file) {
         unlink($file);
      }

      // Need Innodb -- $DB->rollback()  -- workaround:
      foreach ($DB->objcreated as $table => $ids) {
         foreach ($ids as $id) {
            $DB->query($q = "DELETE FROM `$table` WHERE `id`=$id");
         }
      }
      unset($DB->objcreated);
   }


   /**
    * Connect using the test user
    */
   protected function login() {

      $auth = new Auth();
      if (!$auth->login(TU_USER, TU_PASS, true)) {
         $this->markTestSkipped('No login');
      }
   }

   /**
    * Get a unique random string
    */
   protected function getUniqueString() {
      if (is_null($this->str)) {
         return $this->str = uniqid('str');
      }
      return $this->str .= 'x';
   }

   /**
    * Get a unique random integer
    */
   protected function getUniqueInteger() {
      if (is_null($this->int)) {
         return $this->int = mt_rand(1000, 10000);
      }
      return $this->int++;
   }

   /**
    * change current entity
    */
   protected function setEntity($entityname, $subtree) {

      $this->assertTrue(Session::changeActiveEntities(getItemByTypeName('Entity', $entityname, true), $subtree));
   }
}
