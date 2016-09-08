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

class Framework_Version extends PHPUnit_Framework_TestCase {

   public function testVersion() {
      global $CFG_GLPI;

      $this->assertEquals('0.84', GLPI_VERSION, "Bad version in source page");
      $this->assertEquals(GLPI_VERSION, $CFG_GLPI["version"], "Bad version in config");
   }

//   public function testLogin() {
//      global $DB;
//      
//      $DB->connect();
//      
//      $auth = new Auth();
//      $res = $auth->Login('stupid_login_which_doesnt_exists', 'stupid_password');
//      $this->assertFalse($res, "Bad login accepted");
//
//      $res = $auth->Login('glpi', 'glpi');
//      $this->assertTrue($res, "Good login refused");
//   }
}
?>
