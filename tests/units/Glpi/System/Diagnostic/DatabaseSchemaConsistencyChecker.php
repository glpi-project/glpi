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

namespace tests\units\Glpi\System\Diagnostic;

class DatabaseSchemaConsistencyChecker extends \GLPITestCase {

   protected function sqlProvider(): iterable {
      // `date_creation` should always be associated with `date_mod`
      yield [
         'create_table_sql'   => <<<SQL
CREATE TABLE `%s` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
         ,
         'expected_missing'   => [
            'date_mod'
         ],
      ];
      yield [
         'create_table_sql'   => <<<SQL
CREATE TABLE `%s` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
         ,
         'expected_missing'   => [
            'date_creation'
         ],
      ];
      yield [
         'create_table_sql'   => <<<SQL
CREATE TABLE `%s` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL
         ,
         'expected_missing'   => [],
      ];
   }

   /**
    * @dataProvider sqlProvider
    */
   public function testGetMissingFields(
      string $create_table_sql,
      array $expected_missing
   ) {

      global $DB;

      $table_name = sprintf('glpitests_%s', uniqid());

      $this->newTestedInstance($DB);
      $DB->query(sprintf($create_table_sql, $table_name));
      $missing_fields = $this->testedInstance->getMissingFields($table_name);
      $DB->query(sprintf('DROP TABLE `%s`', $table_name));

      $this->array($missing_fields)->isEqualTo($expected_missing);
   }
}
