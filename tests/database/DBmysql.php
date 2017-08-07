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

class DBmysql extends atoum {

   private $olddb;

   public function beforeTestMethod($method) {
      $this->olddb = new \DB();
      $this->olddb->dbdefault = 'glpitest0723';
      $this->olddb->connect();
      $this->boolean($this->olddb->connected)->isTrue();
   }

   public function afterTestMethod($method) {
      $this->olddb->close();
   }

   /**
    * Test updated database against fresh install
    *
    * @return void
    */
   public function testUpdatedDatabase() {
      global $DB;

      $fresh_tables = $DB->list_tables();
      foreach ($fresh_tables as $fresh_table) {
         $table = $fresh_table['TABLE_NAME'];
         $this->boolean($this->olddb->tableExists($table, false))->isTrue("Table $table does not exists from migration!");

         $fresh         = $DB->query("SHOW CREATE TABLE `$table`")->fetch_row();
         //get table index
         $fresh_idx   = preg_grep(
            "/^\s\s+?KEY/",
            array_map(
               function($idx) { return rtrim($idx, ','); },
               explode("\n", $fresh[1])
            )
         );
         //get table schema, without index, without AUTO_INCREMENT
         $fresh         = preg_replace(
            [
               "/\s\s+KEY .*/",
               "/AUTO_INCREMENT=\d+ /"
            ],
            "",
            $fresh[1]
         );
         $updated       = $this->olddb->query("SHOW CREATE TABLE `$table`")->fetch_row();
         //get table index
         $updated_idx   = preg_grep(
            "/^\s\s+?KEY/",
            array_map(
               function($idx) { return rtrim($idx, ','); },
               explode("\n", $updated[1])
            )
         );
         //get table schema, without index, without AUTO_INCREMENT
         $updated       = preg_replace(
            [
               "/\s\s+KEY .*/",
               "/AUTO_INCREMENT=\d+ /"
            ],
            "",
            $updated[1]
         );

         //compare table schema
         $this->string($updated)->isIdenticalTo($fresh);
         //check index
         $fresh_diff = array_diff($fresh_idx, $updated_idx);
         $this->array($fresh_diff)->isEmpty("Index missing in update for $table: " . implode(', ', $fresh_diff));
         $update_diff = array_diff($updated_idx, $fresh_idx);
         $this->array($update_diff)->isEmpty("Index missing in empty for $table: " . implode(', ', $update_diff));
      }
   }
}
