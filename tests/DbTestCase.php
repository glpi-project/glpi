<?php
/*
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2015 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Generic test classe, to be extended for CommonDBTM Object

class DbTestCase extends PHPUnit_Framework_TestCase {

   protected function setUp() {
      global $DB;

      // Need Innodb -- $DB->begin_transaction() -- workaround:
      $DB->objcreated = array();
   }


   protected function tearDown() {
      global $DB;

      // Need Innodb -- $DB->rollback()  -- workaround:
      foreach ($DB->objcreated as $table => $ids) {
         foreach ($ids as $id) {
            $DB->query($q="DELETE FROM `$table` WHERE `id`=$id");
         }
      }
      unset($DB->objcreated);
   }
}
