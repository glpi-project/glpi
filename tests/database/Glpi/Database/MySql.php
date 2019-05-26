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

namespace tests\units\Glpi\Database;

class MySql extends \GLPITestCase {

   private $olddb;

   public function beforeTestMethod($method) {
      parent::beforeTestMethod($method);
      $this->olddb = \Glpi\DatabaseFactory::create();
      $this->olddb->connect();
      $this->boolean($this->olddb->isConnected())->isTrue();
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

      $expected = "UPDATE `glpi_tickets` SET `date_mod` = ?, `users_id` = ? WHERE `id` = ?";
      $set = [
         'date_mod'  => '2019-01-01 12:00:00',
         'users_id'  => 2
      ];
      $built = $DB->buildUpdate('glpi_tickets', $set, ['id' => 1]);
      $this->string($built)->isIdenticalTo($expected);
      $this->array($set)->isIdenticalTo(['2019-01-01 12:00:00', 2, 1]);

      $set = [
         'name' => '_join_computer1'
      ];
      $expected = "UPDATE `glpi_computers`";
      $expected .= " LEFT JOIN `glpi_locations` ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)";
      $expected .= " LEFT JOIN `glpi_computertypes` ON (`glpi_computers`.`computertypes_id` = `glpi_computertypes`.`id`)";
      $expected .= " SET `name` = ? WHERE `glpi_locations`.`name` = ? AND `glpi_computertypes`.`name` = ?";
      $built = $DB->buildUpdate('glpi_computers', $set, [
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
      $this->array($set)->isIdenticalTo([
         '_join_computer1',
         'test',
         'laptop'
      ]);
   }

   public function testBuildDelete() {
      global $DB;

      $expected = "DELETE `glpi_tickets` FROM `glpi_tickets` WHERE `id` = ?";
      $set = [];
      $built = $DB->buildDelete('glpi_tickets', $set, ['id' => 1]);
      $this->string($built)->isIdenticalTo($expected);
      $this->array($set)->isIdenticalTo([1]);

      $set = [];
      $expected = "DELETE `glpi_computers` FROM `glpi_computers`";
      $expected .= " LEFT JOIN `glpi_locations` ON (`glpi_computers`.`locations_id` = `glpi_locations`.`id`)";
      $expected .= " LEFT JOIN `glpi_computertypes` ON (`glpi_computers`.`computertypes_id` = `glpi_computertypes`.`id`)";
      $expected .= " WHERE `glpi_locations`.`name` = ? AND `glpi_computertypes`.`name` = ?";
      $built = $DB->buildDelete('glpi_computers', $set, [
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
      $this->array($set)->isIdenticalTo([
         'test',
         'laptop'
      ]);
   }

   public function testBuildInsertBulk() {
      global $DB;

      $expected = "INSERT INTO `glpi_configs` (`context`, `name`, `value`) VALUES ";
      $expected .= "(:context_0,:name_0,:value_0),(:context_1,:name_1,:value_1),(:context_2,:name_2,:value_2),(:context_3,:name_3,:value_3)";
      $built = $DB->buildInsertBulk('glpi_configs', ['context', 'name', 'value'], [
      ['core', 'cut', 250],
      ['core', 'list_limit', 15],
      ['core', 'list_limit_max', 50],
      ['core', 'url_maxlength', 30]]);

      $this->string($built)->isIdenticalTo($expected);
   }

   public function testTableSchemaCreate() {
      global $DB;

      $schema = new \DBTableSchema();
      $schema->init('glpi_test')
         ->addField('tickets_id', 'int', ['value' => '0'])
         ->addIndexedField('type', 'int', ['value' => '1'])
         ->addField('date_begin', 'timestamp')
         ->addField('date_answered', 'timestamp')
         ->addField('satisfaction', 'int')
         ->addField('comment', 'text')
         ->addUniqueKey('tickets_id', ['tickets_id']);
      $schema_params = $schema->createTemplate();
      $built = $DB->buildCreate('glpi_test', $schema_params['fields'], $schema_params['keys']);

      $expected = "CREATE TABLE IF NOT EXISTS glpi_test ";
      $expected .= "(`id` int(11) NOT NULL AUTO_INCREMENT, `tickets_id` INT(11) NOT NULL DEFAULT '0', ";
      $expected .= "`type` INT(11) NOT NULL DEFAULT '1', `date_begin` TIMESTAMP NULL DEFAULT NULL, ";
      $expected .= "`date_answered` TIMESTAMP NULL DEFAULT NULL, `satisfaction` INT(11) NOT NULL DEFAULT '0', ";
      $expected .= "`comment` TEXT COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`id`), KEY `type` (`type`), ";
      $expected .= "UNIQUE KEY `tickets_id` (`tickets_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";

      $this->string($built)->isIdenticalTo($expected);
   }
}
