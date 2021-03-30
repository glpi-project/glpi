<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

/* Test for inc/update.class.php */

class Update extends \GLPITestCase {

   public function testCurrents() {
      global $DB;
      $update = new \Update($DB);

      $expected = [
         'dbversion' => GLPI_SCHEMA_VERSION,
         'language'  => 'en_GB',
         'version'   => GLPI_VERSION
      ];
      $this->array($update->getCurrents())->isEqualTo($expected);
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
      )->isEmpty();

      $this->object($update->setMigration($migration))->isInstanceOf('Update');
   }


   public function migrationsProvider() {
      $path = realpath(GLPI_ROOT . '/install/migrations');
      return [
         [
            // Validates version normalization (9.1 -> 9.1.0).
            'current_version'     => '9.1',
            'force_latest'        => false,
            'expected_migrations' => [
               $path . '/update_9.1.0_to_9.1.1.php'  => 'update910to911',
               $path . '/update_9.1.1_to_9.1.3.php'  => 'update911to913',
               $path . '/update_9.1.x_to_9.2.0.php'  => 'update91xto920',
               $path . '/update_9.2.0_to_9.2.1.php'  => 'update920to921',
               $path . '/update_9.2.1_to_9.2.2.php'  => 'update921to922',
               $path . '/update_9.2.2_to_9.2.3.php'  => 'update922to923',
               $path . '/update_9.2.x_to_9.3.0.php'  => 'update92xto930',
               $path . '/update_9.3.0_to_9.3.1.php'  => 'update930to931',
               $path . '/update_9.3.1_to_9.3.2.php'  => 'update931to932',
               $path . '/update_9.3.x_to_9.4.0.php'  => 'update93xto940',
               $path . '/update_9.4.0_to_9.4.1.php'  => 'update940to941',
               $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
               $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
               $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
               $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
               $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
               $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
               $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
               $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
               $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ],
         [
            // Validate version normalization (9.4.1.1 -> 9.4.1).
            'current_version'     => '9.4.1.1',
            'force_latest'        => false,
            'expected_migrations' => [
               $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
               $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
               $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
               $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
               $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
               $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
               $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
               $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
               $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ],
         [
            // Validate 9.2.2 specific case.
            'current_version'     => '9.2.2',
            'force_latest'        => false,
            'expected_migrations' => [
               $path . '/update_9.2.1_to_9.2.2.php'  => 'update921to922',
               $path . '/update_9.2.2_to_9.2.3.php'  => 'update922to923',
               $path . '/update_9.2.x_to_9.3.0.php'  => 'update92xto930',
               $path . '/update_9.3.0_to_9.3.1.php'  => 'update930to931',
               $path . '/update_9.3.1_to_9.3.2.php'  => 'update931to932',
               $path . '/update_9.3.x_to_9.4.0.php'  => 'update93xto940',
               $path . '/update_9.4.0_to_9.4.1.php'  => 'update940to941',
               $path . '/update_9.4.1_to_9.4.2.php'  => 'update941to942',
               $path . '/update_9.4.2_to_9.4.3.php'  => 'update942to943',
               $path . '/update_9.4.3_to_9.4.5.php'  => 'update943to945',
               $path . '/update_9.4.5_to_9.4.6.php'  => 'update945to946',
               $path . '/update_9.4.6_to_9.4.7.php'  => 'update946to947',
               $path . '/update_9.4.x_to_9.5.0.php'  => 'update94xto950',
               $path . '/update_9.5.1_to_9.5.2.php'  => 'update951to952',
               $path . '/update_9.5.2_to_9.5.3.php'  => 'update952to953',
               $path . '/update_9.5.3_to_9.5.4.php'  => 'update953to954',
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ],
         [
            // Dev versions always triggger latest migration
            'current_version'     => '10.0.0-dev',
            'force_latest'        => false,
            'expected_migrations' => [
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ],
         [
            // Force latests does not duplicate latest in list
            'current_version'     => '10.0.0-dev',
            'force_latest'        => true,
            'expected_migrations' => [
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ],
         [
            // Validate that list is empty when version matches
            'current_version'     => '10.0.0',
            'force_latest'        => false,
            'expected_migrations' => [
            ],
         ],
         [
            // Validate force latest
            'current_version'     => '10.0.0',
            'force_latest'        => true,
            'expected_migrations' => [
               $path . '/update_9.5.x_to_10.0.0.php' => 'update95xto1000',
            ],
         ]
      ];
   }

   /**
    * @dataProvider migrationsProvider
    */
   public function testGetMigrationsToDo(string $current_version, bool $force_latest, array $expected_migrations) {
      global $DB;
      $update = new \Update($DB);
      $this->array($update->getMigrationsToDo($current_version, $force_latest))->isIdenticalTo($expected_migrations);
   }
}
