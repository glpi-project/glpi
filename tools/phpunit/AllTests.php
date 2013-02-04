<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2013 by the INDEPNET Development Team.

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

require_once 'System/AllTests.php';
require_once 'Install/AllTests.php';
require_once 'Framework/AllTests.php';

class AllTests {
   public static function suite() {
      $suite = new PHPUnit_Framework_TestSuite('GLPI');
      $suite->addTest(System_AllTests::suite());
      $suite->addTest(Install_AllTests::suite());
      $suite->addTest(Framework_AllTests::suite());
      return $suite;
   }
}
?>
