<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

class DBmysql extends \GLPITestCase {

   private $olddb;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->olddb = new \DB();
      $this->olddb->dbdefault = 'glpitest0723';
      $this->olddb->connect();
      $this->boolean($this->olddb->connected)->isTrue();
   }

   public function afterTestMethod($method) {
      parent::afterTestMethod($method);
      $this->olddb->close();
   }

   /**
    * Test updated database against fresh install
    *
    * @return void
    */
   public function testUpdatedDatabase() {
      global $DB;

      $fresh_tables = $DB->listTables();
      while ($fresh_table = $fresh_tables->next()) {
         $table = $fresh_table['TABLE_NAME'];
         $this->boolean($this->olddb->tableExists($table, false))->isTrue("Table $table does not exists from migration!");

         $create = $DB->getTableSchema($table);
         $fresh = $create['schema'];
         $fresh_idx = $create['index'];

         $update = $this->olddb->getTableSchema($table);
         $updated = $update['schema'];
         $updated_idx = $update['index'];

         //compare table schema
         $this->string($updated)->isIdenticalTo($fresh);
         //check index
         $fresh_diff = array_diff($fresh_idx, $updated_idx);
         $this->array($fresh_diff)->isEmpty("Index missing in update for $table: " . implode(', ', $fresh_diff));
         $update_diff = array_diff($updated_idx, $fresh_idx);
         $this->array($update_diff)->isEmpty("Index missing in empty for $table: " . implode(', ', $update_diff));
      }
   }

   public function testBuildUpdate() {
      global $DB;

      $expected = "UPDATE `glpi_tickets` SET `date_mod` = '2019-01-01 12:00:00', `users_id` = '2' WHERE `id` = '1'";
      $built = $DB->buildUpdate('glpi_tickets', [
         'date_mod'  => '2019-01-01 12:00:00',
         'users_id'  => 2
      ], ['id' => 1]);
      $this->string($built)->isIdenticalTo($expected);

      $expected = "UPDATE `glpi_computers`";
      $expected .= " LEFT JOIN `glpi_locations` ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)";
      $expected .= " LEFT JOIN `glpi_computertypes` ON (`glpi_computers`.`computertypes_id` = `glpi_computertypes`.`id`)";
      $expected .= " SET `name` = '_join_computer1' WHERE `glpi_locations`.`name` = 'test' AND `glpi_computertypes`.`name` = 'laptop'";
      $built = $DB->buildUpdate('glpi_computers', ['name' => '_join_computer1'], [
         'glpi_locations.name' => 'test',
         'glpi_computertypes.name' => 'laptop'
      ], [
         'LEFT JOIN' => [
            'glpi_locations' => [
               'ON' => [
                  'glpi_computers'    => 'locations_id',
                  'glpi_locations'  => 'id'
               ]
            ], 'glpi_computertypes' => [
               'ON' => [
                  'glpi_computers'    => 'computertypes_id',
                  'glpi_computertypes'  => 'id'
               ]
            ]
         ]
      ]);
      $this->string($built)->isIdenticalTo($expected);
   }

   public function testBuildDelete() {
      global $DB;

      $expected = "DELETE FROM `glpi_tickets` WHERE `id` = '1'";
      $built = $DB->buildDelete('glpi_tickets', ['id' => 1]);
      $this->string($built)->isIdenticalTo($expected);

      $expected = "DELETE FROM `glpi_computers`";
      $expected .= " LEFT JOIN `glpi_locations` ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)";
      $expected .= " LEFT JOIN `glpi_computertypes` ON (`glpi_computers`.`computertypes_id` = `glpi_computertypes`.`id`)";
      $expected .= " WHERE `glpi_locations`.`name` = 'test' AND `glpi_computertypes`.`name` = 'laptop'";
      $built = $DB->buildDelete('glpi_computers', [
         'glpi_locations.name' => 'test',
         'glpi_computertypes.name' => 'laptop'
      ], [
         'LEFT JOIN' => [
            'glpi_locations' => [
               'ON' => [
                  'glpi_computers'    => 'locations_id',
                  'glpi_locations'  => 'id'
               ]
            ], 'glpi_computertypes' => [
               'ON' => [
                  'glpi_computers'    => 'computertypes_id',
                  'glpi_computertypes'  => 'id'
               ]
            ]
         ]
      ]);
      $this->string($built)->isIdenticalTo($expected);
   }
}
