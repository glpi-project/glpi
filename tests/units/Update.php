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

namespace tests\units;

use \atoum;

/* Test for inc/update.class.php */

class Update extends atoum {

   public function testConstructor() {
      global $DB;

      $oldtypes = [
         'GENERAL_TYPE',
         'COMPUTER_TYPE',
         'BUDGET_TYPE',
         'MOBOARD_DEVICE',
         'POWER_DEVICE'
      ];

      foreach ($oldtypes as $oldtype) {
         $this->boolean(defined($oldtype))->isFalse();
      }

      $update = new \Update($DB);

      //old types are now defined
      foreach ($oldtypes as $oldtype) {
         $this->boolean(defined($oldtype))->isTrue();
      }
   }

   public function testCurrents() {
      global $DB;
      $update = new \Update($DB);

      $expected = [
         'dbversion' => GLPI_SCHEMA_VERSION,
         'language'  => 'en_GB',
         'version'   => GLPI_VERSION
      ];
      $this->array($update->getCurrents())->isIdenticalTo($expected);
   }

   public function testInitSession() {
      global $DB;

      $update = new \Update($DB);
      session_destroy();
      $this->variable(session_status())->isIdenticalTo(PHP_SESSION_NONE);

      $update->initSession();
      $this->variable(session_status())->isIdenticalTo(PHP_SESSION_ACTIVE);

      $this->array($_SESSION)->hasKeys([
         'glpilanguage',
         'glpi_currenttime',
         'glpi_use_mode'
      ])->notHasKeys([
         'debug_sql',
         'debug_vars',
         'use_log_in_files'
      ]);
      $this->variable($_SESSION['glpi_use_mode'])->isIdenticalTo(\Session::DEBUG_MODE);
      $this->variable(error_reporting())->isIdenticalTo(E_ALL | E_STRICT);
   }

   public function testSetMigration() {
      global $DB;
      $update = new \Update($DB);
      $migration = null;
      $this->output(
         function () use (&$migration) {
            $migration = new \Migration(GLPI_VERSION);
         }
      )->isIdenticalTo("<div id='migration_message_" . GLPI_VERSION . "'>\n            <p class='center'>Work in progress...</p></div>");

      $this->object($update->setMigration($migration))->isInstanceOf('Update');
   }
}
