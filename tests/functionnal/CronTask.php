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

use DbTestCase;

/* Test for inc/crontask.class.php */

class CronTask extends DbTestCase {

   public function registerProvider() {
      return [
         ['CoreNonExistent', 'CoreTest1', 30, [], false], // Non-existent core class
         ['CronTask', 'CoreTest2', 30, [], true], // Existing core class
         ['PluginTestItemtype', 'PluginTest1', 30, [], true] // Plugin class. Existence not checked.
      ];
   }

   /**
    * @dataProvider registerProvider
    */
   public function testRegister($itemtype, $name, $frequency, $options, $expect_pass) {
      $result = \CronTask::register($itemtype, $name, $frequency, $options);
      if ($expect_pass) {
         $this->variable($result)->isNotEqualTo(false);
      } else {
         $this->variable($result)->isEqualTo(false);
      }
   }

   public function testUnregister() {
      global $DB;

      // Register task for any plugin class. Only plugins are supported with the unregister method.
      $plugin_task = \CronTask::register('PluginTestItemtype', 'PluginTest1', 30, []);
      $this->variable($plugin_task)->isNotEqualTo(false);

      // Try un-registering the task
      $result = \CronTask::unregister('Test');
      $this->boolean($result)->isTrue();
      // Check the delete actually worked
      $iterator = $DB->request([
         'SELECT' => ['id'],
         'FROM'   => \CronTask::getTable(),
         'WHERE'  => ['itemtype' => 'PluginTestItemtype']
      ]);
      $this->integer($iterator->count())->isEqualTo(0);
   }
}
