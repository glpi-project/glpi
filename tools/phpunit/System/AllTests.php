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

class System extends PHPUnit_Framework_TestCase {

   public function testPHP() {

      // From Toolbox::commonCheckForUseGLPI
      $this->assertEquals('5', substr(phpversion(),0,1), "Bad PHP Version ".phpversion());

      // Use assertTrue(!init...) because some return false, others return '0'
      $this->assertTrue(!ini_get('zend.ze1_compatibility_mode'), "Fail: zend.ze1_compatibility_mode=On");
      $this->assertTrue(!ini_get('session.auto_start'), "Fail: session.auto_start=On");
      $this->assertTrue(!ini_get('magic_quotes_sybase'), "Fail: magic_quotes_sybase=On");

      $this->assertTrue(function_exists('mysql_query'), "Fail: no mysql extension");
      $this->assertTrue(extension_loaded('session'), "Fail: no session extension");
      $this->assertTrue(function_exists('json_encode'), "Fail: no json extension");
      $this->assertTrue(extension_loaded('mbstring'), "Fail: no mbstring extension");

      // TODO : memory limit
   }


   public function testDir() {

      // From Toolbox::commonCheckForUseGLPI
      $this->assertTrue(error_log("PHPUnit\n", 3, GLPI_LOG_DIR."/php-errors.log"),
                        "Fail: no write access to ".GLPI_LOG_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_DUMP_DIR),
                          "Fail: no write access to ".GLPI_DUMP_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_DOC_DIR),
                          "Fail: no write access to ".GLPI_DOC_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_CONFIG_DIR),
                          "Fail: no write access to ".GLPI_CONFIG_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_SESSION_DIR),
                          "Fail: no write access to ".GLPI_SESSION_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_CRON_DIR),
                          "Fail: no write access to ".GLPI_CRON_DIR);
      $this->assertEquals(0, Toolbox::testWriteAccessToDirectory(GLPI_CACHE_DIR),
                          "Fail: no write access to ".GLPI_CACHE_DIR);
   }
}

class System_AllTests  {

   public static function suite() {

      $suite = new PHPUnit_Framework_TestSuite('System');
      return $suite;
   }
}
?>
