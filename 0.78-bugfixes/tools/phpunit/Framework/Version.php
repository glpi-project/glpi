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

class Framework_Version extends PHPUnit_Framework_TestCase {

   public function testVersion() {
      global $CFG_GLPI;

      $this->assertEquals('0.78', GLPI_VERSION, "Bad version in source page");
      $this->assertEquals(GLPI_VERSION, $CFG_GLPI['version'], "Bad version in config");
   }

   public function testLogin() {

      $auth = new Auth();
      $res = $auth->Login('stupid_login_which_doesnt_exists', 'stupid_password');
      $this->assertFalse($res, "Bad login accepted");

      $res = $auth->Login('glpi', 'glpi');
      $this->assertTrue($res, "Good login refused");
   }
}
?>
